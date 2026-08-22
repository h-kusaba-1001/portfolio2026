<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * ルートテンプレート。Blade 側で OGP・meta を静的に出力する（ADR-008）。
     */
    protected $rootView = 'app';

    /**
     * 全ページで共有する props。
     *
     * 認証もセッションも持たないため、共有するものは今のところ無い。
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return parent::share($request);
    }
}
