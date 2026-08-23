#!/usr/bin/env bash
#
# 本番デプロイ（ADR-001: osls / ADR-005: Lift / ADR-007: Sail）
#
# 2 つのモードがある。
#
#   ./bin/deploy.sh          フルデプロイ。CloudFormation を経由してスタック全体を更新する
#   ./bin/deploy.sh --fast   関数コードだけを差し替える。CloudFormation を経由しない
#
# --fast が使えるのは「PHP のコードだけを変えたとき」。
# serverless.yml・環境変数・インフラ・**フロントエンドのアセット**を変えたときは
# フルデプロイが必要（アセットは Lift が S3 へ上げるため、関数の更新では反映されない）。
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

FAST=false
STAGE=prod

for arg in "$@"; do
    case "$arg" in
        --fast) FAST=true ;;
        *) STAGE="$arg" ;;
    esac
done

export WWWGROUP="${WWWGROUP:-$(id -g)}"
export WWWUSER="${WWWUSER:-$(id -u)}"

run() {
    docker compose exec -T -u sail laravel.test "$@"
}

restore_dev_dependencies() {
    echo "==> 開発依存を戻しています"
    run composer install --no-interaction
}

if [ "$FAST" = true ]; then
    echo "==> 高速モード: 関数コードのみ差し替え"
    echo "    serverless.yml やフロントのアセットを変えた場合は使えません"
else
    echo "==> 依存の脆弱性チェック（NFR-S5 / SECURITY-10）"
    run composer audit
    run npm audit --omit=dev

    echo "==> フロントエンドをビルド"
    run npm run build

    # AI・クローラ向けに、トップページをビルド時に描画して同梱する
    # （A-1 / ADR-020）。実行時の Lambda に Node は無いので、ここで作る。
    # **--fast では作り直さない。** 関数コードだけを差し替えるモードでは
    # アセットも反映されないため、原稿を変えたときはフルデプロイを使うこと。
    echo "==> トップページをプリレンダ（AI・クローラ向け）"
    run php artisan portfolio:prerender
fi

echo "==> 本番用に開発依存を除外（この時点で vendor/bin/sail は消える）"
run composer install --no-dev --optimize-autoloader --no-interaction

# 以降どこで失敗しても開発依存を戻す
trap restore_dev_dependencies EXIT

if [ "$FAST" = true ]; then
    echo "==> 関数を更新（stage: ${STAGE}）"
    run npx osls deploy function --function web --stage "${STAGE}"
else
    echo "==> デプロイ（stage: ${STAGE}）"
    run npx osls deploy --stage "${STAGE}"
fi

echo "==> デプロイ完了"

if [ "$FAST" = false ]; then
    run npx osls info --stage "${STAGE}"
fi
