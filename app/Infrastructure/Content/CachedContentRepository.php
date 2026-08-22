<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentRepositoryInterface;
use App\Domain\Content\Section;
use App\Domain\Content\SectionId;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * パース結果をキャッシュするデコレータ（business-rules.md R-6）。
 *
 * キャッシュを本体に混ぜず分離しているため、
 * テストでは素の MarkdownContentRepository を束ねればキャッシュの影響を受けない。
 *
 * 失敗（ContentUnavailable）はキャッシュしない。
 * 例外がそのまま上位へ抜けるため、次のリクエストで再試行される。
 */
final readonly class CachedContentRepository implements ContentRepositoryInterface
{
    public function __construct(
        private ContentRepositoryInterface $inner,
        private CacheRepository $cache,
        private string $contentPath,
    ) {}

    public function find(SectionId $id): Section
    {
        $key = $this->cacheKey($id);

        // ファイル更新時刻が取れない（＝ファイルが無い）場合は
        // キャッシュを引かずに委譲する。失敗の判定は本体に任せる。
        if ($key === null) {
            return $this->inner->find($id);
        }

        $cached = $this->cache->get($key);

        if ($cached instanceof Section) {
            return $cached;
        }

        $section = $this->inner->find($id);

        $this->cache->forever($key, $section);

        return $section;
    }

    /**
     * ファイル更新時刻をキーに含めることで、
     * コンテンツを更新すれば自動的に別のキーになる（TTL を持たない理由）。
     */
    private function cacheKey(SectionId $id): ?string
    {
        $path = $this->contentPath.'/'.$id->fileName();

        if (! is_file($path)) {
            return null;
        }

        $mtime = @filemtime($path);

        if ($mtime === false) {
            return null;
        }

        return sprintf('content:%s:%d', $id->value, $mtime);
    }
}
