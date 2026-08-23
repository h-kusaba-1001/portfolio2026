#!/usr/bin/env bash
#
# SBOM（ソフトウェア部品表）の生成（NFR-S5 / SECURITY-10）
#
# CycloneDX 形式で、本番に載る依存だけを出力する（開発依存は除外）。
# デプロイのたびには実行しない。時間がかかる割に、
# 依存が変わらない限り中身が変わらないため。
#
# 実行するタイミング:
#   - 依存を追加・更新したとき
#   - 公開前の確認
#
set -euo pipefail

cd "$(dirname "$0")/.."

export WWWGROUP="${WWWGROUP:-$(id -g)}"
export WWWUSER="${WWWUSER:-$(id -u)}"

run() {
    docker compose exec -T -u sail laravel.test "$@"
}

echo "==> PHP の依存（composer）"
run composer CycloneDX:make-sbom \
    --output-format=JSON \
    --output-file=sbom-composer.json \
    --omit=dev

echo "==> JavaScript の依存（npm）"
run npx @cyclonedx/cyclonedx-npm --omit dev --output-file sbom-npm.json

echo
echo "==> 生成物"
for f in sbom-composer.json sbom-npm.json; do
    count=$(python3 -c "import json;print(len(json.load(open('$f')).get('components',[])))" 2>/dev/null || echo '?')
    printf '  %-22s %s components\n' "$f" "$count"
done

echo
echo "SBOM は .gitignore 済み。配布が必要な場合はリリース成果物として添付すること。"
