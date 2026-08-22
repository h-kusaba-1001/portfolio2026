# Component Methods

メソッドシグネチャと入出力の定義。**詳細なビジネスルール（分割規則、front matter の扱い、
エッジケース）は Functional Design（UoW-2）で確定する。**

PHP 8.4 を前提とする（ADR-007）。

---

## Domain 層

### `App\Domain\Content\SectionId`（enum: string）

```php
enum SectionId: string
{
    case STACK = 'stack';
    case EXPERIENCE = 'experience';
    case CAREER = 'career';
    case NEXT = 'next';

    public function fileName(): string;
    public function title(): string;
    public static function inDisplayOrder(): array;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `fileName()` | 対応する Markdown ファイル名を返す | — | `string`（例: `stack.md`） |
| `title()` | 画面に出すセクション見出しを返す | — | `string`（例: `技術構成`） |
| `inDisplayOrder()` | 表示順に並べた全ケースを返す | — | `list<SectionId>` |

### `App\Domain\Content\ContentBlock`（final readonly class）

```php
final readonly class ContentBlock
{
    public function __construct(
        public string $heading,
        public string $html,
    ) {}
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `__construct()` | H2 見出しと変換済み HTML の組を作る | `string $heading`, `string $html` | — |

**備考**: `heading` は構成図ノードとの対応キー（Q2 = A）。前後の空白は生成側で除去する。

### `App\Domain\Content\Section`（final readonly class）

```php
final readonly class Section
{
    private function __construct(
        public SectionId $id,
        public string $title,
        public array $blocks,      // list<ContentBlock>
        public bool $isAvailable,
    ) {}

    public static function loaded(SectionId $id, array $blocks): self;
    public static function failed(SectionId $id): self;
    public function blockByHeading(string $heading): ?ContentBlock;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `loaded()` | 読み込み成功したセクションを作る | `SectionId`, `list<ContentBlock>` | `Section` |
| `failed()` | 読み込み失敗したセクションを作る（本文なし、`isAvailable = false`） | `SectionId` | `Section` |
| `blockByHeading()` | 見出しでブロックを引く | `string $heading` | `?ContentBlock` |

**設計判断**: コンストラクタを private にし、生成経路を 2 つの名前付きコンストラクタに限定する。
`isAvailable` を外から自由に設定できると、失敗表現が壊れるため。

### `App\Domain\Content\PortfolioContent`（final readonly class）

```php
final readonly class PortfolioContent
{
    public function __construct(
        public array $sections,    // list<Section>
    ) {}

    public function section(SectionId $id): ?Section;
    public function hasFailures(): bool;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `section()` | ID でセクションを引く | `SectionId` | `?Section` |
| `hasFailures()` | 失敗したセクションが 1 つでもあるか | — | `bool` |

### `App\Domain\Content\ContentRepositoryInterface`（ポート）

```php
interface ContentRepositoryInterface
{
    /** @throws ContentUnavailable */
    public function find(SectionId $id): Section;
}
```

| メソッド | 目的 | 入力 | 出力 | 例外 |
|---|---|---|---|---|
| `find()` | 1 セクションを取得する | `SectionId` | `Section`（必ず `isAvailable = true`） | `ContentUnavailable` |

**契約**: 実装は失敗を隠さない。取得できなければ必ず例外を投げる。
`Section::failed()` を返してはならない（失敗の扱いは Application 層の責務）。

### `App\Domain\Content\MarkdownParserInterface`（ポート）

```php
interface MarkdownParserInterface
{
    /** @return list<ContentBlock> */
    public function toBlocks(string $markdown): array;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `toBlocks()` | Markdown を H2 見出し単位のブロック列に変換する | `string $markdown` | `list<ContentBlock>` |

**Functional Design で確定する事項**
- H1 見出しの扱い（セクションタイトルとして使うか捨てるか）
- H2 より前にある本文（リード文）の扱い
- H2 が 1 つも無いファイル（`career.md` など）の扱い
- 空ファイル・見出しのみのファイルの扱い

### `App\Domain\Content\ContentUnavailable`（例外）

```php
final class ContentUnavailable extends \RuntimeException
{
    public static function forSection(SectionId $id, ?\Throwable $previous = null): self;
    public function sectionId(): SectionId;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `forSection()` | セクション単位の失敗を生成する | `SectionId`, `?Throwable` | `self` |
| `sectionId()` | 失敗したセクション ID を返す | — | `SectionId` |

**セキュリティ**: メッセージにファイルの絶対パスを含めない（NFR-S6 / SECURITY-09）。
原因は `previous` に連結し、ログにのみ出力する。

---

## Application 層

### `App\Application\Content\GetPortfolioContent`（ユースケース）

```php
final readonly class GetPortfolioContent
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): PortfolioContent;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `__invoke()` | 全セクションを取得し `PortfolioContent` を組み立てる | — | `PortfolioContent` |

**振る舞い**
1. `SectionId::inDisplayOrder()` を走査する
2. 各 ID で `repository->find()` を呼ぶ
3. `ContentUnavailable` を捕捉したら `Section::failed()` に置き換え、`logger->error()` に記録する
4. 例外は外に伝播させない（ページ全体は必ず表示される: Q6 = B）

**ログ出力**: セクション ID、例外クラス名、例外メッセージ。
リクエスト ID は Laravel のログコンテキストで付与する（NFR-S3 / SECURITY-03）。

---

## Infrastructure 層

### `App\Infrastructure\Content\MarkdownContentRepository`

```php
final readonly class MarkdownContentRepository implements ContentRepositoryInterface
{
    public function __construct(
        private MarkdownParserInterface $parser,
        private string $contentPath,
    ) {}

    public function find(SectionId $id): Section;
}
```

| メソッド | 目的 | 入力 | 出力 | 例外 |
|---|---|---|---|---|
| `find()` | ファイルを読み、パースして `Section` を返す | `SectionId` | `Section` | `ContentUnavailable` |

**振る舞い**
1. `$contentPath . '/' . $id->fileName()` を解決する
2. ファイルが存在しない・読めない場合は `ContentUnavailable` を投げる
3. パーサに委譲し、結果が空配列なら `ContentUnavailable` を投げる
4. `Section::loaded()` を返す

**注意**: `$contentPath` は設定から注入する（`config/content.php`）。
`SectionId` は enum のため任意のファイル名を渡せず、パストラバーサルの経路にならない。

### `App\Infrastructure\Content\CachedContentRepository`（デコレータ）

```php
final readonly class CachedContentRepository implements ContentRepositoryInterface
{
    public function __construct(
        private ContentRepositoryInterface $inner,
        private CacheRepository $cache,
        private string $contentPath,
    ) {}

    public function find(SectionId $id): Section;
    private function cacheKey(SectionId $id): string;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `find()` | キャッシュ経由で取得する | `SectionId` | `Section` |
| `cacheKey()` | ファイル更新時刻を含むキャッシュキーを生成する | `SectionId` | `string` |

**振る舞い**
- キーは `content:{id}:{filemtime}` 形式。デプロイでファイルが入れ替われば自動的に別キーになる
- 失敗（`ContentUnavailable`）はキャッシュしない。次のリクエストで再試行できるようにする
- ファイルが存在せず `filemtime` が取れない場合は、キャッシュを引かずに `inner` へ委譲する

### `App\Infrastructure\Content\CommonMarkParser`

```php
final readonly class CommonMarkParser implements MarkdownParserInterface
{
    public function __construct(
        private ConverterInterface $converter,
    ) {}

    public function toBlocks(string $markdown): array;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `toBlocks()` | Markdown を H2 単位に分割し HTML に変換する | `string $markdown` | `list<ContentBlock>` |

**CommonMark 設定**（`config` で注入）
- `html_input` = `strip`（生 HTML を除去）
- `allow_unsafe_links` = `false`
- `max_nesting_level` を設定し、極端な入れ子を拒否する

---

## Http 層

### `App\Http\Controllers\PortfolioController`

```php
final readonly class PortfolioController
{
    public function __construct(
        private GetPortfolioContent $getPortfolioContent,
    ) {}

    public function __invoke(): Response;
    private function toProps(PortfolioContent $content): array;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `__invoke()` | `/` を処理し Inertia レスポンスを返す | — | `Inertia\Response` |
| `toProps()` | ドメインオブジェクトを props 配列に変換する | `PortfolioContent` | `array` |

**props の形**（Functional Design で確定）

```json
{
  "sections": [
    {
      "id": "stack",
      "title": "技術構成",
      "available": true,
      "blocks": [
        { "heading": "CloudFront", "html": "<p>...</p>" }
      ]
    }
  ]
}
```

### `App\Http\Middleware\SecurityHeaders`

```php
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response;
}
```

| メソッド | 目的 | 入力 | 出力 |
|---|---|---|---|
| `handle()` | レスポンスに NFR-S1 のヘッダを付与する | `Request`, `Closure` | `Response` |

**備考**: ヘッダ値と CSP の具体は NFR Design（UoW-1）で確定する。

---

## フロントエンド 主要インターフェース

### `Pages/Portfolio`

```ts
type SectionProps = {
  id: 'stack' | 'experience' | 'career' | 'next';
  title: string;
  available: boolean;
  blocks: { heading: string; html: string }[];
};

type PortfolioProps = { sections: SectionProps[] };
```

### `components/diagram/ArchitectureDiagram`

```ts
type DiagramNodeDef = {
  id: string;
  label: string;
  heading: string;      // content/stack.md の H2 見出しと一致させる（Q2 = A）
  x: number;            // 座標は直書き（Q7 = A）
  y: number;
  connectsTo: string[];
  kind: 'core' | 'extension';
};

type ArchitectureDiagramProps = {
  stackSection: SectionProps;
  onSelectNode: (heading: string) => void;
  selectedHeading: string | null;
};
```

**設計判断**: `DiagramNodeDef[]` はコンポーネントに隣接する定数として持つ（Q7 = A）。
サーバからは供給しない。`heading` が `stack.md` の H2 と一致しない場合、
`NodePanel` は「この要素の説明はまだありません」を表示する（画面を壊さない）。

### `hooks/usePrefersReducedMotion`

```ts
function usePrefersReducedMotion(): boolean;
```

`prefers-reduced-motion: reduce` のとき `true`。
`FlowParticles` はこの値が `true` なら粒を描画せず、静的な経路のみ表示する。
