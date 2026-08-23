#!/usr/bin/env bash
#
# CloudFront アクセスログ集計（hk-portfolio）
#
#   scripts/report.sh [--hours N] [--exclude-ip IP]... [--include-self] [--raw DIR]
#
# 既定は直近 24 時間。自分の IP（EXCLUDE_IPS）は除外して集計する。
set -euo pipefail

: "${AWS_PROFILE:=portfolio}"
: "${AWS_REGION:=ap-northeast-1}"
export AWS_PROFILE AWS_REGION

STACK="${STACK:-hk-portfolio-prod}"
HOURS=24
INCLUDE_SELF=0
RAW_DIR=""
# 既定の除外 IP（サイト所有者）
EXCLUDE_IPS=("72.14.201.152")
EXTRA_EXCLUDES=()

while [ $# -gt 0 ]; do
    case "$1" in
        --hours) HOURS="$2"; shift 2 ;;
        --exclude-ip) EXTRA_EXCLUDES+=("$2"); shift 2 ;;
        --include-self) INCLUDE_SELF=1; shift ;;
        --raw) RAW_DIR="$2"; shift 2 ;;
        -h|--help) sed -n '2,9p' "$0"; exit 0 ;;
        *) echo "unknown option: $1" >&2; exit 2 ;;
    esac
done

if [ "$INCLUDE_SELF" = 1 ]; then
    EXCLUDE_IPS=()
fi
if [ ${#EXTRA_EXCLUDES[@]} -gt 0 ]; then
    EXCLUDE_IPS+=("${EXTRA_EXCLUDES[@]}")
fi

# --- 認証確認 -------------------------------------------------------------
if ! aws sts get-caller-identity >/dev/null 2>&1; then
    echo "AWS の認証が切れている。次を実行すること:" >&2
    echo "  aws sso login --profile ${AWS_PROFILE}" >&2
    exit 1
fi

# --- スタックからログバケットと Distribution ID を引く ---------------------
read -r BUCKET DIST_ID <<EOF
$(aws cloudformation describe-stack-resources --stack-name "$STACK" \
    --query "[StackResources[?LogicalResourceId=='AccessLogsBucket'].PhysicalResourceId|[0],
              StackResources[?ResourceType=='AWS::CloudFront::Distribution'].PhysicalResourceId|[0]]" \
    --output text)
EOF

if [ -z "${BUCKET:-}" ] || [ "$BUCKET" = "None" ]; then
    echo "アクセスログのバケットが見つからない（stack: $STACK）" >&2
    exit 1
fi

WORK="${RAW_DIR:-$(mktemp -d)}"
mkdir -p "$WORK"
CLEANUP=1
[ -n "$RAW_DIR" ] && CLEANUP=0
trap '[ "$CLEANUP" = 1 ] && rm -rf "$WORK"' EXIT

# --- 対象時間帯のキーだけを落とす -----------------------------------------
# ログのファイル名は <DistId>.YYYY-MM-DD-HH.<hash>.gz（HH は UTC、イベント発生時刻）。
# 1 時間分のバッファを取って落とし、最終的なフィルタはレコードの実時刻で行う。
NOW_EPOCH=$(date -u +%s)
FROM_EPOCH=$(( NOW_EPOCH - HOURS * 3600 ))

HOUR_PATTERN=""
h=$(( HOURS + 1 ))
while [ "$h" -ge 0 ]; do
    stamp=$(date -u -d "@$(( NOW_EPOCH - h * 3600 ))" +%Y-%m-%d-%H)
    HOUR_PATTERN="${HOUR_PATTERN}${HOUR_PATTERN:+|}${DIST_ID}\.${stamp}\."
    h=$(( h - 1 ))
done

aws s3api list-objects-v2 --bucket "$BUCKET" --prefix "cloudfront/" \
    --query 'Contents[].Key' --output text 2>/dev/null | tr '\t' '\n' \
    | grep -E "$HOUR_PATTERN" > "$WORK/keys.txt" || true

KEY_COUNT=$(wc -l < "$WORK/keys.txt" | tr -d ' ')

: > "$WORK/all.tsv"
if [ "$KEY_COUNT" -gt 0 ]; then
    while read -r key; do
        [ -z "$key" ] && continue
        aws s3 cp "s3://${BUCKET}/${key}" "$WORK/$(basename "$key")" --quiet
    done < "$WORK/keys.txt"
    # ヘッダ行（#Version / #Fields）を落として連結
    zcat "$WORK"/*.gz 2>/dev/null | grep -v '^#' > "$WORK/all.tsv" || true
fi

FROM_UTC=$(date -u -d "@${FROM_EPOCH}" '+%Y-%m-%d %H:%M:%S')
NOW_UTC=$(date -u -d "@${NOW_EPOCH}" '+%Y-%m-%d %H:%M:%S')

EXCLUDE_CSV=$(IFS=,; echo "${EXCLUDE_IPS[*]:-}")

awk -F'\t' -v from="$FROM_EPOCH" -v excl="$EXCLUDE_CSV" \
    -v from_utc="$FROM_UTC" -v now_utc="$NOW_UTC" -v hours="$HOURS" \
    -v bucket="$BUCKET" -v dist="$DIST_ID" -v files="$KEY_COUNT" '
function epoch(d, t,   Y,M,D,hh,mm,ss) {
    split(d, a, "-"); split(t, b, ":")
    return mktime(a[1] " " a[2] " " a[3] " " b[1] " " b[2] " " b[3]) - tzoff
}
BEGIN {
    tzoff = systime() - mktime(strftime("%Y %m %d %H %M %S", systime(), 1))
    n = split(excl, e, ",")
    for (i = 1; i <= n; i++) { if (e[i] != "") ex[e[i]] = 1 }
}
{
    ts = epoch($1, $2)
    if (ts < from) next

    ip = $5; uri = $8; status = $9; ua = $11; result = $14; edge = $3

    if (ip in ex) { self_hits++; next }

    total++
    ips[ip]++
    stat[status]++
    uri_c[uri]++
    hour[substr($2, 1, 2)]++
    if (result ~ /Hit/) hits++

    # ページビュー: 静的アセットを除いた 2xx/3xx
    if (uri !~ /^\/(build|aws-icons|brand)\// && uri !~ /favicon|robots\.txt|\.(css|js|map|png|jpg|jpeg|svg|webp|ico|woff2?)$/ \
        && status ~ /^[23]/) {
        pv++
        pv_uri[uri]++
        pv_ip[ip]++
    }

    # ボット判定（User-Agent は URL エンコードされている）
    if (tolower(ua) ~ /bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headlesschrome|curl|wget|python-requests|scanner|nmap|zgrab|censys/) {
        bot++
        bot_ip[ip]++
    } else {
        human_ip[ip]++
        human++
    }
    ua_c[ua]++
}
END {
    printf "==================================================================\n"
    printf " CloudFront アクセスレポート  (直近 %s 時間)\n", hours
    printf "==================================================================\n"
    printf " 集計範囲 (UTC) : %s  →  %s\n", from_utc, now_utc
    printf " Distribution   : %s\n", dist
    printf " ログバケット   : %s (%s ファイル)\n", bucket, files
    if (length(ex) > 0) {
        s = ""
        for (i in ex) { s = s (s == "" ? "" : ", ") i }
        printf " 除外 IP        : %s  (%d リクエストを除外)\n", s, self_hits + 0
    } else {
        printf " 除外 IP        : なし (--include-self)\n"
    }
    printf "\n"

    if (total == 0) {
        printf " この期間のアクセスは 0 件。\n"
        printf "\n 注: CloudFront の標準ログは配信までに数分〜1 時間ほど遅れる。\n"
        exit
    }

    printf "── サマリ ────────────────────────────────────────────────────────\n"
    printf "  総リクエスト数     : %d\n", total
    printf "  ページビュー       : %d  (HTML のみ / 静的アセット除く)\n", pv + 0
    printf "  ユニーク IP        : %d\n", length(ips)
    printf "  ページ閲覧 IP      : %d\n", length(pv_ip)
    printf "  推定ボット         : %d リクエスト (%.0f%%) / %d IP\n", bot + 0, total ? bot * 100 / total : 0, length(bot_ip)
    printf "  推定人間           : %d リクエスト / %d IP\n", human + 0, length(human_ip)
    printf "  キャッシュヒット率 : %.1f%%\n", total ? hits * 100 / total : 0
    printf "\n"

    printf "── 時間帯別 (UTC / JST = +9) ──────────────────────────────────────\n"
    for (h = 0; h < 24; h++) {
        k = sprintf("%02d", h)
        if (!(k in hour)) continue
        jst = sprintf("%02d", (h + 9) %% 24)
        bar = ""
        c = hour[k]
        w = int(c / 2); if (w > 40) w = 40; if (c > 0 && w == 0) w = 1
        for (i = 0; i < w; i++) bar = bar "█"
        printf "  %s UTC (%s JST)  %4d  %s\n", k, jst, c, bar
    }
    printf "\n"

    printf "── アクセスの多いページ ──────────────────────────────────────────\n"
    show(pv_uri, 10, "  (ページビューなし)\n")
    printf "\n"

    printf "── リクエスト元 IP 上位 ──────────────────────────────────────────\n"
    show(ips, 10, "")
    printf "\n"

    printf "── ステータスコード ──────────────────────────────────────────────\n"
    show(stat, 10, "")
    printf "\n"

    printf "── User-Agent 上位 (URL エンコードのまま) ────────────────────────\n"
    show_trunc(ua_c, 8)
}
function show(arr, limit, empty,   k, i, j, keys, vals, n, tmp, tk) {
    n = 0
    for (k in arr) { n++; keys[n] = k; vals[n] = arr[k] }
    if (n == 0) { printf "%s", empty; return }
    for (i = 1; i < n; i++) for (j = i + 1; j <= n; j++)
        if (vals[j] > vals[i]) { tmp = vals[i]; vals[i] = vals[j]; vals[j] = tmp
                                 tk = keys[i]; keys[i] = keys[j]; keys[j] = tk }
    for (i = 1; i <= n && i <= limit; i++) printf "  %5d  %s\n", vals[i], keys[i]
}
function show_trunc(arr, limit,   k, i, j, keys, vals, n, tmp, tk, s) {
    n = 0
    for (k in arr) { n++; keys[n] = k; vals[n] = arr[k] }
    if (n == 0) return
    for (i = 1; i < n; i++) for (j = i + 1; j <= n; j++)
        if (vals[j] > vals[i]) { tmp = vals[i]; vals[i] = vals[j]; vals[j] = tmp
                                 tk = keys[i]; keys[i] = keys[j]; keys[j] = tk }
    for (i = 1; i <= n && i <= limit; i++) {
        s = keys[i]
        if (length(s) > 90) s = substr(s, 1, 90) "…"
        printf "  %5d  %s\n", vals[i], s
    }
}
' "$WORK/all.tsv"

if [ -n "$RAW_DIR" ]; then
    echo
    echo "生ログ: $WORK/all.tsv"
fi
