<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * 見出しを照合用のキーに正規化する（business-rules.md R-2）。
 *
 * 表示には使わない。構成図ノードとの突き合わせにのみ使う。
 *
 * ⚠️ この規則は TypeScript 側にも同じものが必要になる（UoW-4）。
 *    ずれると実行時まで気付けないため、実装を変えるときは必ず両方を直し、
 *    「全ノードのキーが実在するか」を検証するテストで担保すること。
 */
final class HeadingKey
{
    /** 半角空白類に加えて全角空白（U+3000）も空白として扱う */
    private const WHITESPACE = '[\s\x{3000}]';

    public static function from(string $heading): string
    {
        // 1. 前後の空白を除去
        $normalized = (string) preg_replace(
            '/^'.self::WHITESPACE.'+|'.self::WHITESPACE.'+$/u',
            '',
            $heading,
        );

        // 2. 連続する空白を半角空白 1 つに畳む
        $normalized = (string) preg_replace('/'.self::WHITESPACE.'+/u', ' ', $normalized);

        // 3. ASCII の英字のみ小文字化する。
        //    日本語やその他の文字は変えない（記号の除去や全角→半角変換もしない）。
        return (string) preg_replace_callback(
            '/[A-Z]/',
            static fn (array $m): string => strtolower($m[0]),
            $normalized,
        );
    }
}
