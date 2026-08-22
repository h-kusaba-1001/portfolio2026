<?php

use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

// 単一ページ構成（Q4 = A）。セクションは 1 スクロールに並べる。
Route::get('/', PortfolioController::class)->name('portfolio');
