<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Content\GetPortfolioContent;
use App\Http\Presenters\PortfolioProps;
use Inertia\Inertia;
use Inertia\Response;

/**
 * トップページ（`/`）。単一ページ構成（Q4 = A）。
 *
 * props の組み立ては PortfolioProps に置く。
 * ビルド時プリレンダ（ADR-020）が同じものを必要とするため。
 */
final readonly class PortfolioController
{
    public function __construct(
        private GetPortfolioContent $getPortfolioContent,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('Portfolio', [
            'sections' => PortfolioProps::sections(($this->getPortfolioContent)()),
        ]);
    }
}
