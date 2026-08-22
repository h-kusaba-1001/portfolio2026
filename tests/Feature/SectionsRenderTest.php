<?php

declare(strict_types=1);

use App\Domain\Content\ContentRepositoryInterface;
use App\Domain\Content\Section;
use App\Domain\Content\SectionId;

/*
| UoW-3 のテスト。
|
| React の描画そのものはブラウザテストを書かない方針（ADR-009）のため、
| サーバが返す props と Blade の出力で検証できる範囲を固定する。
*/

it('4 セクション全てが available で返る', function () {
    $this->get('/')
        ->assertInertia(
            fn ($page) => $page
                ->where('sections.0.available', true)
                ->where('sections.1.available', true)
                ->where('sections.2.available', true)
                ->where('sections.3.available', true)
        );
});

it('キャリアのブロックが 4 件返る（各社の経歴）', function () {
    $this->get('/')
        ->assertInertia(fn ($page) => $page->has('sections.2.blocks', 4));
});

it('セクションが欠けても他は表示され、固定文言に必要な情報が props に残る', function () {
    // 1 セクションだけ失敗させ、ページ全体が生きていることを確認する
    $this->mock(ContentRepositoryInterface::class, function ($mock) {
        $mock->shouldReceive('find')->andReturnUsing(function (SectionId $id) {
            if ($id === SectionId::CAREER) {
                throw App\Domain\Content\ContentUnavailable::fileMissing($id);
            }

            return Section::loaded($id, $id->defaultTitle(), '<p>本文</p>', []);
        });
    });

    $this->get('/')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('sections.2.available', false)
                ->where('sections.2.title', 'キャリアの変遷')
                ->where('sections.0.available', true)
        );
});

it('Blade が OGP と meta description を静的に出力する', function () {
    // SSR を導入していないため（ADR-008）、検索エンジンやリンクプレビューが
    // 読むのは Blade が出力するこの部分だけ。
    $body = $this->get('/')->getContent();

    expect($body)
        ->toContain('<meta name="description"')
        ->toContain('og:title')
        ->toContain('og:description')
        ->toContain('lang="ja"');
});
