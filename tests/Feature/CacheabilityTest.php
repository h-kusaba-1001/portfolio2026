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
