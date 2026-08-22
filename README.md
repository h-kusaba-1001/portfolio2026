# HK Portfolio

月額 100 円未満で動く、サーバレスなポートフォリオサイト。

**公開 URL**: https://d3bttkxchvfb66.cloudfront.net


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

### 日常的に使うコマンド

すべて Sail 経由（コンテナ内）で実行する。`alias sail='./vendor/bin/sail'` を張っておくと短い。

| 目的 | コマンド |
|---|---|
| 起動 / 停止 | `sail up -d` / `sail down` |
| フロントの開発サーバ | `sail npm run dev` |
| ビルド | `sail npm run build` |
| テスト | `sail exec laravel.test ./vendor/bin/pest` |
| 型チェック | `sail npm run typecheck` |
| 依存の脆弱性チェック | `sail composer audit` / `sail npm audit --omit=dev` |
| Artisan | `sail artisan <command>` |

**ホストの Node は使わないこと。** osls 4 と Vite 8 は Node 20.19 以上を要求するため、
ホストのバージョンによっては動かない。Sail のコンテナは Node 24 に固定してある。

## デプロイ

### 前提（初回のみ）

1. **AWS の認証情報**（IAM Identity Center / SSO）

   ```bash
   aws configure sso --profile portfolio
   aws sso login --profile portfolio
   ```

   `~/.aws` はコンテナに読み取り専用でマウントされる（`compose.yaml`）。
   長期のアクセスキーは使わず、SSO の一時トークンを利用する。

2. **デプロイ用の権限**

   `docs/deploy-iam-policy.json` を IAM Identity Center の権限セットに
   カスタマー管理ポリシーとして設定する。
   `PowerUserAccess` だけでは IAM ロールを作成できないため足りない。

3. **`.env` の設定**

   `AWS_PROFILE` / `AWS_REGION` / `BUDGET_ALERT_EMAIL` を設定する。
   これらは `compose.yaml` 経由でコンテナに渡り、`serverless.yml` が参照する。
   変更したら `sail down && sail up -d` でコンテナを作り直すこと。

### 手順

```bash
# 1. SSO ログイン（トークンが切れていたら）
aws sso login --profile portfolio

# 2. 依存の脆弱性チェック（NFR-S5 / SECURITY-10）
sail composer audit
sail npm audit --omit=dev

# 3. フロントエンドのビルド
sail npm run build

# 4. 本番用に開発依存を除外
sail composer install --no-dev --optimize-autoloader

# 5. デプロイ
sail npm run deploy

# 6. 開発依存を戻す
sail composer install
```

### 確認・撤去

```bash
sail npm run deploy:info      # 公開 URL とスタックの状態
sail npm run deploy:package   # CloudFormation テンプレートの生成（AWS に触らない）
sail npm run deploy:remove    # スタックごと削除
```

**初回デプロイは CloudFront の作成に 10〜20 分かかる。** 2 回目以降は 4〜5 分程度。

Serverless Framework v4 ではなく `osls` を使う。理由は ADR-001 を参照。

## AI 支援の設定

[Laravel Boost](https://laravel.com/docs/boost) を導入している。
`php artisan boost:install` により、このリポジトリで作業する AI エージェント向けに
Laravel のバージョンに合わせたガイドラインとスキル（Inertia + React、Pest、Tailwind など）、
および MCP サーバが設定されている。

- ガイドライン: `CLAUDE.md` の `<laravel-boost-guidelines>` ブロック
- スキル: `.claude/skills/`
- MCP: `.mcp.json`

Boost のガイドラインを最新化するには `sail artisan boost:update`。
**`CLAUDE.md` 冒頭の AI-DLC ワークフローは Boost とは別物**で、上書きされない。

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
