<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 相関 ID を採番し、ログコンテキストに載せる（U1-OB-2 / SECURITY-03）。
 *
 * Bref の FPM ランタイムは Lambda のリクエスト ID を `X-Request-ID` ヘッダとして
 * リクエストに載せる。本番ではこれを使い、CloudWatch Logs の REPORT 行と
 * 突き合わせられるようにする（Q5 = A）。
 * ローカル・テストでは存在しないため UUID にフォールバックする。
 *
 * ヘッダは外部から送られてくる値でもあるため、そのままログに載せない。
 * 形式を検証し、想定外の文字列は破棄して自前で採番する
 * （ログインジェクション対策。SECURITY-03「ログに信用できない値を載せない」）。
 */
final class RequestId
{
    /** Lambda のリクエスト ID は UUID 形式。念のため長さにも上限を設ける。 */
    private const PATTERN = '/^[A-Za-z0-9\-]{1,64}$/';

    public function handle(Request $request, Closure $next): Response
    {
        Log::withContext(['request_id' => $this->resolveRequestId($request)]);

        return $next($request);
    }

    private function resolveRequestId(Request $request): string
    {
        $header = $request->header('X-Request-ID');

        if (is_string($header) && preg_match(self::PATTERN, $header) === 1) {
            return $header;
        }

        return (string) Str::uuid();
    }
}
