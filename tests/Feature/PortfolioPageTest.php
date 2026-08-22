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

it('4 セクションが表示順で渡る', function () {
    // UoW-1 では「sections が空である」ことを固定していたが、
    // UoW-2 でコンテンツ基盤を実装したため書き換えた。
    $this->get('/')
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('sections', 4)
                ->where('sections.0.id', 'stack')
                ->where('sections.1.id', 'experience')
                ->where('sections.2.id', 'career')
                ->where('sections.3.id', 'next')
        );
});

it('props に内部情報が含まれない', function () {
    $response = $this->get('/');

    $body = $response->getContent();

    // ファイルパス・更新時刻・例外情報を props に載せない（NFR-S6）
    expect($body)
        ->not->toContain('/var/www/html')
        ->not->toContain(base_path())
        ->not->toContain('mtime');
});

it('各セクションが必要なキーを持つ', function () {
    $this->get('/')
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('sections.0', fn (AssertableInertia $section) => $section
                    ->has('id')
                    ->has('title')
                    ->has('available')
                    ->has('lead')
                    ->has('blocks')
                )
        );
});
