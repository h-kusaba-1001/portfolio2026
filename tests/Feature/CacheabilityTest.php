<?php

declare(strict_types=1);

/*
| P-1 / U1-PF-4 / NFR-S9 の検証。
|
| CloudFront で HTML をキャッシュするには、レスポンスに Set-Cookie が
| 付いていないことが前提になる。セッションと CSRF を web グループから
| 外したことが、うっかり戻されていないかをここで固定する。
*/

it('トップページのレスポンスにクッキーが付かない', function () {
    $response = $this->get('/');

    $response->assertOk();

    expect($response->headers->getCookies())->toBeEmpty()
        ->and($response->headers->has('Set-Cookie'))->toBeFalse();
});

it('エラーレスポンスにもクッキーが付かない', function () {
    $response = $this->get('/this-path-does-not-exist');

    expect($response->headers->getCookies())->toBeEmpty();
});

/*
| P-2 の検証。
|
| Lift の既定は CachingDisabled なので、CloudFront 側でキャッシュポリシーを
| 差し替えたうえで、オリジンが max-age を返す必要がある。
| ここではオリジン側（Laravel）の責務だけを固定する。
*/

it('トップページは 5 秒キャッシュ可能として返る', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('Cache-Control', 'max-age=5, public');
});

it('HEAD も GET と同じキャッシュ指示を返す', function () {
    // CloudFront は GET と HEAD をどちらもキャッシュする。
    // ここが食い違うと、先に届いた方の判断でキャッシュが決まってしまう。
    $response = $this->head('/');

    $response->assertHeader('Cache-Control', 'max-age=5, public');
});

it('エラーレスポンスはキャッシュさせない', function () {
    $response = $this->get('/this-path-does-not-exist');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});
