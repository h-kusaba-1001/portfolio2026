<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Content\ContentRepositoryInterface;
use App\Domain\Content\MarkdownParserInterface;
use App\Infrastructure\Content\CachedContentRepository;
use App\Infrastructure\Content\CommonMarkParser;
use App\Infrastructure\Content\MarkdownContentRepository;
use App\Support\PrerenderedPage;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * インターフェースと実装の束ね付けを 1 箇所に集約する（services.md §DI）。
     */
    public function register(): void
    {
        $this->app->singleton(ConverterInterface::class, static function (): ConverterInterface {
            // business-rules.md R-4: 変換結果を dangerouslySetInnerHTML に渡すため、
            // 変換元の時点で危険な要素を落としておく。
            // 「自前のコンテンツだから安全」とは考えない（content/ は US-7 の入り口）。
            return new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);
        });

        $this->app->bind(MarkdownParserInterface::class, CommonMarkParser::class);

        $this->app->bind(ContentRepositoryInterface::class, function (): ContentRepositoryInterface {
            /** @var string $contentPath */
            $contentPath = config('content.path');

            $repository = new MarkdownContentRepository(
                parser: $this->app->make(MarkdownParserInterface::class),
                contentPath: $contentPath,
            );

            if (! config('content.cache.enabled')) {
                return $repository;
            }

            return new CachedContentRepository(
                inner: $repository,
                cache: $this->app->make(CacheRepository::class),
                contentPath: $contentPath,
            );
        });
    }

    public function boot(): void
    {
        // ビルド時に描画した HTML をルートビューに渡す（A-1 / ADR-020）。
        // Blade がファイルを直接読むのを避けるため、ここで注入する。
        View::composer('app', static function ($view): void {
            $view->with('prerenderedPage', PrerenderedPage::html() ?? '');
        });
    }
}
