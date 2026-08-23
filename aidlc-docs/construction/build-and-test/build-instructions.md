# Build Instructions

## Prerequisites

| 項目 | 内容 |
|---|---|
| ビルドツール | Vite 8（フロント）/ Composer 2.10（PHP） |
| 実行環境 | **Laravel Sail（Docker）**。ホストに PHP / Node を入れない（ADR-007） |
| Node | 24（`compose.yaml` の build args で固定） |
| PHP | 8.4.24 |
| システム要件 | Docker が動くこと。初回のイメージビルドに約 10 分 |

**ホストの Node を使わないこと。** osls 4 は `^20.19.0 || ^22.13.0 || >=24` を要求し、
Vite 8 も Node 20.19 以上を必要とする。

## 環境変数

`.env`（`.env.example` からコピー）に必要なもの。

| 変数 | 用途 |
|---|---|
| `APP_KEY` | Laravel の暗号化キー。`artisan key:generate` で生成 |
| `AWS_PROFILE` | デプロイに使う SSO プロファイル（既定 `portfolio`） |
| `AWS_REGION` | `ap-northeast-1` |
| `BUDGET_ALERT_EMAIL` | 予算アラートの通知先 |
| `LOG_STDERR_FORMATTER` | `Monolog\Formatter\JsonFormatter` |

`compose.yaml` 経由でコンテナに渡るため、**変更したら `sail down && sail up -d`** で作り直す。

## ビルド手順

### 1. 依存の解決

```bash
# 初回のみ（ホストに PHP / Composer が無くても実行できる）
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
  -v "$(pwd):/app" -w /app \
  laravelsail/php84-composer@sha256:a2716e93e577c80bca7551126056446c1e06cb141af652ee6932537158108400 \
  composer install

cp .env.example .env
./vendor/bin/sail up -d
sail npm install
```

### 2. ビルド

```bash
sail npm run build
```

### 3. 成功の確認

```
public/build/manifest.json              0.38 kB
public/build/assets/app-XXXXXXX.css    42.33 kB │ gzip:  8.21 kB
public/build/assets/app-XXXXXXX.js    330.11 kB │ gzip: 104.81 kB
✓ built in 575ms
```

**生成物**: `public/build/`（`.gitignore` 済み。デプロイパッケージには含まれる）

**許容される警告**: `npm notice run ...` は npm の通常出力。

## トラブルシューティング

### `EACCES` でビルドが失敗する

**原因**: `node_modules` や `vendor` が root 所有になっている。
`docker compose exec`（`-u sail` を付けない）で npm / composer を実行すると起きる。

**対処**:
```bash
docker compose exec -T laravel.test chown -R sail:sail /var/www/html/node_modules /var/www/html/vendor
```

**予防**: コンテナ内でファイルを作る操作は必ず `sail ...` か `-u sail` で行う。

### `Sail is not running` と出る

```bash
sail up -d
```

### `./vendor/bin/sail: not found`

**原因**: `composer install --no-dev` を実行した直後。**`laravel/sail` 自体が消えている。**

**対処**: `docker compose exec -u sail laravel.test composer install` で戻す。
デプロイ時は `./bin/deploy.sh` を使えばこの状態にならない。
