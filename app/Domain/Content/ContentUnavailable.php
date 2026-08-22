<?php

declare(strict_types=1);

namespace App\Domain\Content;

use RuntimeException;
use Throwable;

/**
 * セクションの内容が取得・変換できなかったことを表す（business-rules.md R-3）。
 *
 * メッセージにファイルの絶対パスを含めない（NFR-S6 / SECURITY-09）。
 * 原因は previous に連結し、ログにのみ出力する。
 */
final class ContentUnavailable extends RuntimeException
{
    private function __construct(
        private readonly SectionId $sectionId,
        private readonly ContentUnavailableReason $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Content for section "%s" is unavailable (%s).', $sectionId->value, $reason->value),
            0,
            $previous,
        );
    }

    public static function fileMissing(SectionId $id): self
    {
        return new self($id, ContentUnavailableReason::FILE_MISSING);
    }

    public static function fileUnreadable(SectionId $id, ?Throwable $previous = null): self
    {
        return new self($id, ContentUnavailableReason::FILE_UNREADABLE, $previous);
    }

    public static function bodyEmpty(SectionId $id): self
    {
        return new self($id, ContentUnavailableReason::BODY_EMPTY);
    }

    public static function conversionFailed(SectionId $id, ?Throwable $previous = null): self
    {
        return new self($id, ContentUnavailableReason::CONVERSION_FAILED, $previous);
    }

    public function sectionId(): SectionId
    {
        return $this->sectionId;
    }

    public function reason(): ContentUnavailableReason
    {
        return $this->reason;
    }
}
