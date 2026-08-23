<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| セキュリティヘッダ（NFR-S1 / SECURITY-04 / ADR-015）
|--------------------------------------------------------------------------
|
| ヘッダの値をここに置き、SecurityHeaders ミドルウェアは適用のみを担当する。
| こうしておくと Feature テストが「設定に書いた値」と「実レスポンス」を
| 突き合わせられるため、設定を変えたのにテストが通る状態を防げる。
|
| CloudFront の Response Headers Policy は使わない（ADR-015）。
| HTML を返す経路は必ず Lambda を通るため、ここで付ければ漏れがない。
|
*/

$isLocal = env('APP_ENV') === 'local';

$csp = [
    "default-src 'self'",
    "script-src 'self'",

    // 本番では style-src も厳格にする（ADR-011 は ADR-018 で Superseded）。
    // 当初 'unsafe-inline' を許可した理由は「構成図が style 属性を書き換えるため」
    // だったが、実装では <animateMotion> を使ったため不要だった。
    // アプリ側の style 属性も全て静的クラスと SVG 属性に置き換え済み。
    //
    // ローカルは Vite の HMR が <style> を注入するため、開発時のみ許可する。
    $isLocal
        ? "style-src 'self' 'unsafe-inline'"
        : "style-src 'self'",

    "img-src 'self' data:",
    "font-src 'self'",

    // ローカルでは Vite の HMR が WebSocket を張るため、開発時のみ許可する。
    // 本番・テスト環境では 'self' のみ。
    $isLocal
        ? "connect-src 'self' ws://localhost:* http://localhost:*"
        : "connect-src 'self'",

    "frame-ancestors 'none'",
    "base-uri 'self'",

    // フォームが存在しないため、送信先を一切許可しない
    "form-action 'none'",
];

return [

    'headers' => [
        'Content-Security-Policy' => implode('; ', $csp),
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],

];
