<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Content\GetPortfolioContent;
use App\Domain\Content\PortfolioContent;
use App\Domain\Content\Section;
use Inertia\Inertia;
use Inertia\Response;

/**
 * トップページ（`/`）。単一ページ構成（Q4 = A）。
 *
 * 専用の Presenter クラスは作らない。変換対象が 1 ページのみで、
 * 分離しても得るものがないため（ADR-004 の判断基準）。
 */
final readonly class PortfolioController
{
    public function __construct(
        private GetPortfolioContent $getPortfolioContent,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('Portfolio', [
            'sections' => $this->toProps(($this->getPortfolioContent)()),
        ]);
    }

    /**
     * ドメインオブジェクトを props 配列に変換する。
     *
     * 表示に不要な内部情報（ファイルパス・更新時刻・例外情報・キャッシュ状態）は
     * 一切含めない（NFR-S6）。
     *
     * @return list<array<string, mixed>>
     */
    private function toProps(PortfolioContent $content): array
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
