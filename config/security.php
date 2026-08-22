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

    // style-src にのみ 'unsafe-inline' を許可する（ADR-011）
    // 構成図アニメーション（UoW-4）が style 属性を書き換えるため。
    // script-src は厳格なまま維持しており、XSS の主経路は塞がれている。
    "style-src 'self' 'unsafe-inline'",

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
