<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\Inertia;

/**
 * トップページ（`/`）。単一ページ構成（Q4 = A）。
 *
 * UoW-1 時点では雛形。UoW-2 で `GetPortfolioContent` を注入し、
 * `content/*.md` から読んだセクションを props として渡す。
 */
final class PortfolioController
{
    public function __invoke(): Response
    {
        return Inertia::render('Portfolio', [
            // UoW-2 で GetPortfolioContent の結果に差し替える
            'sections' => [],
        ]);
    }
}
