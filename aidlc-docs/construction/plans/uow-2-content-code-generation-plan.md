# Code Generation Plan — UoW-2（コンテンツ基盤）

**このドキュメントが Code Generation の唯一の正典です。** 記載のないことは実行しません。

**対象 Bolt**: B-3（`content/*.md` の編集が画面に反映される）
**対応ストーリー**: US-7（コードを触らずにコンテンツを更新したい）

---

## 1. ユニットのコンテキスト

### 依存関係
- **前提**: UoW-1（完了・デプロイ済み）
- **このユニットに依存**: UoW-3（静的セクション）、UoW-4（構成図）— **両者は UoW-2 完了後に並行可能**

### 提供するインターフェース

| 提供物 | 利用者 |
|---|---|
| `sections` props（`id` / `title` / `available` / `lead` / `blocks[]`） | UoW-3・UoW-4 |
| `ContentBlock.key`（正規化済み見出し） | UoW-4 の構成図ノード照合 |
| `SectionProps` 型（確定版） | UoW-3・UoW-4 |

### 所有するデータ
**なし**（ADR-002）。読み取り専用。マイグレーションを作らない。

### 設計の出典
`aidlc-docs/construction/uow-2-content/functional-design/`
（domain-entities.md / business-rules.md / business-logic-model.md）

**規則番号（R-1〜R-7）とテスト番号（T-1〜T-10）は、そのまま実装とテストに対応させる。**

---

## 2. 実行ステップ

### Step 1: 依存の追加
- [ ] 1-1. `league/commonmark` を追加する
- [ ] 1-2. `composer audit` を実行し、脆弱性が無いことを確認する

### Step 2: Domain 層（フレームワーク非依存）
- [ ] 2-1. `app/Domain/Content/SectionId.php`（enum。ファイル名 / 既定タイトル / 表示順）
- [ ] 2-2. `app/Domain/Content/ContentBlock.php`（`heading` / `key` / `html`）
- [ ] 2-3. `app/Domain/Content/Section.php`（`loaded()` / `failed()` / `blockByKey()` / `hasBlocks()`）
- [ ] 2-4. `app/Domain/Content/PortfolioContent.php`（`sections()` / `section()` / `hasFailures()`）
- [ ] 2-5. `app/Domain/Content/ContentUnavailable.php`（理由の種別 4 つを含む）
- [ ] 2-6. `app/Domain/Content/ContentRepositoryInterface.php`（ポート）
- [ ] 2-7. `app/Domain/Content/MarkdownParserInterface.php`（ポート）
- [ ] 2-8. `app/Domain/Content/HeadingKey.php`（正規化規則 R-2 の実装。**フロントと対になる箇所**）
- [ ] 2-9. **`Illuminate\*` と `League\CommonMark\*` を import していないことを確認**

### Step 3: Application 層
- [ ] 3-1. `app/Application/Content/GetPortfolioContent.php`
      （全セクションを取得、`ContentUnavailable` を捕捉して `failed()` に差し替え、ログ記録）
- [ ] 3-2. 例外を外に伝播させないことを実装で担保する

### Step 4: Infrastructure 層
- [ ] 4-1. `app/Infrastructure/Content/CommonMarkParser.php`
      （R-1 の分割アルゴリズム、R-4 のセキュリティ設定）
- [ ] 4-2. `app/Infrastructure/Content/MarkdownContentRepository.php`
      （ファイル読み込み、R-3 の失敗判定）
- [ ] 4-3. `app/Infrastructure/Content/CachedContentRepository.php`
      （デコレータ。R-6 のキー規則、失敗はキャッシュしない）

### Step 5: 設定と DI
- [ ] 5-1. `config/content.php`（`path` / `cache.enabled`）
- [ ] 5-2. `app/Providers/AppServiceProvider.php` にインターフェースと実装の束ね付けを追加
- [ ] 5-3. テストからキャッシュ有無を切り替えられることを確認する

### Step 6: Http 層
- [ ] 6-1. `PortfolioController` に `GetPortfolioContent` を注入する
- [ ] 6-2. `toProps()` を実装する（ドメインオブジェクトを props 配列へ。business-logic-model.md §6 の形）
- [ ] 6-3. **ドメインオブジェクトをそのまま props に出さない**ことを確認する

### Step 7: フロントエンド（暫定表示）
- [ ] 7-1. `resources/js/pages/Portfolio.tsx` を更新し、
        `title` / `lead` / `blocks` を素の形で表示する
      （**体裁は整えない。UoW-3 で作り直す前提の確認用表示**）
- [ ] 7-2. `available = false` のセクションで固定文言を出す
- [ ] 7-3. 型チェックが通ることを確認する

### Step 8: テスト（T-1〜T-10）
- [ ] 8-1. T-1: `stack.md` から 6 ブロックが取れ、`lead` が空でない
- [ ] 8-2. **T-2: `experience.md` / `next.md` が `available = true`、`lead` に内容、`blocks` は空**
- [ ] 8-3. T-3: `title` が H1 から取れる。H1 が無ければ既定値
- [ ] 8-4. T-4: 本文 HTML に H1 が含まれない
- [ ] 8-5. T-5: 全角空白・大文字小文字の差を吸収してキーが一致する
- [ ] 8-6. T-6: ファイル不在で `available = false`、**他セクションは表示される**
- [ ] 8-7. T-7: 本文が空のファイルで `available = false`
- [ ] 8-8. T-8: Markdown 中の生 HTML（`<script>` を含む）が除去される
- [ ] 8-9. T-9: 失敗がキャッシュされない
- [ ] 8-10. T-10: `Domain/` が `Illuminate\*` / `League\CommonMark\*` を import していない
- [ ] 8-11. UoW-1 の `PortfolioPageTest`「sections が空である」を書き換える

### Step 9: 検証
- [ ] 9-1. `sail exec laravel.test ./vendor/bin/pest` が全て通る
- [ ] 9-2. `sail npm run typecheck` が通る
- [ ] 9-3. `sail npm run build` が通る
- [ ] 9-4. **ローカルで `content/*.md` を編集し、画面に反映されることを確認（B-3 の完了判定）**
- [ ] 9-5. `composer audit` / `npm audit` に脆弱性が無い

### Step 10: ドキュメント
- [ ] 10-1. `aidlc-docs/construction/uow-2-content/code/implementation-summary.md` を作成
- [ ] 10-2. 設計と実装の食い違いを記録
- [ ] 10-3. 必要なら ADR を追加・更新

### Step 11: 進捗
- [ ] 11-1. `aidlc-docs/aidlc-state.md` を更新
- [ ] 11-2. `aidlc-docs/audit.md` に記録

### Step 12: デプロイ（**任意 / 承認時に判断**）
- [ ] 12-1. 本番へ反映するかを確認する
- [ ] 12-2. デプロイする場合は README の手順に従う
      （audit → build → `composer install --no-dev` → `sail npm run deploy` → 開発依存を戻す）

---

## 3. 実行方針

### 私が実行すること
Step 1〜11。Docker は `sg docker -c` 経由（UoW-1 と同じ）。

### 中断のルール
- 設計（`functional-design/`）と実装が食い違った場合、**勝手に設計を曲げず報告する**
- 実行できない手順があれば、**できたふりをせず**報告する

### 意図的にやらないこと

| やらないこと | 理由 |
|---|---|
| セクションの体裁を整える | UoW-3 の担当。ここでは素の表示に留める |
| 構成図・ノード照合の実装 | UoW-4 の担当。`key` を props に載せるところまで |
| `MarkdownBlock` / `ContentUnavailable` コンポーネントの作成 | UoW-3 の担当 |
| データベース・マイグレーション | ADR-002 |

---

## 4. 完了条件

- [ ] Step 1〜11 の全チェックボックスが `[x]`
- [ ] テストが全て通る（T-1〜T-10 を含む）
- [ ] `content/*.md` の編集が画面に反映される（**B-3 の完了判定**）
- [ ] 型チェックとビルドが通る

---

## 5. 引き継ぎ中の未解決事項

- **P-2**: HTML が CloudFront にキャッシュされない（UoW-1 の V-5 未達）。**本ユニットの範囲外**
- **V-7**: アプリログの JSON 形式が本番で未確認。本ユニットで失敗系のログが出れば確認できる可能性がある
