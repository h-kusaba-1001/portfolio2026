# Tech Stack Decisions — UoW-1

技術選定と根拠。**大半は INCEPTION フェーズの ADR で確定済み**のため、
本書は ① 既存 ADR への参照と ② 本ステージで新たに確定した事項を記録する。

---

## 1. 既存 ADR で確定済みの選定

| レイヤ | 採用 | ADR | 本ステージでの再検討 |
|---|---|---|---|
| デプロイ CLI | osls | ADR-001 | なし |
| データストア | なし（Markdown） | ADR-002, ADR-003 | なし |
| ランタイム | Bref 3.0 + Lambda / PHP 8.4 | ADR-007 | なし |
| 配信 | Lift `server-side-website` | ADR-005 | なし |
| フロント | React + TypeScript + Tailwind | ADR-006 | なし |
| レンダリング | CSR（SSR なし） | ADR-008 | なし |
| テスト | Pest | ADR-009 | なし |
| ローカル環境 | Laravel Sail | ADR-007 | なし |

---

## 2. 本ステージで確定した事項

| # | 決定 | 選択肢 | 根拠 |
|---|---|---|---|
| 1 | **リージョン: `ap-northeast-1`** | 東京 / バージニア北部 / 大阪 | Q1 = A。閲覧者は国内想定。CloudFront が前段にあるため、リージョン間の単価差は無料枠内では効かない |
| 2 | **Lambda メモリ: 512 MB** | 512 / 1024 / 1792 MB | Q5 = A。無料枠（月 40 万 GB-秒）の消費を最小化する。実測で遅すぎる場合は引き上げる |
| 3 | **CSP: `script-src` 厳格、`style-src` のみ `'unsafe-inline'`** | 厳格維持 / style のみ緩和 / nonce | Q2 = B。ADR-011 |
| 4 | **セキュリティヘッダ: CloudFront Response Headers Policy** | ミドルウェア / CloudFront / 両方 | Q3 = B。静的アセットにも付与されるため |
| 5 | **オリジン保護: 共有シークレットヘッダ検証ミドルウェア** | ミドルウェア併用 / オリジン遮断 / 許容 | Q3-a = B。ADR-012 |
| 6 | **ログ: JSON 構造化（Monolog JsonFormatter）** | JSON / Laravel 既定 | Q4 = A。CloudWatch Logs Insights で検索可能にする |
| 7 | **デプロイ IAM: 専用ポリシー（サービス限定・リソースはワイルドカード）** | Administrator / 専用ポリシー / SSO | Q6 = B。ADR-012 |
| 8 | **依存スキャン: `composer audit` + `npm audit`、SBOM は CycloneDX** | ローカル / Dependabot / 両方 | Q7 = A |
| 9 | **濫用対策: WAF を使わず、キャッシュ + 同時実行上限 + 予算アラート** | WAF なし / WAF あり / キャッシュ強化 | Q8 = A + C。ADR-013 |

---

## 3. 新規に追加する依存パッケージ

### PHP（Composer）

| パッケージ | 用途 | 備考 |
|---|---|---|
| `laravel/framework` | フレームワーク | — |
| `inertiajs/inertia-laravel` | Inertia のサーバ側アダプタ | ADR-004 |
| `league/commonmark` | Markdown 変換 | UoW-2 で使用 |
| `bref/bref` | Lambda ランタイム | ADR-007 |
| `bref/laravel-bridge` | Laravel と Bref の接続 | — |
| `pestphp/pest`（dev） | テスト | ADR-009 |
| `pestphp/pest-plugin-laravel`（dev） | Laravel 統合 | — |
| `cyclonedx/cyclonedx-php-composer`（dev） | SBOM 生成 | NFR-S5 |

**除外**: `laravel/sanctum`、`laravel/tinker` 以外の認証・API 系パッケージは導入しない
（ADR-004、SECURITY-09「最小構成」）。

### JavaScript（npm）

| パッケージ | 用途 |
|---|---|
| `@inertiajs/react` | Inertia のクライアント |
| `react`, `react-dom` | UI |
| `typescript`, `@types/react`, `@types/react-dom` | 型（ADR-006） |
| `tailwindcss`, `@tailwindcss/vite` | スタイル |
| `vite`, `laravel-vite-plugin` | ビルド |
| `@cyclonedx/cyclonedx-npm`（dev） | SBOM 生成 |

**除外**: アニメーションライブラリ（framer-motion 等）は導入しない。
UoW-4 は SVG と CSS アニメーションで実装する（バンドルサイズと ADR-004 の判断基準）。

### インフラ（Serverless プラグイン）

| プラグイン | 用途 |
|---|---|
| `serverless-lift` | `server-side-website` 構造（ADR-005） |

---

## 4. バージョン固定方針（NFR-S5 / SECURITY-10）

| 対象 | 固定方法 |
|---|---|
| Composer 依存 | `composer.lock` をコミット |
| npm 依存 | `package-lock.json` をコミット |
| Sail の PHP イメージ | `laravelsail/php84-composer:8.4-x.y.z` 形式でタグを固定（`latest` を使わない） |
| Bref ランタイム | `php-84-fpm` を明示指定 |
| Node（Sail 内） | Sail のイメージに含まれるバージョンに従う |

**注意**: 現在の `README.md` のセットアップ手順は
`laravelsail/php84-composer:latest` を使用している。**Code Generation で固定タグに修正する。**

---

## 5. 却下した選択肢

| 却下したもの | 理由 |
|---|---|
| AWS WAF | 月 5〜8 USD の固定費が NFR-1 と衝突する（ADR-013） |
| プロビジョンド同時実行 | 固定費が発生する。コールドスタートの数値目標を持たないため不要 |
| Inertia SSR | ADR-008 |
| X-Ray / 監視ダッシュボード | 計測要件を削除済み。Resiliency 拡張は無効（ADR-010） |
| Dependabot | Q7 = A。ローカル/ビルド時のスキャンで足りると判断。後から追加可能 |
| RDS / DynamoDB | ADR-002 |
| 独自ドメイン + ACM 証明書 | NFR-5（独自ドメインを取得しない） |
