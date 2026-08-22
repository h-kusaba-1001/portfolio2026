# Implementation Summary — UoW-2（コンテンツ基盤）

**実施日**: 2026-08-22
**状態**: **完了。本番反映済み** — https://d3bttkxchvfb66.cloudfront.net

---

## 1. 生成したファイル

### Domain 層（10 ファイル / フレームワーク非依存）

| パス | 内容 |
|---|---|
| `SectionId.php` | enum。ファイル名・既定タイトル・表示順 |
| `HeadingKey.php` | 正規化規則 R-2。**TypeScript 側と対になる箇所** |
| `ContentBlock.php` | `heading`（表示）+ `key`（照合）+ `html` |
| `Section.php` | `loaded()` / `failed()` の 2 経路のみ |
| `PortfolioContent.php` | 集約。要素数は常に 4 |
| `ParsedMarkdown.php` | パーサの出力（`title` / `lead` / `blocks`） |
| `ContentUnavailable.php` | ドメイン例外 |
| `ContentUnavailableReason.php` | 失敗理由 4 種 |
| `ContentRepositoryInterface.php` | ポート |
| `MarkdownParserInterface.php` | ポート |

### Application 層

`GetPortfolioContent.php` — 唯一のユースケース。失敗を捕捉して `failed()` に差し替え、
例外を外に出さない。

### Infrastructure 層

| パス | 内容 |
|---|---|
| `CommonMarkParser.php` | R-1 の分割（行単位の走査）。**コードフェンス内の `##` を見出しと誤認しない** |
| `MarkdownContentRepository.php` | ファイル読み込みと R-3 の失敗判定 |
| `CachedContentRepository.php` | デコレータ。キーに更新時刻を含める。失敗はキャッシュしない |

### その他

`config/content.php`、`AppServiceProvider`（DI 3 件）、`PortfolioController`（`toProps()`）、
`resources/js/pages/Portfolio.tsx`（**暫定表示**）、`bin/deploy.sh`（新規）

### テスト

| ファイル | 内容 |
|---|---|
| `tests/Feature/ContentPipelineTest.php` | T-1〜T-9 |
| `tests/Unit/DomainIsolationTest.php` | T-10（Domain 層の依存検証） |
| `tests/Feature/PortfolioPageTest.php` | 書き換え（UoW-1 の「sections が空」を廃止） |

---

## 2. 検証結果

| 項目 | 結果 |
|---|---|
| テスト | **23 passed（120 assertions）** |
| `tsc --noEmit` | エラーなし |
| `npm run build` | 成功（JS 314.62 kB / CSS 33.55 kB） |
| `composer audit` / `npm audit --omit=dev` | 脆弱性なし |
| **B-3 の完了判定** | `content/next.md` を編集 → 画面に反映 → 復元を確認。**合格** |
| 本番 | 200 / 4 セクション全て `available: true` / セキュリティヘッダ 5 件維持 |

**実データでの分割結果**（T-1 で固定）

```
stack.md  -> lead あり + 6 ブロック
             keys: cloudfront / api gateway / lambda (bref)
                   / laravel + inertia.js / デプロイ: osls / 拡張ポイント
career.md -> lead あり + 4 ブロック
experience.md / next.md -> lead のみ、blocks は空（available = true）
```

---

## 3. 実装中に見つかった問題

### Δ-8: `config/content.php` で `app()` を呼べない

設定ファイルの読み込み時点ではコンテナが未準備で、
`app()->environment('local')` が `Target class [env] does not exist` で落ちた。
**22 テストが一斉に失敗**して発覚。`env('APP_ENV') !== 'local'` に変更。

### Δ-9: root 所有のファイルで `sail` コマンドが軒並み失敗する

私が `docker compose exec`（= root）で `composer` と `npm` を実行していたため、
`vendor/` と `node_modules/` が **root 所有**になっていた。
`sail` の各コマンドは `sail` ユーザー（uid 1000）で動くため、
`sail npm run build` が `EACCES` で失敗し、`sail composer require` も書き込みで失敗していた。

**50,246 ファイル**が root 所有になっていた。`chown -R sail:sail` で修正し、
`sail composer` / `sail npm` / `sail exec` の全てが通ることを確認。

**教訓**: コンテナ内でファイルを作る操作は `-u sail` で行うこと。

### Δ-10: `composer install --no-dev` が `sail` 自身を消す

**README に書いたデプロイ手順が、そのままでは必ず失敗する**ことが判明。

```
sail composer install --no-dev   # ← ここで laravel/sail が vendor から消える
sail npm run deploy              # ← sh: ./vendor/bin/sail: not found
```

さらに、途中で失敗すると**開発依存が欠けたままの作業ツリーが残る**。

**対応**: `bin/deploy.sh` を作成。`docker compose` を直接使い、
`trap ... EXIT` で**どこで失敗しても開発依存を戻す**。README も書き換えた。

---

## 4. 設計どおりに実装した箇所（確認）

| 規則 | 実装 | テスト |
|---|---|---|
| R-1 分割 | `CommonMarkParser::parse()` | T-1, T-2, T-4 |
| R-2 正規化 | `HeadingKey::from()` | T-5 |
| R-3 失敗判定 | `MarkdownContentRepository::find()` | T-6, T-7 |
| R-4 HTML 変換の安全性 | `AppServiceProvider` の CommonMark 設定 | T-8 |
| R-5 順序 | `SectionId::inDisplayOrder()` | `PortfolioPageTest` |
| R-6 キャッシュ | `CachedContentRepository` | T-9 |
| R-7 未知ファイル | `SectionId` から直接パスを組み立て、列挙しない | — |

**設計の規則番号がそのままコードのコメントとテスト名に入っている。**
「この分岐は何の要件だったか」を後から追える。

---

## 5. 追加で入れた防御（設計に無かったもの）

**コードフェンスの考慮**: `CommonMarkParser` は ``` で囲まれた中の
`## 見出しに見える行` を見出しとして扱わない。
現在の `content/*.md` にコードフェンスは無いが、
技術構成の説明にコード例を足す可能性は高く、そのとき静かに壊れる類の問題のため。

---

## 6. 未解決の問題（引き継ぎ）

| # | 内容 | 状態 |
|---|---|---|
| **P-2** | HTML が CloudFront にキャッシュされない（UoW-1 の V-5 未達）。Lift が `CachingDisabled` を適用しているため | **未解決・判断待ち** |
| **V-7** | アプリログの JSON 形式が本番で未確認 | 未確認（正常系ではログが出ない） |

---

## 7. 次のユニットへの引き渡し

| 引き渡すもの | 状態 |
|---|---|
| `sections` props | `id` / `title` / `available` / `lead` / `blocks[{heading, key, html}]` |
| `SectionProps` 型 | 確定。`resources/js/types/index.ts` |
| `resources/js/pages/Portfolio.tsx` | **暫定表示。UoW-3 で作り直す前提** |
| `HeadingKey` の正規化規則 | **UoW-4 で TypeScript 側に同じ規則が必要**。緩和策 3 点は business-rules.md R-2 |
| `stack.md` の 6 キー | `cloudfront` / `api gateway` / `lambda (bref)` / `laravel + inertia.js` / `デプロイ: osls` / `拡張ポイント` |
