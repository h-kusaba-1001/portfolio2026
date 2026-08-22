#!/usr/bin/env bash
#
# 本番デプロイ（ADR-001: osls / ADR-005: Lift / ADR-007: Sail）
#
# なぜスクリプトにしているか:
#   `composer install --no-dev` は laravel/sail 自体を vendor から削除するため、
#   その直後に `./vendor/bin/sail` を呼ぶ手順は必ず失敗する。
#   さらに、途中で失敗すると開発依存が欠けたままの作業ツリーが残る。
#   この 2 つを避けるため、docker compose を直接使い、
#   終了時に必ず開発依存を戻す。
#
# 前提:
#   - `aws sso login --profile portfolio` 済み
#   - .env に AWS_PROFILE / AWS_REGION / BUDGET_ALERT_EMAIL / APP_KEY
#   - `sail up -d` でコンテナが起動している
#
set -euo pipefail

cd "$(dirname "$0")/.."

STAGE="${1:-prod}"

export WWWGROUP="${WWWGROUP:-$(id -g)}"
export WWWUSER="${WWWUSER:-$(id -u)}"

run() {
    docker compose exec -T -u sail laravel.test "$@"
}

restore_dev_dependencies() {
    echo "==> 開発依存を戻しています"
    run composer install --no-interaction
}

echo "==> 依存の脆弱性チェック（NFR-S5 / SECURITY-10）"
run composer audit
run npm audit --omit=dev

echo "==> フロントエンドをビルド"
run npm run build

echo "==> 本番用に開発依存を除外（この時点で vendor/bin/sail は消える）"
run composer install --no-dev --optimize-autoloader --no-interaction

# 以降どこで失敗しても開発依存を戻す
trap restore_dev_dependencies EXIT

echo "==> デプロイ（stage: ${STAGE}）"
run npx osls deploy --stage "${STAGE}"

echo "==> デプロイ完了"
run npx osls info --stage "${STAGE}"
