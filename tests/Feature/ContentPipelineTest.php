<?php

declare(strict_types=1);

use App\Application\Content\GetPortfolioContent;
use App\Domain\Content\ContentRepositoryInterface;
use App\Domain\Content\SectionId;

/*
| UoW-2 のテスト（business-logic-model.md §8 の T-1〜T-9）。
|
| 実データ（content/*.md）に対する検証と、
| 一時ディレクトリに置いた異常系の検証を分けている。
*/

function loadSections(): array
{
    $content = app(GetPortfolioContent::class)();

    return $content->sections();
}

function useTempContent(array $files): void
{
    $dir = sys_get_temp_dir().'/content-test-'.uniqid();
    mkdir($dir);

    foreach ($files as $name => $body) {
        file_put_contents($dir.'/'.$name, $body);
    }

    config(['content.path' => $dir, 'content.cache.enabled' => false]);

    // バインディングを作り直して、新しいパスを反映させる
    app()->forgetInstance(ContentRepositoryInterface::class);
}

// --- T-1: stack.md の分割 -----------------------------------------------

it('T-1: stack.md から 7 ブロックが取れ、lead が空でない', function () {
    $content = app(GetPortfolioContent::class)();
    $stack = $content->section(SectionId::STACK);

    expect($stack->isAvailable)->toBeTrue()
        ->and($stack->blocks)->toHaveCount(7)
        ->and($stack->lead)->not->toBe('');

    // lead は「H1 の後、最初の H2 の前」の本文。
    // **文面そのものは検証しない**（原稿は編集対象なので、書き換えるたびに
    // テストが落ちるのは分割の検証として意味がない）。
    // 分割位置が正しいことだけを、見出しタグが残っていないかで見る。
    // 本文中にサービス名が出てくるのは普通なので、文字列では判定できない。
    expect($stack->lead)->not->toContain('<h1')
        ->and($stack->lead)->not->toContain('<h2');

    $keys = array_map(fn ($b) => $b->key, $stack->blocks);

    // S3 は UoW-4 で追加した（構成図のノードに対応させるため）
    expect($keys)->toBe([
        'cloudfront',
        's3',
        'api gateway',
        'lambda (bref)',
        'laravel + inertia.js',
        'デプロイ: osls',
        '拡張ポイント',
    ]);
});

// --- T-2: H2 を持たないファイル（今回見つけた食い違いの回帰テスト） -------

it('T-2: H2 が無いセクションも available で、lead に本文が入る', function () {
    $content = app(GetPortfolioContent::class)();

    foreach ([SectionId::EXPERIENCE, SectionId::NEXT] as $id) {
        $section = $content->section($id);

        expect($section->isAvailable)->toBeTrue("{$id->value} が失敗扱いになっている")
            ->and($section->blocks)->toBe([])
            ->and(trim($section->lead))->not->toBe('');
    }
});

// --- T-3: タイトルの取得 -------------------------------------------------

it('T-3: title を H1 から取り、H1 が無ければ既定値にフォールバックする', function () {
    $content = app(GetPortfolioContent::class)();

    // 期待値は career.md の H1 そのものを読む。
    // 文面を直書きすると、原稿を書き換えるたびに落ちてしまう。
    $heading = trim(str_replace('#', '', strtok(file_get_contents(base_path('content/career.md')), "\n")));

    expect($heading)->not->toBe('')
        ->and($content->section(SectionId::CAREER)->title)->toBe($heading)
        ->and($content->section(SectionId::CAREER)->title)
        ->not->toBe(SectionId::CAREER->defaultTitle());

    useTempContent(['stack.md' => "本文だけのファイル\n"]);

    $section = app(ContentRepositoryInterface::class)->find(SectionId::STACK);

    expect($section->title)->toBe(SectionId::STACK->defaultTitle());
});

// --- T-4: H1 は本文に含めない --------------------------------------------

it('T-4: 本文 HTML に H1 が含まれない', function () {
    $content = app(GetPortfolioContent::class)();

    foreach ($content->sections() as $section) {
        expect($section->lead)->not->toContain('<h1');

        foreach ($section->blocks as $block) {
            expect($block->html)->not->toContain('<h1');
        }
    }
});

// --- T-5: 見出しの正規化 -------------------------------------------------

it('T-5: 全角空白と大文字小文字の違いを吸収してキーが一致する', function () {
    useTempContent([
        'stack.md' => "# 技術構成\n\nリード\n\n##   API　Gateway  \n\n本文\n",
    ]);

    $section = app(ContentRepositoryInterface::class)->find(SectionId::STACK);

    expect($section->blocks[0]->key)->toBe('api gateway')
        ->and($section->blocks[0]->heading)->toBe('API　Gateway')
        ->and($section->blockByKey('api gateway'))->not->toBeNull();
});

// --- T-6: ファイル不在 ---------------------------------------------------

it('T-6: 1 セクションが欠けても他のセクションは表示される', function () {
    useTempContent([
        'stack.md' => "# 技術構成\n\nリード\n",
        'experience.md' => "# やってきたこと\n\n- あれ\n",
        'next.md' => "# これから\n\n本文\n",
        // career.md をあえて置かない
    ]);

    $sections = loadSections();

    expect($sections)->toHaveCount(4);

    $byId = [];
    foreach ($sections as $s) {
        $byId[$s->id->value] = $s;
    }

    expect($byId['career']->isAvailable)->toBeFalse()
        ->and($byId['career']->title)->toBe(SectionId::CAREER->defaultTitle())
        ->and($byId['stack']->isAvailable)->toBeTrue()
        ->and($byId['next']->isAvailable)->toBeTrue();
});

// --- T-7: 本文が空 -------------------------------------------------------

it('T-7: 本文が空のファイルは失敗として扱う', function () {
    useTempContent(['stack.md' => "# 技術構成\n\n   \n"]);

    $sections = loadSections();

    $stack = collect($sections)->firstWhere(fn ($s) => $s->id === SectionId::STACK);

    expect($stack->isAvailable)->toBeFalse();
});

// --- T-8: 生 HTML の除去（コンテンツ経路の唯一の防御） -------------------

it('T-8: Markdown 中の生 HTML が除去される', function () {
    useTempContent([
        'stack.md' => "# 技術構成\n\n<script>alert(1)</script>\n\n通常の本文\n\n"
            ."## CloudFront\n\n<iframe src=\"http://example.com\"></iframe>\n\n本文\n",
    ]);

    $section = app(ContentRepositoryInterface::class)->find(SectionId::STACK);

    $html = $section->lead.implode('', array_map(fn ($b) => $b->html, $section->blocks));

    expect($html)->not->toContain('<script')
        ->and($html)->not->toContain('<iframe')
        ->and($html)->toContain('通常の本文');
});

// --- T-9: 失敗をキャッシュしない ----------------------------------------

it('T-9: 失敗はキャッシュされず、復旧後に読めるようになる', function () {
    $dir = sys_get_temp_dir().'/content-cache-test-'.uniqid();
    mkdir($dir);

    foreach (['stack.md', 'experience.md', 'next.md'] as $f) {
        file_put_contents($dir.'/'.$f, "# 見出し\n\n本文\n");
    }

    config(['content.path' => $dir, 'content.cache.enabled' => true]);
    app()->forgetInstance(ContentRepositoryInterface::class);

    // career.md が無い状態
    expect(app(GetPortfolioContent::class)()->section(SectionId::CAREER)->isAvailable)->toBeFalse();

    // 置いたら、次の取得で読めること（失敗がキャッシュされていない）
    file_put_contents($dir.'/career.md', "# キャリア\n\n本文\n");

    expect(app(GetPortfolioContent::class)()->section(SectionId::CAREER)->isAvailable)->toBeTrue();
});
