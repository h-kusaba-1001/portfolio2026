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
# 依存解決（ホストに PHP が無くても実行できる）
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs

cp .env.example .env

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

http://localhost で表示される。停止は `./vendor/bin/sail down`。

`alias sail='./vendor/bin/sail'` を張っておくと `sail artisan ...` で済む。

データベースを持たない（ADR-002）ため、Sail のサービスはアプリケーションコンテナのみ。

## デプロイ

```bash
./vendor/bin/sail npm run build
osls deploy
```

Serverless Framework v4 ではなく `osls` を使う。理由は ADR-001 を参照。

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
