<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentRepositoryInterface;
use App\Domain\Content\ContentUnavailable;
use App\Domain\Content\MarkdownParserInterface;
use App\Domain\Content\Section;
use App\Domain\Content\SectionId;
use Throwable;

/**
 * content/*.md を読み、Section を組み立てる。
 *
 * 失敗を隠さず ContentUnavailable を投げる（ポートの契約）。
 * 「どう見せるか」は決めない。
 *
 * 失敗の条件は business-rules.md R-3 の 4 つのみ。
 * 「H2 が無い」「H1 が無い」は失敗にしない（experience.md / next.md が該当）。
 */
final readonly class MarkdownContentRepository implements ContentRepositoryInterface
{
    public function __construct(
        private MarkdownParserInterface $parser,
        private string $contentPath,
    ) {}

    public function find(SectionId $id): Section
    {
        $path = $this->contentPath.'/'.$id->fileName();

        if (! is_file($path)) {
            throw ContentUnavailable::fileMissing($id);
        }

        if (! is_readable($path)) {
            throw ContentUnavailable::fileUnreadable($id);
        }

        $markdown = @file_get_contents($path);

        if ($markdown === false) {
            throw ContentUnavailable::fileUnreadable($id);
        }

        try {
            $parsed = $this->parser->parse($markdown);
        } catch (Throwable $e) {
            throw ContentUnavailable::conversionFailed($id, $e);
        }

        // R-3-3: H1 を除いた本文が空なら失敗として扱う
        if ($parsed->isBodyEmpty()) {
            throw ContentUnavailable::bodyEmpty($id);
        }

        return Section::loaded(
            id: $id,
            title: $parsed->title ?? $id->defaultTitle(),
            lead: $parsed->lead,
            blocks: $parsed->blocks,
        );
    }
}
