<?php

declare(strict_types=1);

use App\Support\PrerenderedPage;

/*
| A-1 の検証。
|
| SSR を持たないため、HTML には内容が実体として存在しない。
| 2026-08-24 の実測では **<script> を除くと本文は 42 文字**しか残らず、
| 面接官が AI に URL を読ませても技術構成も経歴も伝わらなかった。
|
| ビルド時プリレンダ（ADR-020）でこれを埋める。
| ここでは「埋め込みの配線が生きていること」を固定する。
| 描画結果そのものは Node が作るため、テストからは検証しない。
*/

it('プリレンダがあれば #app の中に埋め込まれる', function () {
    PrerenderedPage::store('<main data-prerendered>本文</main>');
    app()['env'] = 'production';

    $html = $this->get('/')->getContent();

    expect($html)->toContain('data-prerendered')
        // #app の外に出ていたら React に消されず二重表示になる
        ->and($html)->toMatch('/<div id="app"[^>]*><main data-prerendered/');
});

it('ローカルでは埋め込まない', function () {
    // ローカルは Markdown を編集しながら開くため、
    // 再生成を忘れると古い HTML が見えてしまう。
    PrerenderedPage::store('<main data-prerendered>古い本文</main>');
    app()['env'] = 'local';

    expect($this->get('/')->getContent())->not->toContain('data-prerendered');
});

it('プリレンダが無くてもページは壊れない', function () {
    $path = base_path('bootstrap/ssr/portfolio.html');
    @unlink($path);
    app()['env'] = 'production';

    $this->get('/')->assertOk();
});
