<?php

declare(strict_types=1);

/*
| NFR-S6 / SECURITY-09, 15 の検証。
| 利用者に内部情報を出さないこと。
*/

it('存在しないパスで 404 を返し、内部情報を漏らさない', function () {
    $response = $this->get('/this-path-does-not-exist');

    $response->assertNotFound();

    $body = $response->getContent();

    expect($body)
        ->not->toContain('/var/www/html')
        ->not->toContain(base_path())
        ->not->toContain('Stack trace')
        ->not->toContain('vendor/laravel/framework');
});

it('エラーページがステータスコードを受け取る', function () {
    $this->get('/this-path-does-not-exist')
        ->assertInertia(
            fn ($page) => $page
                ->component('Error')
                ->where('status', 404)
        );
});
