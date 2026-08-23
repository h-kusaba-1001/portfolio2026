<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Domain\Content\PortfolioContent;
use App\Domain\Content\Section;

/**
 * ドメインオブジェクトを Inertia の props に変換する。
 *
 * もともと PortfolioController の private メソッドだった。
 * 「変換対象が 1 ページだけなので分離しない」と判断していたが、
 * **ビルド時プリレンダ（A-1 / ADR-020）が同じ props を必要とする**ため、
 * 呼び出し側が 2 つになった。分離する理由ができたので切り出す。
 *
 * 表示に不要な内部情報（ファイルパス・更新時刻・例外情報・キャッシュ状態）は
 * 一切含めない（NFR-S6）。
 */
final class PortfolioProps
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(PortfolioContent $content): array
    {
        return array_map(
            static fn (Section $section): array => [
                'id' => $section->id->value,
                'title' => $section->title,
                'available' => $section->isAvailable,
                'lead' => $section->lead,
                'blocks' => array_map(
                    static fn ($block): array => [
                        'heading' => $block->heading,
                        'key' => $block->key,
                        'html' => $block->html,
                    ],
                    $section->blocks,
                ),
            ],
            $content->sections(),
        );
    }
}
