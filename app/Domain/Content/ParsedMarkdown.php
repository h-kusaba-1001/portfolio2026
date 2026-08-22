<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * Markdown を分割した結果（business-rules.md R-1）。
 *
 * パーサの出力であり、Section を組み立てる材料。
 * title は H1 が無ければ null になり、呼び出し側が既定値にフォールバックする。
 */
final readonly class ParsedMarkdown
{
    /**
     * @param  list<ContentBlock>  $blocks
     */
    public function __construct(
        public ?string $title,
        public string $lead,
        public array $blocks,
    ) {}

    /** H1 を除いた本文が空か（R-3-3 の判定に使う） */
    public function isBodyEmpty(): bool
    {
        return trim($this->lead) === '' && $this->blocks === [];
    }
}
