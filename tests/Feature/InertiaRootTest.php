<?php

declare(strict_types=1);

use App\Support\PrerenderedPage;

/*
| Inertia のルート要素の構造。
|
| プリレンダ（ADR-020）を入れる際、@inertia の出力を手で書き直したところ、
| **Inertia v3 がページ情報を読む <script data-page="app"> を消してしまい、
| クライアントが null を掴んで画面が真っ白になった**
| （Cannot read properties of null (reading 'component')）。
|
| 見た目の確認では気づけたが、テストは全て通っていた。
| 構造そのものを固定して、同じ壊し方を二度としないようにする。
*/

it('Inertia v3 が読む script 要素がある', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('<script data-page="app" type="application/json">');
});

it('script の中身が JSON として読め、component を持つ', function () {
    $html = $this->get('/')->getContent();

    preg_match(
        '/<script data-page="app" type="application\/json">(.*?)<\/script>/s',
        $html,
        $matches,
    );

    expect($matches)->toHaveCount(2);

    $page = json_decode($matches[1], true);

    expect($page)->toBeArray()
        ->and($page['component'])->toBe('Portfolio')
        ->and($page['props']['sections'])->not->toBeEmpty();
});

it('マウント先の id="app" はページ内に 1 つだけ', function () {
    // プリレンダした HTML が <div id="app"> ごと入ってしまうと入れ子になり、
    // Inertia がどちらを掴むか不定になる。
    PrerenderedPage::store('<main>本文</main>');
    app()['env'] = 'production';

    $html = $this->get('/')->getContent();

    expect(substr_count($html, 'id="app"'))->toBe(1);
});
