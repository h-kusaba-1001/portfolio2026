<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ContentRepositoryInterface;
use App\Domain\Content\ContentUnavailable;
use App\Domain\Content\PortfolioContent;
use App\Domain\Content\Section;
use App\Domain\Content\SectionId;
use Psr\Log\LoggerInterface;

/**
 * 全セクションを取得し、ページ 1 枚分の内容を組み立てる。
 *
 * 本プロジェクト唯一のユースケース（services.md）。
 *
 * 失敗の捕捉をここに置いているのは、「取得できたか」と
 * 「失敗をどう見せるか」の関心を分けるため。
 * 表示方針が変わってもアダプタを触らずに済む。
 *
 * 例外を外に伝播させない。1 セクションが壊れてもページ全体は表示される。
 */
final readonly class GetPortfolioContent
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): PortfolioContent
    {
        $sections = [];

        foreach (SectionId::inDisplayOrder() as $id) {
            $sections[] = $this->loadOrFallback($id);
        }

        return new PortfolioContent($sections);
    }

    private function loadOrFallback(SectionId $id): Section
    {
        try {
            return $this->repository->find($id);
        } catch (ContentUnavailable $e) {
            // 画面に出さない詳細をログにだけ残す（NFR-S3 / SECURITY-03）。
            // ファイルパスやファイルの中身は出力しない。
            $this->logger->error('Section content unavailable', [
                'section' => $id->value,
                'reason' => $e->reason()->value,
                'exception' => $e->getPrevious() !== null
                    ? $e->getPrevious()::class
                    : $e::class,
            ]);

            return Section::failed($id);
        }
    }
}
