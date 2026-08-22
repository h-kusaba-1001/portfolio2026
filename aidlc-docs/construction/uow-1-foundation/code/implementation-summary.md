# Implementation Summary — UoW-1（基盤構築）

**実施日**: 2026-08-22
**状態**: **B-1（ローカル起動）完了 / B-2（デプロイ）完了。公開 URL: https://d3bttkxchvfb66.cloudfront.net**

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

---

## 8. デプロイ結果（Bolt B-2 / 2026-08-22）

**成功。** スタック `hk-portfolio-prod`（`ap-northeast-1`）。

| 項目 | 値 |
|---|---|
| 公開 URL | https://d3bttkxchvfb66.cloudfront.net |
| CloudFront Distribution | `E1A7E0WUMFC77G` |
| API Gateway（オリジン） | https://75j6557rjj.execute-api.ap-northeast-1.amazonaws.com |
| Lambda | `hk-portfolio-prod-web`（16 MB パッケージ / 512 MB / 実測 Max Memory 138 MB / 実行 18.8 ms） |
| S3 | `...-websiteassets...`（アセット）、`...-accesslogsbucket-...`（ログ）、`...-serverlessdeploymentbucket-...` |

### デプロイに至るまでの失敗（2 回）

| # | 失敗内容 | 原因 | 対応 |
|---|---|---|---|
| 1 | `cloudformation:DescribeStacks` で AccessDenied（14 秒で停止、リソース作成なし） | 権限セット `portfolioDeploy` に IAM 補助ポリシーだけが入っており、CloudFormation・Lambda・S3 などの土台権限が無かった | `docs/deploy-iam-policy.json` を単体で成立する完全版に書き直し |
| 2 | `CREATE_FAILED: WebLambdaFunction` — `ReservedConcurrentExecutions ... below its minimum value of [10]` | **アカウントの Lambda 同時実行上限が 10**（新規アカウントの初期値）。10 を予約すると未予約分が 0 になり拒否される | 予約をやめ、アカウント上限を天井として使う（**ADR-016**） |

### 検証結果（V-1〜V-10）

| # | 項目 | 結果 |
|---|---|---|
| V-1 | 公開 URL にアクセスできる | ✅ 200 |
| V-2 | セキュリティヘッダ 5 件 | ✅ 全て付与。`X-Powered-By` も出ていない |
| V-3 | HTTP → HTTPS リダイレクト | ✅ 301 |
| V-4 | 静的アセットが S3 から配信される | ✅ 配信を確認（`/build/*` は CachingOptimized ポリシー） |
| V-5 | HTML が 60 秒キャッシュされる | ❌ **未達**（下記 P-2 参照） |
| V-6 | S3 が直接アクセスできない | ✅ 両バケットともパブリックアクセスを全ブロック |
| V-7 | アプリログが JSON で出る | ⚠️ **未確認**。正常系ではアプリログが出ないため実物を確認できていない |
| V-8 | ロググループの保持が 14 日 | ✅ `/aws/lambda/hk-portfolio-prod-web` = 14 日 |
| V-9 | CloudFront アクセスログが S3 に出る | ✅ 設定を確認（配信まで最大 1 時間かかるため、ファイルの実物は未確認） |
| V-10 | エラーページに内部情報が出ない | ✅ 404。内部パス・スタックトレースの出現 0 |

### D-1 の本番確認

CloudFront の設定を実機で確認したところ、`extensions` によるログ設定が反映されていた。

```json
{ "Enabled": true, "IncludeCookies": false,
  "Bucket": "hk-portfolio-prod-accesslogsbucket-0yaahbibia5t.s3.amazonaws.com",
  "Prefix": "cloudfront/" }
```

`osls package` 時に CDK が出す「参照先が存在しない」警告は、やはり**誤検知**だった。

---

## 9. 未解決の問題（更新）

### ⚠️ P-2: HTML が CloudFront にキャッシュされない（V-5 未達）

**実測**

```
cache-control: no-cache, private
x-cache: Miss from cloudfront   （連続アクセスでも常に Miss）
```

**原因**: Lift が Lambda 側のビヘイビアに **AWS 管理ポリシー `CachingDisabled`**
（`4135ea2d-6df8-44a3-9df3-4b5a84be39ad`）を適用している。
このポリシーはオリジンの `Cache-Control` を一切参照せず、常にキャッシュしない。

```
DefaultCacheBehavior  -> CachePolicyId: CachingDisabled     ← HTML
/build/*              -> CachePolicyId: CachingOptimized    ← 静的アセット
```

**影響**: U1-PF-4（HTML を 60 秒キャッシュ）と、NFR-S9 の濫用対策のうち
「キャッシュで Lambda 到達を減らす」部分（Q8 = C）が機能していない。

**補足**: アプリ側で `Cache-Control` を返すだけでは解決しない。
`CachingDisabled` はオリジンのヘッダを見ないため、**キャッシュポリシーの差し替えが必須**。

**対応案**（判断が必要）
- **案 A**: `extensions.distribution` で `DefaultCacheBehavior.CachePolicyId` を
  `CachingOptimized`（`658327ea-f89d-4fab-a63d-7e88639e58f6`）に差し替え、
  併せてアプリ側で `Cache-Control: public, max-age=60` を返す。
  D-1 と同じ手法で、実績のあるやり方
- **案 B**: 独自のキャッシュポリシーを作成し、TTL を明示的に 60 秒に固定する
- **案 C**: U1-PF-4 を取り下げる。**アカウントの同時実行上限 10（ADR-016）が
  費用の天井として働いているため、濫用対策としては最低限成立している**

### ⚠️ V-7: アプリログの形式が未確認

正常系ではアプリケーションログが出力されないため、JSON 形式で出ているかを本番で確認できていない。
設定（`LOG_STDERR_FORMATTER`）は投入済み。
UoW-2 以降でエラーが発生した際、または意図的に確認する機会に検証する。

なお、ローカルの `.env` に同じ設定が無く、**ローカルだけ行ベースのログ形式**になっていたため、
`.env.example` と `.env` に `LOG_STDERR_FORMATTER` を追加して本番と揃えた（P-6 の趣旨）。

---

## 10. デプロイ用 IAM 権限の内訳（初回のみ必要なもの）

`docs/deploy-iam-policy.json` の内容を、**初回デプロイでのみ必要なもの**と
**継続的に必要なもの**に分けた整理。

### 初回デプロイでのみ使われたもの

| アクション | 用途 |
|---|---|
| `cloudformation:CreateStack` | スタックの新規作成 |
| `iam:CreateRole` / `iam:PutRolePolicy` / `iam:TagRole` | Lambda 実行ロールの作成 |
| `s3:CreateBucket` / `s3:PutBucketPolicy` / `s3:PutBucketOwnershipControls` / `s3:PutBucketPublicAccessBlock` / `s3:PutEncryptionConfiguration` / `s3:PutLifecycleConfiguration` | 3 つの S3 バケットの作成と設定 |
| `cloudfront:CreateDistribution` / `cloudfront:CreateOriginAccessControl` / `cloudfront:CreateCachePolicy` | ディストリビューションの作成 |
| `apigateway:POST` | HTTP API の作成 |
| `budgets:CreateBudget` | 予算の作成 |
| `lambda:CreateFunction` | 関数の作成 |

### 2 回目以降も必要なもの

| アクション | 用途 |
|---|---|
| `cloudformation:UpdateStack` / `DescribeStacks` / `DescribeStackEvents` / `DescribeStackResource*` / `GetTemplate` / `ValidateTemplate` | スタックの更新と状態確認 |
| `lambda:UpdateFunctionCode` / `UpdateFunctionConfiguration` / `GetFunction` / `PublishVersion` | 関数の更新 |
| `s3:PutObject` / `GetObject` / `ListBucket` / `DeleteObject` | デプロイパッケージとアセットのアップロード・入れ替え |
| `cloudfront:GetDistribution*` / `UpdateDistribution` / `CreateInvalidation` | ディストリビューションの更新 |
| `iam:GetRole` / `iam:PassRole` | ロールの参照と Lambda への引き渡し |
| `apigateway:GET` / `PATCH` / `PUT` | API の更新 |
| `logs:*` | ロググループの作成・保持設定 |

### 削除時にのみ必要なもの（`osls remove`）

`cloudformation:DeleteStack`、`iam:DeleteRole` / `DeleteRolePolicy`、
`lambda:DeleteFunction`、`s3:DeleteBucket`、`cloudfront:DeleteDistribution`、
`budgets:DeleteBudget`

### 絞り込むときの注意

**安易に `Create*` を削らないこと。** CloudFormation は変更内容によって
リソースを「置換（削除して再作成）」することがある。
たとえば Lambda 関数名やバケット名に影響する変更を入れると、
2 回目以降のデプロイでも `Create*` と `Delete*` の両方が必要になる。

**推奨**: 現行のポリシーを維持する。どうしても絞るなら、
`osls remove` を使う予定が無い場合に **削除系のみ**を外すのが安全。

---

## 11. デプロイ後の追加対応（ユーザー要望）

### 11-1. Laravel Boost の導入

`laravel/boost` 2.5 を dev 依存として追加し、`php artisan boost:install` を実行。

| 生成物 | 内容 |
|---|---|
| `CLAUDE.md` の `<laravel-boost-guidelines>` ブロック | Laravel 13 / PHP 8.4 に合わせたガイドライン（218 行の追記） |
| `.claude/skills/` | 6 スキル（inertia-react-development / laravel-best-practices / pest-testing / tailwindcss-development / octane-development / infer-conventions） |
| `.mcp.json` | Boost の MCP サーバ設定 |
| `boost.json` | Boost の設定 |

**確認したこと**: `CLAUDE.md` 冒頭の **AI-DLC ワークフローは上書きされていない**。
Boost の追記はタグで囲まれた末尾への追加のみで、`boost:update` で
そのブロックだけが差し替えられる構造になっている。

### 11-2. Sail の Node バージョン固定

Sail のランタイム Dockerfile は `ARG NODE_VERSION=24` を既定値として持つ。
Sail 側の更新で既定が変わると環境が揺れるため、`compose.yaml` の build args で
**明示的に `NODE_VERSION: '24'` を指定**した（NFR-S5 / SECURITY-10）。

なおホストの Node は v20.12.2 で、**osls 4（`^20.19.0 || ^22.13.0 || >=24`）と
Vite 8 の要求を満たさない**。npm 系のコマンドは必ずコンテナ内で実行する。

### 11-3. Sail からビルド・デプロイできるようにした

**変更点**

1. `compose.yaml` に環境変数の受け渡しを追加
   （`AWS_PROFILE` / `AWS_REGION` / `BUDGET_ALERT_EMAIL` / `APP_KEY`）。
   Docker Compose が同ディレクトリの `.env` を読むため、値は `.env` に置く
2. `${HOME}/.aws` を `/home/sail/.aws` に**読み取り専用**でマウント
3. `package.json` に npm スクリプトを追加
   （`typecheck` / `deploy` / `deploy:info` / `deploy:package` / `deploy:remove`）

**検証**: `sail npx osls info --stage prod` が SSO の認証情報を使って
本番スタックの情報を取得できることを確認した。

**セキュリティ上の判断**: 認証情報をコンテナに見せることになるが、
① 読み取り専用 ② 長期のアクセスキーではなく SSO の一時トークン
③ 個人の開発機である、の 3 点から許容した（SECURITY-12）。
`.env` に置くのも同じ理由で、**シークレットそのものは含まれない**
（`AWS_PROFILE` はプロファイル名、`BUDGET_ALERT_EMAIL` はメールアドレス）。
`.env` は `.gitignore` 済み。

### 11-4. ローカルのログ形式を本番に合わせた

`LOG_STDERR_FORMATTER` が `serverless.yml` にしか無く、
**ローカルだけ行ベース**のログになっていた。`.env` / `.env.example` に追加して揃えた（P-6）。

### 11-5. 回帰確認

| 項目 | 結果 |
|---|---|
| `sail exec laravel.test ./vendor/bin/pest` | 11 passed（52 assertions） |
| `sail npm run typecheck` | エラーなし |
| 本番 URL | 200 |
| ローカル | 200 |
