<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * セキュリティヘッダを全レスポンスに付与する（NFR-S1 / SECURITY-04 / ADR-015）。
 *
 * 値は config/security.php に持つ。ここは適用だけを行う。
 * 例外経路でもヘッダが付くよう、ミドルウェアスタックの外側に登録すること。
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /** @var array<string, string> $headers */
        $headers = config('security.headers', []);

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        $this->removeVersionDisclosure($response);

        return $response;
    }

    /**
     * PHP のバージョンを公開しない（SECURITY-09: フレームワーク・ランタイムの
     * バージョンを利用者に見せない）。
     *
     * PHP-FPM は expose_php が On だと X-Powered-By: PHP/8.4.x を自動で付ける。
     * Symfony のレスポンスヘッダには載らないため、SAPI 側からも消す必要がある。
     */
    private function removeVersionDisclosure(Response $response): void
    {
        $response->headers->remove('X-Powered-By');

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
    }
}
