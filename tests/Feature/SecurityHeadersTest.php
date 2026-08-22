<?php

declare(strict_types=1);

/*
| NFR-S1 / SECURITY-04 / ADR-015 の検証。
|
| ヘッダは CloudFront ではなく Laravel のミドルウェアで付与するため、
| ここでの検証がそのまま本番の挙動を表す。
|
| 設定値そのものを突き合わせているのは、config/security.php を変更したのに
| テストが通ってしまう状態を避けるため。
*/

it('付与するヘッダが設定ファイルの内容と一致する', function () {
    $response = $this->get('/');

    $headers = config('security.headers');

    expect($headers)->not->toBeEmpty();

    foreach ($headers as $name => $value) {
        expect($response->headers->get($name))->toBe($value);
    }
});

it('必要なセキュリティヘッダが揃っている', function () {
    $response = $this->get('/');

    foreach ([
        'Content-Security-Policy',
        'Strict-Transport-Security',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'Referrer-Policy',
    ] as $header) {
        expect($response->headers->has($header))->toBeTrue("{$header} が付与されていない");
    }
});

it('CSP の script-src を緩めていない', function () {
    // ADR-011: 'unsafe-inline' を許可するのは style-src のみ。
    // script-src が緩んだらこのテストが落ちる。
    $csp = config('security.headers.Content-Security-Policy');

    expect($csp)->toContain("script-src 'self'")
        ->and($csp)->not->toContain("script-src 'self' 'unsafe-inline'")
        ->and($csp)->not->toContain("'unsafe-eval'");
});

it('PHP のバージョンを漏らさない', function () {
    // SECURITY-09: ランタイムのバージョンを利用者に見せない。
    // PHP-FPM は expose_php=On だと X-Powered-By を自動で付けるため、明示的に消している。
    $response = $this->get('/');

    expect($response->headers->has('X-Powered-By'))->toBeFalse();
});

it('エラーレスポンスにもヘッダが付く', function () {
    // SecurityHeaders を prepend しているため、例外経路の戻りでも付くはず
    $response = $this->get('/this-path-does-not-exist');

    $response->assertNotFound();

    expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
});
