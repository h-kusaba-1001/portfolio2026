# Application Design（統合ドキュメント）

**対象**: portfolio2026（HK Portfolio）
**作成日**: 2026-08-22
**構成要素**: [components.md](components.md) / [component-methods.md](component-methods.md) /
[services.md](services.md) / [component-dependency.md](component-dependency.md)

---

## 1. 設計の要旨

このアプリケーションは **「Markdown を読んで 1 枚のページとして返す」以外のことをしない**。
データベース・認証・API・外部連携・状態遷移が存在しない。

したがって設計の焦点は「複雑さをどう捌くか」ではなく、
**少ない要素をどう配置すれば、ADR の主張とコードが一致して見えるか**にある。

| 決定 | 内容 | 根拠 |
|---|---|---|
| レイヤ構成 | Domain / Application / Infrastructure / Http の 4 ディレクトリ | Q5-a = B |
| 層を増やす目的 | 依存の向きの制御に限定。Presenter・InputPort・OutputPort は作らない | ADR-004 |
| ユースケース | `GetPortfolioContent` 1 つのみ | サーバ側に業務ロジックが無い |
| ノード対応付け | `stack.md` の H2 見出しテキストによる規約 | Q2 = A |
| キャッシュ | Repository のデコレータとして分離 | Q3 = B |
| ルーティング | `/` のみ | Q4 = A |
| 失敗時の挙動 | セクション枠は残し、本文位置にメッセージ。ページ全体は表示 | Q6 = B, Q6-a = A |
| 構成図 SVG | ノード座標をコンポーネントに直書き | Q7 = A |
| React 粒度 | セクション + 共通要素を分離 | Q8 = B |
| Hero の内容 | コンポーネントに直接記述（Markdown 化しない） | Q1 = B |

---

## 2. コンポーネント一覧（要約）

詳細は [components.md](components.md)。

### サーバ側（PHP 8.4）

```
app/
  Domain/Content/
    SectionId.php                    enum。セクションとファイル名の対応
    Section.php                      loaded / failed の 2 状態を持つ
    ContentBlock.php                 H2 見出し + HTML
    PortfolioContent.php             Section の集約
    ContentRepositoryInterface.php   ポート
    MarkdownParserInterface.php      ポート
    ContentUnavailable.php           ドメイン例外
  Application/Content/
    GetPortfolioContent.php          唯一のユースケース
  Infrastructure/Content/
    MarkdownContentRepository.php    ファイル読み込み
    CachedContentRepository.php      キャッシュデコレータ
    CommonMarkParser.php             Markdown 変換
  Http/
    Controllers/PortfolioController.php
    Middleware/SecurityHeaders.php
config/
  content.php
```

**クラス数 12**。うち Domain が 7、実装が 3、入り口が 2。

### フロントエンド（React）

```
resources/js/
  Pages/Portfolio
  components/
    layout/Section
    content/MarkdownBlock
    content/ContentUnavailable
    ui/GitHubLink
    sections/Hero, Stack, Experience, Career, Next
    diagram/ArchitectureDiagram, DiagramNode, FlowParticles,
            ExtensionPoints, NodePanel
  hooks/usePrefersReducedMotion
```

---

## 3. 主要インターフェース（要約）

詳細は [component-methods.md](component-methods.md)。

```php
interface ContentRepositoryInterface {
    /** @throws ContentUnavailable */
    public function find(SectionId $id): Section;
}

interface MarkdownParserInterface {
    /** @return list<ContentBlock> */
    public function toBlocks(string $markdown): array;
}

final readonly class GetPortfolioContent {
    public function __invoke(): PortfolioContent;   // 例外を外に出さない
}
```

**契約の要点**: Repository は失敗を隠さず例外を投げる。
「失敗をどう見せるか」を決めるのは Application 層。この分離により、
表示方針（Q6-a）を変更してもアダプタを触らずに済む。

---

## 4. データフローと依存の向き（要約）

詳細は [component-dependency.md](component-dependency.md)。

```
content/*.md
  -> MarkdownContentRepository  （ファイルは知るが Markdown 文法は知らない）
  -> CommonMarkParser           （Markdown は知るがファイルは知らない）
  -> Section / ContentBlock     （Domain。Laravel も CommonMark も知らない）
  -> CachedContentRepository    （キャッシュのみを足す）
  -> GetPortfolioContent        （失敗を表示可能な状態に変換）
  -> Inertia props
  -> Pages/Portfolio -> ArchitectureDiagram -> NodePanel
```

**不変条件**: `Domain/` から `Illuminate\*` と `League\CommonMark\*` を import しない。
Build and Test ステージで静的に検証する。

---

## 5. 設計上の弱点（明示）

| # | 弱点 | 影響 | 対応 |
|---|---|---|---|
| 1 | **H2 見出しの文字列一致に依存**（Q2 = A） | `stack.md` の見出しを変えると構成図パネルが空になる。実行時まで気付けない | ① `stack.md` 冒頭に規約コメント ② 全ノードの `heading` が実在することを検証する Feature テスト ③ 不一致時は「説明はまだありません」を表示し画面は壊さない |
| 2 | ノード座標の直書き（Q7 = A） | 拡張ポイント追加時に手作業 | 見せ場の作り込み優先という判断。ADR-004 の「拡張は関数を足すだけ」という主張とは別レイヤの話 |
| 3 | キャッシュストアの配置 | Lambda では `/tmp` のみ書き込み可能。設定を誤ると本番で例外 | Infrastructure Design（UoW-1）で確定する |
| 4 | Hero が Markdown 化対象外（Q1 = B） | US-7「コードを触らずに更新」の適用範囲が S-2〜S-5 に限定される | `docs/requirements.md` §5 に明記済み |

**弱点 1 が最も重い。** Q2 = A は Markdown 側に追加記述が要らない代わりに、
壊れたことを検出する手段を実装側で用意する責任を負う。
Functional Design（UoW-2）と Code Generation（UoW-4）で検証テストを必ず含める。

---

## 6. Security Compliance（Application Design ステージ）

| Rule | 判定 | 根拠 |
|---|---|---|
| SECURITY-01 暗号化 | **N/A** | 本ステージの成果物はアプリケーション構造。ストレージ設定は Infrastructure Design で扱う |
| SECURITY-02 中間層アクセスログ | **N/A** | 同上（インフラ層の関心事） |
| SECURITY-03 アプリケーションログ | **準拠** | `GetPortfolioContent` に `LoggerInterface` を注入し、失敗を構造化ログに記録する設計。ログにファイルパス等の内部情報を含めない方針を明記 |
| SECURITY-04 セキュリティヘッダ | **準拠** | `SecurityHeaders` ミドルウェアをコンポーネントとして定義。ヘッダ値の具体は NFR Design で確定 |
| SECURITY-05 入力検証 | **準拠（適用範囲を限定）** | 受け付ける入力パラメータは無い（`/` のみ、クエリ・ボディなし）。一方でコンテンツ経路に同等の考え方を適用し、CommonMark を `html_input = strip` / `allow_unsafe_links = false` / `max_nesting_level` 設定で構成する |
| SECURITY-06 最小権限 | **N/A** | IAM は Infrastructure Design で扱う |
| SECURITY-07 ネットワーク | **N/A** | VPC 不使用 |
| SECURITY-08 アクセス制御 | **N/A** | 全ページが公開コンテンツ。認可対象のリソースが存在しない |
| SECURITY-09 ハードニング | **準拠** | `ContentUnavailable` のメッセージに絶対パスを含めない。画面表示は固定文言のみ |
| SECURITY-10 サプライチェーン | **N/A** | 依存管理は Code Generation / Build and Test で扱う |
| SECURITY-11 セキュア設計 | **準拠** | ① 責務分離: 変換・読み込み・表示方針を別クラスに分離 ② 多層防御: CommonMark の `strip` に加え、`dangerouslySetInnerHTML` に渡すのは自前コンテンツのみに限定 ③ 濫用ケース: 「Markdown が壊れている / 見出しが一致しない / ファイルが無い」の 3 つを設計で扱い、いずれも画面を壊さない ④ レート制限は公開 API が無いため Infrastructure Design で CloudFront 側の判断とする |
| SECURITY-12 認証 | **N/A** | ユーザー認証が存在しない |
| SECURITY-13 完全性検証 | **準拠** | 外部 CDN からスクリプトを読まない（アセットは自前バンドル、SRI 不要）。非信頼データのデシリアライズなし |
| SECURITY-14 アラート・監視 | **N/A** | ログ保持・アラートは Infrastructure Design で扱う |
| SECURITY-15 例外処理 | **準拠** | ① ファイル I/O の失敗を `ContentUnavailable` で明示的に処理 ② フェイルクローズ: 取得できなければ本文を表示せず、固定文言に置き換える（内容を推測して補完しない） ③ 利用者向けメッセージは固定文言のみ ④ グローバルエラーハンドラの設定は Code Generation（UoW-1）で扱う |

**ブロッキング所見: なし。**

---

## 7. フロントエンドの言語: TypeScript（確定）

2026-08-22 に TypeScript で確定。ADR-006 に追記済み。

**この決定が効く範囲**
- `SectionProps` と `DiagramNodeDef` の型定義により、props の形の不一致がビルド時に落ちる
- サーバ側の `toProps()` の出力形を変更した場合、フロント側の型と合わなければコンパイルエラーになる

**この決定では防げないこと（§5 弱点 1）**
- `DiagramNodeDef.heading` の文字列が `content/stack.md` の H2 見出しと一致するかは、
  型では検証できない。**Feature テストによる検証が引き続き必須**

**ファイル拡張子の方針**
- React コンポーネント: `.tsx`
- 型定義・フック: `.ts`
- 型定義の置き場所: `resources/js/types/` に集約する
