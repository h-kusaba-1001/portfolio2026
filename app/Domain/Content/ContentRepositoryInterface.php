<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * セクション内容の取得口（ポート）。
 *
 * 契約: 実装は失敗を隠さない。取得できなければ必ず例外を投げる。
 * Section::failed() を返してはならない（失敗の扱いは Application 層の責務）。
 */
interface ContentRepositoryInterface
{
    /**
     * @throws ContentUnavailable
     */
    public function find(SectionId $id): Section;
}
