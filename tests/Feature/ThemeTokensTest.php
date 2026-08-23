<?php

declare(strict_types=1);

/*
| テーマトークンの整合性。
|
| ダークの定義は「自動（@media prefers-color-scheme）」と「明示（:root.dark）」の
| 2 箇所にあり、CSS では 1 箇所にまとめられない。
| 片方にだけ変数を足すと、その設定のときだけ色が崩れる。
|
| 実際に図の 4 変数が @media 側に無く、「自動 + OS がダーク」のときだけ
| ノードのカードが白いまま文字が薄い色になり、読めなくなっていた。
| 目視でしか気づけない類の不具合なので、ここで機械的に固定する。
*/

/**
 * app.css から指定したセレクタのカスタムプロパティを取り出す。
 *
 * @return array<string, string>
 */
function cssCustomProperties(string $selector): array
{
    $css = file_get_contents(resource_path('css/app.css'));

    $position = strpos($css, $selector.' {');
    expect($position)->not->toBeFalse("セレクタ {$selector} が app.css に見つからない");

    $body = substr($css, $position + strlen($selector) + 2);
    $body = substr($body, 0, strpos($body, '}'));

    preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $body, $matches, PREG_SET_ORDER);

    $properties = [];

    foreach ($matches as $match) {
        $properties[$match[1]] = trim($match[2]);
    }

    return $properties;
}

it('自動モードと明示ダークが同じトークンを持つ', function () {
    $auto = cssCustomProperties(':root:not(.light)');
    $explicit = cssCustomProperties(':root.dark');

    expect($auto)->not->toBeEmpty()
        ->and($explicit)->not->toBeEmpty()
        ->and($auto)->toEqual($explicit);
});

it('ライトとダークが同じトークンを定義している', function () {
    // どちらか一方にしか無いトークンがあると、その配色のときだけ
    // 別のトークンへフォールバックして意図しない色になる。
    $light = array_keys(cssCustomProperties(':root'));
    $dark = array_keys(cssCustomProperties(':root.dark'));

    sort($light);
    sort($dark);

    expect($light)->toEqual($dark);
});
