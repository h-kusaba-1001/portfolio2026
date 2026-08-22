# HK Portfolio

月額 100 円未満で動く、サーバレスなポートフォリオサイト。


## 何が動いているか

```
Browser → CloudFront → API Gateway → Lambda (Bref) → Laravel → Response
              ↓
             S3 (静的アセット)
```

データベースを持たない。コンテンツは `content/` 配下の Markdown を Laravel が読んで
Inertia の props として返している。

## 技術構成

| レイヤ | 採用技術 |
|---|---|
| デプロイ | [osls](https://github.com/oss-serverless/osls)（Serverless Framework v3 の OSS フォーク） |
| ランタイム | [Bref](https://bref.sh/) 3.0 + AWS Lambda |
| アプリ | Laravel + Inertia.js |
| フロント | React + TypeScript + Tailwind CSS |
| 配信 | Lift `server-side-website`（CloudFront + S3） |
| データ | Markdown（`league/commonmark`） |
| 開発環境 | Laravel Sail（Docker） |

選定理由は [docs/architecture-decisions.md](docs/architecture-decisions.md) を参照。

## 開発手法

AWS が提唱する **AI-DLC（AI-Driven Development Lifecycle）** に沿って開発している。
Inception フェーズの成果物は [docs/aidlc-inception.md](docs/aidlc-inception.md)。

個人開発のため、本来チームで行う「モブエラボレーション」「モブコンストラクション」は
AI との 1 対 1 の対話に置き換えている。

## セットアップ

ローカル開発は Laravel Sail（Docker）で行う。Docker が起動していれば、
ホストに PHP / Node を入れる必要はない。理由は ADR-007 を参照。

```bash
# 1. PHP 依存の解決（ホストに PHP / Composer が無くても実行できる）
#    イメージはダイジェストで固定する（NFR-S5: latest を使わない）
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -e COMPOSER_HOME=/tmp/composer \
  -v "$(pwd):/app" -w /app \
  laravelsail/php84-composer@sha256:a2716e93e577c80bca7551126056446c1e06cb141af652ee6932537158108400 \
  composer install

# 2. 環境設定
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/app" -w /app \
  laravelsail/php84-composer@sha256:a2716e93e577c80bca7551126056446c1e06cb141af652ee6932537158108400 \
  php artisan key:generate

# 3. 起動（初回はイメージのビルドに 10 分ほどかかる）
./vendor/bin/sail up -d
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

http://localhost で表示される。停止は `./vendor/bin/sail down`。

`alias sail='./vendor/bin/sail'` を張っておくと `sail artisan ...` で済む。

データベースを持たない（ADR-002）ため、Sail のサービスはアプリケーションコンテナのみ。

### テスト

```bash
./vendor/bin/sail exec laravel.test ./vendor/bin/pest
```

### 型チェック

```bash
./vendor/bin/sail npx tsc --noEmit
```

## デプロイ

デプロイ前に AWS の認証情報を用意する（IAM Identity Center の一時認証情報を想定）。

```bash
export APP_KEY=...              # .env の値をそのまま使う
export BUDGET_ALERT_EMAIL=...   # 予算アラートの通知先

# 依存の脆弱性チェック（NFR-S5）
./vendor/bin/sail exec laravel.test composer audit
./vendor/bin/sail exec laravel.test npm audit --omit=dev

# フロントエンドのビルド
./vendor/bin/sail npm run build

# 本番用に開発依存を除外してから固める
./vendor/bin/sail exec laravel.test composer install --no-dev --optimize-autoloader

npx osls deploy --stage prod

# 開発依存を戻す
./vendor/bin/sail exec laravel.test composer install
```

Serverless Framework v4 ではなく `osls` を使う。理由は ADR-001 を参照。

デプロイ内容の確認だけなら `npx osls print --stage prod`、
生成される CloudFormation テンプレートの確認は `npx osls package --stage prod`。

## ドキュメント

- [要件定義](docs/requirements.md)
- [アーキテクチャ決定記録（ADR）](docs/architecture-decisions.md)
- [AI-DLC / Inception 成果物](docs/aidlc-inception.md)

## コンテンツの更新

`content/` 配下の Markdown を編集してデプロイするだけ。コードを触る必要はない。

| ファイル | 対応セクション |
|---|---|
| `content/stack.md` | 技術構成 |
| `content/experience.md` | やってきたこと |
| `content/career.md` | キャリアの変遷 |
| `content/next.md` | これから |
