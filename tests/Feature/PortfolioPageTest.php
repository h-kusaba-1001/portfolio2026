<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('トップページが 200 を返し、Portfolio コンポーネントを描画する', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Portfolio')
                ->has('sections')
        );
});

it('UoW-1 時点では sections が空である', function () {
    // UoW-2 でコンテンツ基盤を実装した時点で、このテストは書き換える。
    // 「まだ実装していない」ことを明示的に固定しておく。
    $this->get('/')
        ->assertInertia(
            fn (AssertableInertia $page) => $page->where('sections', [])
        );
});
