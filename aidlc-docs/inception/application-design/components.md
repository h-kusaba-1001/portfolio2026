# Components

**設計方針**: 軽量な 3 層 + ポート（Q5-a = B）。依存の向きは常に外側 → 内側。
Domain はフレームワークに依存しない。Infrastructure は Domain のインターフェースを実装する。

**ADR-004 との整合**: 層を増やすのは「依存の向きを制御する」目的に限る。
Presenter・InputPort・OutputPort など、現時点で 1 実装しかなく分離の必要がない要素は作らない。

---

## レイヤ構成

```
app/
  Domain/Content/          # フレームワーク非依存。ポートを所有する
  Application/Content/     # ユースケース。Domain のポートに依存する
  Infrastructure/Content/  # ポートの実装。CommonMark・ファイルシステム・キャッシュ
  Http/Controllers/        # 入力の受け口。Inertia への変換
resources/js/
  Pages/                   # Inertia のページ
  components/              # React コンポーネント
  hooks/
```

---

## Domain 層

### SectionId（値オブジェクト / enum）

**目的**: 掲載セクションの識別子。Markdown ファイル名との対応を 1 箇所に閉じ込める。

**責務**
- セクションの列挙（`STACK` / `EXPERIENCE` / `CAREER` / `NEXT`）
- 各セクションに対応する Markdown ファイル名の提供
- 表示順の提供

**備考**: Hero は Markdown を持たない（Q1 = B）ため列挙に含めない。
Contact は廃止（`docs/requirements.md` S-6）。

### Section（エンティティ相当 / readonly class）

**目的**: 1 セクションの内容を表す。読み込み成功と失敗の両方を表現できる。

**責務**
- セクション ID、見出し、本文ブロック列の保持
- 読み込み失敗状態の表現（Q6-a = A により、失敗はエラーではなく表示可能な状態として扱う）

**インターフェース**: 名前付きコンストラクタ `loaded()` / `failed()` の 2 経路のみ。
直接インスタンス化させない。

### ContentBlock（値オブジェクト / readonly class）

**目的**: H2 見出しとその配下の HTML を 1 組で保持する。

**責務**
- 見出しテキストの保持（構成図ノードとの対応キーになる。Q2 = A の規約対応の実体）
- 変換済み HTML の保持

**備考**: `content/stack.md` の H2 見出し（`CloudFront`、`API Gateway` など）が、
そのまま構成図ノードの参照キーになる。見出しを変更すると対応が切れるため、
この規約を `content/stack.md` の冒頭コメントに明記する。

### PortfolioContent（集約 / readonly class）

**目的**: 全セクションをまとめて 1 つのページに供給する単位。

**責務**
- Section の順序付きコレクションの保持
- セクション ID による取得

### ContentRepositoryInterface（ポート）

**目的**: セクション内容の取得口。Domain が所有し、Infrastructure が実装する。

**責務**
- `SectionId` を受け取り `Section` を返す契約の定義
- 取得できない場合に `ContentUnavailable` を投げる契約の定義

### MarkdownParserInterface（ポート）

**目的**: Markdown → HTML 変換の抽象。CommonMark への依存を Domain から切り離す。

**責務**
- Markdown 文字列を、H2 見出し単位のブロック列に変換する契約の定義

### ContentUnavailable（ドメイン例外）

**目的**: セクション内容が取得・変換できなかったことを表す。

**責務**
- 失敗した `SectionId` の保持
- 原因例外の連結（ログ出力用。画面には出さない: NFR-S6）

---

## Application 層

### GetPortfolioContent（ユースケース）

**目的**: 全セクションを取得し、ページ 1 枚分の内容を組み立てる。

**責務**
- `SectionId` の全ケースを順に取得する
- `ContentUnavailable` を捕捉し、`Section::failed()` に変換する（部分的劣化を許容: Q6-a = A）
- 失敗をログに記録する（NFR-S3）
- `PortfolioContent` を返す

**設計判断**: 失敗の捕捉を Repository ではなく本ユースケースに置く。
Repository は「取得できたか否か」だけを扱い、「失敗をどう見せるか」の方針は
Application 層の関心事とする。表示方針が変わってもアダプタを触らずに済む。

---

## Infrastructure 層

### MarkdownContentRepository（アダプタ）

**目的**: `content/*.md` を読み、`Section` を組み立てる。

**責務**
- `SectionId` からファイルパスを解決する
- ファイルの存在確認と読み込み
- `MarkdownParserInterface` に変換を委譲する
- 失敗時に `ContentUnavailable` を投げる

**実装**: `ContentRepositoryInterface`

### CachedContentRepository（デコレータ）

**目的**: パース結果をキャッシュする（Q3 = B）。

**責務**
- キャッシュにあればそれを返す
- 無ければ委譲先を呼び、結果をキャッシュに格納する
- キャッシュキーにファイルの更新時刻を含め、コンテンツ更新時に自動失効させる

**実装**: `ContentRepositoryInterface`（`MarkdownContentRepository` をラップ）

**設計判断**: キャッシュを Repository 本体に混ぜず、デコレータとして分離する。
キャッシュの有無で本体の責務が変わらず、テスト時は素の Repository を注入できる。

**注意（Infrastructure Design で詳細化）**: Lambda 上では書き込み可能なのは `/tmp` のみ。
キャッシュストアの出力先を `/tmp` に向ける設定が必要。

### CommonMarkParser（アダプタ）

**目的**: `league/commonmark` を使って Markdown を H2 単位のブロック列に変換する。

**責務**
- CommonMark の設定（生 HTML の除去、危険なリンクの拒否）
- H2 見出しによる分割
- 見出しテキストとブロック HTML の組の生成

**実装**: `MarkdownParserInterface`

**セキュリティ**: `html_input` を `strip`、`allow_unsafe_links` を `false` に設定する。
コンテンツは自前の Markdown のみだが、レンダリングに `dangerouslySetInnerHTML` を使うため、
変換元で生 HTML を落としておく（SECURITY-05 の考え方をコンテンツ経路に適用）。

---

## Http 層

### PortfolioController

**目的**: `/` へのリクエストを受け、Inertia のページを返す。

**責務**
- `GetPortfolioContent` の実行
- ドメインオブジェクトから Inertia props への変換
- ページコンポーネント名の指定

**設計判断**: 専用の Presenter クラスは作らない。変換対象が 1 ページのみで、
分離しても得るものがないため（ADR-004 の判断基準を適用）。
変換ロジックはコントローラ内の private メソッドに閉じる。

### SecurityHeaders（ミドルウェア）

**目的**: NFR-S1 のセキュリティヘッダを全 HTML レスポンスに付与する（ADR-015）。

**責務**
- `Content-Security-Policy` / `Strict-Transport-Security` / `X-Content-Type-Options` /
  `X-Frame-Options` / `Referrer-Policy` の付与

**ヘッダ値**: `aidlc-docs/construction/uow-1-foundation/nfr-requirements/nfr-requirements.md` §4。
CSP は `script-src 'self'` を厳格に保ち、`style-src` にのみ `'unsafe-inline'` を許可する（ADR-011）。

**設計の変遷**: 一度は CloudFront での付与 + オリジン遮断（`VerifyCloudFrontOrigin`）に
変更したが、ADR-015 で本方式に戻した。経緯は ADR-012 / ADR-015 を参照。

---

## フロントエンド（React）

粒度は Q8 = B（セクション + 共通要素の分離）。

### Pages

| コンポーネント | 目的 |
|---|---|
| `Pages/Portfolio` | ページ全体。props を受け取り各セクションに配る |

### 共通要素

| コンポーネント | 目的 |
|---|---|
| `components/layout/Section` | セクションの外枠。見出し、アンカー、スクロールフェードイン |
| `components/content/MarkdownBlock` | 変換済み HTML の描画 |
| `components/content/ContentUnavailable` | 読み込み失敗時のプレースホルダ（Q6-a = A） |
| `components/ui/GitHubLink` | Hero に置くリポジトリリンク |

### セクション

| コンポーネント | 目的 |
|---|---|
| `components/sections/Hero` | サイト名、キャッチ、GitHub リンク、S-2 への導線。**内容はコンポーネントに直接記述**（Q1 = B） |
| `components/sections/Stack` | 構成図 + ノード選定理由パネル。★最大の見せ場 |
| `components/sections/Experience` | やってきたこと |
| `components/sections/Career` | キャリアの変遷 |
| `components/sections/Next` | これから |

### 構成図（UoW-4）

| コンポーネント | 目的 |
|---|---|
| `components/diagram/ArchitectureDiagram` | SVG 本体。**ノード座標と接続をコンポーネント内に直書き**（Q7 = A） |
| `components/diagram/DiagramNode` | ノード 1 つの描画と選択状態 |
| `components/diagram/FlowParticles` | 光の粒がノード間を流れるアニメーション |
| `components/diagram/ExtensionPoints` | 拡張ポイントの破線表示（DynamoDB / SQS / Bref X-Ray / SSR） |
| `components/diagram/NodePanel` | 選択されたノードの選定理由を表示。`stack.md` の H2 ブロックを見出しで引く |

### Hooks

| フック | 目的 |
|---|---|
| `hooks/usePrefersReducedMotion` | `prefers-reduced-motion` の検出。アニメーションの停止判断に使う |

---

## フロントエンドの言語

**TypeScript**（2026-08-22 確定 / ADR-006）。

- React コンポーネントは `.tsx`、型定義・フックは `.ts`
- 共有型（`SectionProps`、`DiagramNodeDef`）は `resources/js/types/` に集約する
- サーバ側 `toProps()` の出力形と型定義を対応させ、片方だけの変更をビルドで検出する
