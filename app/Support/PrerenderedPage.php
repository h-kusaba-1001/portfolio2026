<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ビルド時に描画した HTML の置き場所（A-1 / ADR-020）。
 *
 * `bootstrap/ssr/` に置く理由:
 *   - デプロイパッケージに含まれる
 *   - SSR バンドルと同じ場所なので、対になっていることが分かりやすい
 *   - .gitignore 済みなので、生成物をリポジトリに入れずに済む
 *
 * **ローカルでは使わない。** ローカルは Markdown を編集しながら開くため、
 * 再生成を忘れると古い HTML が一瞬見えることになる。
 * 本番はデプロイのたびに必ず再生成されるので、ずれようがない。
 */
final class PrerenderedPage
{
    private static function path(): string
    {
        return base_path('bootstrap/ssr/portfolio.html');
    }

    public static function store(string $html): void
    {
        $directory = dirname(self::path());

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(self::path(), $html);
    }

    /**
     * 埋め込む HTML。無い場合とローカルの場合は null。
     */
    public static function html(): ?string
    {
        if (app()->environment('local')) {
            return null;
        }

        $path = self::path();

        return file_exists($path) ? file_get_contents($path) : null;
    }
}
