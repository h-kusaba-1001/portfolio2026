<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * Markdown → HTML 変換の抽象（ポート）。
 * Domain が所有し、Infrastructure が実装する。
 */
interface MarkdownParserInterface
{
    /**
     * business-rules.md R-1 の規則で分割し、各部を HTML に変換する。
     *
     * @throws \Throwable 変換に失敗した場合（呼び出し側が ContentUnavailable に変換する）
     */
    public function parse(string $markdown): ParsedMarkdown;
}
