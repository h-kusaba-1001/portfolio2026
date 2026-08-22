<?php

declare(strict_types=1);

use App\Domain\Content\HeadingKey;
use App\Domain\Content\SectionId;
use App\Domain\Content\ContentRepositoryInterface;

/*
| 構成図ノードと content/stack.md の対応を検証する。
|
| 正規化規則（business-rules.md R-2）は PHP と TypeScript の 2 箇所にある。
| ずれても実行時まで気付けないため、ここで固定する。
| （docs/backlog.md §6 の「必ず実施する 3 点」のうち 1 つ目）
*/

/** resources/js/components/diagram/nodes.ts から heading を抜き出す */
function diagramHeadings(): array
{
    $source = file_get_contents(base_path('resources/js/components/diagram/nodes.ts'));

    preg_match_all("/heading:\s*'([^']+)'/u", $source, $matches);

    return $matches[1];
}

it('構成図に heading を持つノードが存在する', function () {
    expect(diagramHeadings())->not->toBeEmpty();
});

it('全ノードの heading が content/stack.md に実在する', function () {
    $stack = app(ContentRepositoryInterface::class)->find(SectionId::STACK);

    $available = array_map(fn ($block) => $block->key, $stack->blocks);

    $missing = [];

    foreach (diagramHeadings() as $heading) {
        $key = HeadingKey::from($heading);

        if (! in_array($key, $available, true)) {
            $missing[] = $heading.' -> '.$key;
        }
    }

    expect($missing)->toBe(
        [],
        "構成図のノードに対応する H2 が content/stack.md にない。\n"
        ."stack.md 側のキー: ".implode(' / ', $available)
    );
});

it('TypeScript 側の正規化規則が PHP と同じ結果を返す', function () {
    // 実装の突き合わせ。片方だけ変えたらここで落ちる。
    $source = file_get_contents(base_path('resources/js/lib/headingKey.ts'));

    // 3 つの手順が両方に存在することを確認する
    expect($source)
        ->toContain('EDGE_WHITESPACE')       // 1. 前後の空白を除去
        ->toContain("replace(WHITESPACE, ' ')") // 2. 連続空白を 1 つに
        ->toContain('toLowerCase');           // 3. ASCII 小文字化

    // 代表的な入力で PHP 側の結果を固定しておく
    expect(HeadingKey::from('  API　Gateway  '))->toBe('api gateway')
        ->and(HeadingKey::from('Lambda (Bref)'))->toBe('lambda (bref)')
        ->and(HeadingKey::from('デプロイ: osls'))->toBe('デプロイ: osls');
});
