<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * 読み込み失敗の理由（business-rules.md R-3）。
 * ログの分析用。画面表示には使わない。
 */
enum ContentUnavailableReason: string
{
    case FILE_MISSING = 'file_missing';
    case FILE_UNREADABLE = 'file_unreadable';
    case BODY_EMPTY = 'body_empty';
    case CONVERSION_FAILED = 'conversion_failed';
}
