<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTML レスポンスに Cache-Control を付ける（U1-PF-4 / NFR-S9）。
 *
 * CloudFront にこの値を尊重させることで、同一エッジへの連続アクセスが
 * Lambda に届かなくなる。濫用対策の一部でもある（ADR-013）。
 *
 * TTL を 5 秒にしている理由:
 *   - 「キャッシュ無し → 5 秒」で削減効果のほとんどを取れる。
 *     そこから伸ばして得られる差は小さく、費用の天井は
 *     アカウントの同時実行上限が既に担保している
 *   - versionedAssets を使っていないため、デプロイで古いアセットが消える。
 *     HTML を長くキャッシュすると、消えた JS を指し続けて画面が壊れる。
 *     5 秒ならその窓が実質無視できる
 *   - デプロイ結果の確認が待たされない
 *
 * 成功した GET / HEAD 応答にだけ付ける。エラー応答はキャッシュさせない。
 * HEAD を外すと、CloudFront が GET と HEAD で別々の判断をしてしまう。
 */
final class CacheControl
{
    private const MAX_AGE = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isReadRequest = in_array($request->getMethod(), ['GET', 'HEAD'], true);

        if ($isReadRequest && $response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', 'public, max-age='.self::MAX_AGE);

            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
