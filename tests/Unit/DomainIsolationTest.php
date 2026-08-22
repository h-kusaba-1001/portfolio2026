<?php

declare(strict_types=1);

/*
| T-10: Domain 層がフレームワークに依存していないことを検証する。
|
| Application Design の不変条件:
|   - Domain/ は Illuminate\* を import しない
|   - Domain/ は League\CommonMark\* を import しない
|
| 依存の向きが逆転していることはコードを読めば分かるが、
| 「うっかり use 文を足す」ことは起きうる。ここで固定する。
*/

it('T-10: Domain 層がフレームワークと変換ライブラリに依存していない', function () {
    $domainPath = dirname(__DIR__, 2).'/app/Domain';

    $files = glob($domainPath.'/**/*.php') ?: [];

    expect($files)->not->toBeEmpty('Domain 層のファイルが見つからない');

    $violations = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        foreach (['Illuminate\\', 'League\\CommonMark\\', 'Inertia\\'] as $forbidden) {
            if (str_contains($source, 'use '.$forbidden)) {
                $violations[] = basename($file).' -> '.$forbidden;
            }
        }
    }

    expect($violations)->toBe([]);
});
