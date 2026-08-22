# Implementation Summary — UoW-1（基盤構築）

**実施日**: 2026-08-22
**状態**: **B-1（ローカル起動）完了。B-2（デプロイ）未実施**

---

## 1. 環境

| 項目 | 実際の値 |
|---|---|
| Laravel | **13.26.1**（`laravel/framework` 制約 `^13.17`） |
| PHP | 8.4.24（Sail コンテナ内） |
| Node | 24.19.0（Sail コンテナ内） |
| Vite | 8.2.2 |
| Tailwind CSS | 4.x（Laravel 13 スケルトンに同梱） |
| Inertia (Laravel) | 3.3 |
| Pest | 4.x |
| Bref | 3.0 |
| osls | 4.1.0 |

**Docker 実行の制約**: Claude Code のシェルは `docker` グループに属していなかったため、
ユーザーが `usermod -aG docker` を実行。再ログインなしで使えるよう
`sg docker -c '<コマンド>'` 経由で実行した。

---

## 2. 生成・変更したファイル

### 作成

| パス | 内容 | 対応 |
|---|---|---|
| `compose.yaml` | Sail 定義。**アプリケーションコンテナのみ** | ADR-002, ADR-007 |
| `config/security.php` | セキュリティヘッダの値 | NFR-S1, ADR-011 |
| `app/Http/Middleware/SecurityHeaders.php` | ヘッダ付与 + `X-Powered-By` 除去 | NFR-S1, ADR-015, SECURITY-04, 09 |
| `app/Http/Middleware/RequestId.php` | 相関 ID の採番 | U1-OB-2, SECURITY-03 |
| `app/Http/Middleware/HandleInertiaRequests.php` | Inertia のルートビュー指定 | — |
| `app/Http/Controllers/PortfolioController.php` | `/` の処理（雛形） | Q4 = A |
| `resources/views/app.blade.php` | OGP・meta の静的出力 | ADR-008 |
| `resources/js/app.tsx` | Inertia エントリポイント | ADR-006 |
| `resources/js/types/index.ts` | 共有型定義 | ADR-006 |
| `resources/js/pages/Portfolio.tsx` | トップページ（雛形） | — |
| `resources/js/pages/Error.tsx` | エラーページ | NFR-S6, LC-6 |
| `tsconfig.json` | TypeScript 設定 | ADR-006 |
| `vite.config.ts` | Vite 設定（React プラグイン、外部フォントなし） | — |
| `serverless.yml` | デプロイ定義 | Infrastructure Design |
| `tests/Pest.php` | Pest 設定 | ADR-009 |
| `tests/Feature/SecurityHeadersTest.php` | ヘッダ 5 件 + CSP + `X-Powered-By` | NFR-S1 |
| `tests/Feature/PortfolioPageTest.php` | トップページの描画 | — |
| `tests/Feature/ErrorPageTest.php` | 内部情報の非漏洩 | NFR-S6 |

### 変更

| パス | 内容 |
|---|---|
| `.env.example` | DB 前提の既定値を除去。`SESSION_DRIVER=array` / `QUEUE_CONNECTION=sync` / `CACHE_STORE=file` / `LOG_CHANNEL=stderr` |
| `bootstrap/app.php` | ミドルウェア登録、例外ハンドラ |
| `routes/web.php` | `/` を `PortfolioController` に |
| `resources/css/app.css` | **外部フォント（bunny.net）を除去**し、OS 標準フォントスタックに |
| `.gitignore` | `.serverless` 等を追加 |
| `README.md` | 実際に動いた手順に更新 |

### 削除

`resources/views/welcome.blade.php`（Laravel の既定ページ。SECURITY-09）、
`resources/js/app.js`、`vite.config.js`、`tests/**/ExampleTest.php`

---

## 3. 検証結果

| 項目 | 結果 |
|---|---|
| `pest` | **9 passed（48 assertions）** |
| `tsc --noEmit` | エラーなし |
| `npm run build` | 成功（JS 313.98 kB / gzip 99.10 kB、CSS 8.05 kB） |
| `composer audit` | 脆弱性なし |
| `npm audit --omit=dev` | 脆弱性 0 件 |
| HTTP レスポンス | 200。セキュリティヘッダ 5 件を実測で確認 |
| `osls print --stage prod` | 設定が完全に解決される |
| `osls package --stage prod` | CloudFormation テンプレート生成成功 |

---

## 4. 設計との差異（実装して初めて分かったこと）

| # | 設計上の記述 | 実際 | 対応 |
|---|---|---|---|
| Δ-1 | Inertia のページは `resources/js/Pages/` | **inertia-laravel v3 の既定は `resources/js/pages/`（小文字）** | 規約に合わせてディレクトリ名を変更。config の公開を避けた |
| Δ-2 | `provider.runtime: provided.al2` | **Bref 3 は `provided.al2023` のみ対応**。`provided.al2` だと osls が起動時に弾く | `serverless.yml` を修正 |
| Δ-3 | `APP_URL: https://${construct:website.domain}` | **Lift が公開する変数は `url` / `cname` / `assetsBucketName` の 3 つ**。`domain` は存在しない | `${construct:website.url}` に修正（スキーム込み） |
| Δ-4 | `VIEW_COMPILED_PATH` を明示設定する必要がある | **`bref/laravel-bridge` が自動設定する**（`useStoragePath` / `view.compiled` / `cache.stores.file.path` を `/tmp/storage` 配下へ） | 環境変数を削除。**D-2 の確認結果** |
| Δ-5 | 相関 ID は `LAMBDA_INVOCATION_CONTEXT` を JSON パースして取得 | **Bref は `X-Request-ID` ヘッダとして渡す**（ブリッジもこれを使っている） | ヘッダから取得する実装に変更。外部から送られうる値のため形式検証を追加 |
| Δ-6 | （記載なし） | **`X-Powered-By: PHP/8.4.24` が出ていた**（SECURITY-09 のバージョン非開示に反する） | ミドルウェアで除去し、テストを追加 |
| Δ-7 | （記載なし） | **Laravel 13 スケルトンが外部フォント（bunny.net）を読み込む設定を含む** | CSP `font-src 'self'` と SECURITY-13 に反するため除去 |

**Δ-2・Δ-3 は、デプロイして初めて分かる類の問題を `osls print` で事前に潰せた例。**
Δ-4・Δ-5 は、設計時に「実機で確認する」と保留した項目（D-2）の答え。

---

## 5. D-1〜D-5 の確認結果

| # | 内容 | 結果 |
|---|---|---|
| **D-1** | Lift の `extensions` が `DistributionConfig.Logging` をマージするか | **✅ マージされる。** 生成された `cloudformation-template-update-stack.json` で、Distribution の `Logging` に `Fn::GetAtt: [AccessLogsBucket, DomainName]` が入り、同一テンプレート内に `AccessLogsBucket` も存在することを確認。`osls package` 時に CDK が「参照先が存在しない」と警告を出すが、これは CDK が自身の synth 結果だけを検証しているための**誤検知**（`resources:` セクションはその後にマージされる） |
| **D-2** | `bref/laravel-bridge` の `/tmp` 自動処理の範囲 | **✅ 判明。** `BrefServiceProvider` が `useStoragePath('/tmp/storage')`、`view.compiled`、`cache.stores.file.path` を自動設定する。手動設定は `CACHE_STORE=file` のみで足りる |
| **D-3** | 本番デプロイ時の開発依存の除外手順 | **✅ 確定。** `composer install --no-dev --optimize-autoloader` を実行してから `osls deploy`。`serverless.yml` の `package.patterns` で `tests/` `docs/` `aidlc-docs/` `node_modules/` を除外し、`content/` は明示的に含める |
| **D-4** | デプロイ用 IAM ポリシーの最終内容 | **⏳ 未確定。** 実デプロイでしか詰められない。初回は権限不足を見ながら確定させる（Q6 = B の方針どおり） |
| **D-5** | 予算アラートの通知先メールアドレス | **⏳ 未確定。** `BUDGET_ALERT_EMAIL` として export が必要 |

---

## 6. 未解決の問題

### ⚠️ P-1: Set-Cookie により CloudFront の HTML キャッシュが効かない可能性

**実測**: トップページのレスポンスに `Set-Cookie` が 2 つ付いている。

```
Set-Cookie: XSRF-TOKEN=...
Set-Cookie: hk-portfolio-session=...
```

**問題**: U1-PF-4 は HTML を CloudFront で 60 秒キャッシュする設計（Q2 = B）。
一般に、`Set-Cookie` を含むレスポンスはキャッシュ対象から外れる。
このままでは **P-2（多層キャッシュ）と NFR-S9（濫用対策としてのキャッシュ）が機能しない。**

**原因**: Laravel の `web` ミドルウェアグループが、セッションと CSRF を既定で有効にしているため。
`SESSION_DRIVER=array`（永続化しない）にしていても、クッキー自体は発行される。

**本プロジェクトの状況**: 認証もフォームも POST も存在しないため、
セッションと CSRF は**機能上まったく必要ない**。

**対応案**（判断が必要）
- **案 A**: `web` グループからセッション・CSRF 関連のミドルウェアを外す
  → `Set-Cookie` が消え、HTML がキャッシュ可能になる。UoW-2 以降でフォームを追加する予定はない（ADR-004）
- **案 B**: CloudFront 側でクッキーを無視する設定にする
  → Lift の設定可能範囲を再確認する必要がある
- **案 C**: 現状のまま進め、B-2 のデプロイ後に実測して判断する

**この判断は本ステージの計画に含まれていないため、実装せずに報告する。**

---

## 7. 次のユニットへの引き渡し

| 引き渡すもの | 状態 |
|---|---|
| `PortfolioController` | 空の `sections` を返す雛形。UoW-2 で `GetPortfolioContent` を注入する |
| `resources/js/types/index.ts` | `SectionProps` / `ContentBlock` の形を定義済み。UoW-2 のサーバ実装と対応させる |
| `resources/js/pages/Portfolio.tsx` | 最小表示。UoW-3 / UoW-4 がセクションを追加する |
| `tests/Feature/PortfolioPageTest.php` | 「UoW-1 時点では sections が空」を固定するテストあり。**UoW-2 で書き換えが必要** |
