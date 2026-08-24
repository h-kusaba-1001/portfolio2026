#!/usr/bin/env bash
#
# デプロイ後の確認（毎回必ず実行する）。
#
# **CloudFront は HTML を 5 秒キャッシュする（ADR-017）。**
# 直後に叩くと古い版を見てしまうので、キャッシュが切れるのを待ってから確認する。
#
# なぜ必要か:
#   1. Inertia のルート要素を壊して画面が真っ白になったことがある。
#      HTTP は 200 のままなので、状態コードだけ見ても気づけない。
#   2. デプロイ用スクリプトが途中で失敗しても、出力を確認しなければ
#      「壊れた版が本番に残ったまま」に気づけない。
#
# 使い方: ./bin/verify-deploy.sh [URL]
set -uo pipefail

URL="${1:-https://d3bttkxchvfb66.cloudfront.net}"
WAIT="${VERIFY_WAIT:-8}"

failures=0

check() {
    local label="$1" expected="$2" actual="$3"

    if [ "$actual" = "$expected" ]; then
        printf '  \033[32mOK\033[0m   %-38s %s\n' "$label" "$actual"
    else
        printf '  \033[31mNG\033[0m   %-38s %s（期待: %s）\n' "$label" "$actual" "$expected"
        failures=$((failures + 1))
    fi
}

echo "==> CloudFront のキャッシュが切れるのを待つ（${WAIT} 秒）"
sleep "$WAIT"

html=$(mktemp)
status=$(curl -sS -o "$html" -w '%{http_code}' "$URL/")

check "HTTP ステータス" "200" "$status"

# Inertia v3 はここからページ情報を読む。消えるとクライアントが null を掴む
check "data-page の script 要素" "1" \
    "$(grep -c '<script data-page="app" type="application/json">' "$html")"

# プリレンダした HTML を丸ごと埋めると入れ子になり、掴む要素が不定になる
check 'マウント先 id="app"' "1" \
    "$(grep -o 'id="app"' "$html" | wc -l | tr -d ' ')"

# ページ情報が JSON として壊れていないか
component=$(python3 - "$html" <<'PY'
import json, re, sys
html = open(sys.argv[1], encoding='utf-8').read()
m = re.search(r'<script data-page="app" type="application/json">(.*?)</script>', html, re.S)
try:
    print(json.loads(m.group(1))['component'])
except Exception:
    print('読めない')
PY
)
check "ページ情報の component" "Portfolio" "$component"

# 参照しているアセットが全て取得できるか（Lift の assets 漏れを検出する）
missing=0
for path in $(grep -oE '/build/assets/[a-zA-Z0-9._-]+\.(js|css)' "$html" | sort -u); do
    code=$(curl -sS -o /dev/null -w '%{http_code}' "$URL$path")
    [ "$code" = "200" ] || { missing=$((missing + 1)); echo "      404: $path"; }
done
check "取得できないアセット" "0" "$missing"

# AI・クローラ向けの本文が入っているか（ADR-020）
text_length=$(python3 - "$html" <<'PY'
import html as h, re, sys
raw = open(sys.argv[1], encoding='utf-8').read()
stripped = re.sub(r'<script.*?</script>', '', raw, flags=re.S)
text = re.sub(r'\s+', ' ', h.unescape(re.sub(r'<[^>]+>', ' ', stripped))).strip()
print('十分' if len(text) > 800 else f'不足({len(text)}文字)')
PY
)
check "script を除いた本文" "十分" "$text_length"

rm -f "$html"

echo
if [ "$failures" -eq 0 ]; then
    echo "==> 確認 OK。デプロイ完了。"
    exit 0
fi

echo "==> ${failures} 件の問題。**デプロイは完了していない。**"
exit 1
