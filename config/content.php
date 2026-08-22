<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| コンテンツ（ADR-002 / ADR-003）
|--------------------------------------------------------------------------
|
| Markdown はデプロイパッケージに同梱される。データベースは使わない。
|
*/

return [

    'path' => env('CONTENT_PATH', base_path('content')),

    'cache' => [
        // ローカルでは無効にしておくと、Markdown の編集が即座に反映される。
        // 本番では有効（business-rules.md R-6-5）。
        //
        // 設定ファイルの読み込み時点ではコンテナが未準備のため、
        // app()->environment() を使わず env() で環境を判定する。
        'enabled' => (bool) env('CONTENT_CACHE_ENABLED', env('APP_ENV') !== 'local'),
    ],

];
