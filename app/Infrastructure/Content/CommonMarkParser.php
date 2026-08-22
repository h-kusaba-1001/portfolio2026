<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentBlock;
use App\Domain\Content\MarkdownParserInterface;
use App\Domain\Content\ParsedMarkdown;
use League\CommonMark\ConverterInterface;

/**
 * Markdown を title / lead / blocks に分割し、各部を HTML に変換する。
 *
 * 分割は CommonMark のノードツリーではなく、行単位の走査で行う
 * （business-logic-model.md §2 の擬似コード）。
 * ライブラリのレンダラ内部に依存せず、規則が読んで分かる形になるため。
 *
 * セキュリティ設定（生 HTML の除去など）は ConverterInterface の生成側に持つ。
 * ここは分割と変換の呼び出しに徹する。
 */
final readonly class CommonMarkParser implements MarkdownParserInterface
{
    public function __construct(
        private ConverterInterface $converter,
    ) {}

    public function parse(string $markdown): ParsedMarkdown
    {
        $title = null;
        $leadLines = [];
        $blocks = [];

        /** @var array{heading: string, lines: list<string>}|null $current */
        $current = null;

        $inFence = false;

        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            // コードフェンス内の見出しに見える行を、見出しとして扱わない
            if (preg_match('/^\s{0,3}(```|~~~)/', $line) === 1) {
                $inFence = ! $inFence;
            }

            if (! $inFence) {
                // R-1-1: 最初の H1 のみタイトルとして取り出し、本文からは除く
                if ($title === null && preg_match('/^#\s+(.*\S)\s*$/u', $line, $m) === 1) {
                    $title = trim($m[1]);

                    continue;
                }

                // R-1-3: H2 で新しいブロックを開始する
                if (preg_match('/^##\s+(.*\S)\s*$/u', $line, $m) === 1) {
                    if ($current !== null) {
                        $blocks[] = $this->toBlock($current);
                    }

                    $current = ['heading' => trim($m[1]), 'lines' => []];

                    continue;
                }
            }

            if ($current !== null) {
                $current['lines'][] = $line;
            } else {
                // R-1-5: H1 より前の本文も lead に入る
                $leadLines[] = $line;
            }
        }

        if ($current !== null) {
            $blocks[] = $this->toBlock($current);
        }

        return new ParsedMarkdown(
            title: $title,
            lead: $this->convert($leadLines),
            blocks: $blocks,
        );
    }

    /**
     * @param  array{heading: string, lines: list<string>}  $raw
     */
    private function toBlock(array $raw): ContentBlock
    {
        return new ContentBlock(
            heading: $raw['heading'],
            html: $this->convert($raw['lines']),
        );
    }

    /**
     * @param  list<string>  $lines
     */
    private function convert(array $lines): string
    {
        $source = trim(implode("\n", $lines));

        if ($source === '') {
            return '';
        }

        return trim($this->converter->convert($source)->getContent());
    }
}
