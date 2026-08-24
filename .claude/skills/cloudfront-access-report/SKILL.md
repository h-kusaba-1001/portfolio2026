---
name: cloudfront-access-report
description: "ポートフォリオサイト（hk-portfolio / CloudFront）のアクセス数を調べる。「アクセス数」「何人来た」「アクセスログ」「PV」「誰が見た」「CloudFront のログ」といった質問で起動する。S3 に配信された CloudFront 標準アクセスログを取得し、指定期間（既定 24 時間）のリクエスト数・ページビュー・ユニーク IP・時間帯分布・上位ページ・ボット比率を集計する。サイト所有者自身の IP は既定で除外する。デプロイやインフラ構成の変更には使わない。"
---

# CloudFront アクセスレポート

`hk-portfolio-prod` スタックの CloudFront アクセスログを S3 から取り、集計して報告する。

## 使い方

```bash
# 直近 24 時間（既定）
./.claude/skills/cloudfront-access-report/scripts/report.sh

# 期間を変える
./.claude/skills/cloudfront-access-report/scripts/report.sh --hours 72

# 除外 IP を足す（自分の回線が複数ある / IP が変わった場合）
./.claude/skills/cloudfront-access-report/scripts/report.sh --exclude-ip 203.0.113.10

# 自分の分も含めて全部見る
./.claude/skills/cloudfront-access-report/scripts/report.sh --include-self

# 生ログを残して自分で掘る
./.claude/skills/cloudfront-access-report/scripts/report.sh --raw ./cf-logs
```

期間を指定されなければ `--hours 24` のまま実行する。

## 前提

- ホストの `aws` CLI をそのまま使う（Sail 経由にしない）。プロファイルは `portfolio`。
- トークンが切れていたらスクリプトが検知して止まるので、ユーザーに
  `aws sso login --profile portfolio` を実行してもらう。**代わりに実行しない**（ブラウザ認証が要る）。

## 構成上の前提（serverless.yml）

- ログ配信先: `AccessLogsBucket`（スタックから引く）の `cloudfront/` プレフィックス
- 形式: CloudFront **標準ログ（レガシー）**。TSV + `#Version` / `#Fields` の 2 行ヘッダ、gzip
- ファイル名: `<DistributionId>.YYYY-MM-DD-HH.<hash>.gz`（`HH` は **UTC**、イベント発生時刻）
- 保持期間: **14 日**（S3 ライフサイクル / ADR-014）。それより古い期間は問い合わせても出ない
- バケット名も Distribution ID もハードコードせず、
  `aws cloudformation describe-stack-resources` で毎回引く

## 落とし穴

- **配信遅延**: 標準ログは数分〜1 時間ほど遅れて S3 に届く。
  「さっきアクセスしたのに 0 件」は正常。0 件のときは必ずこれを添えて報告する。
- **`aws s3 cp` を使わない / 並列化もしない**: `s3 cp` の転送マネージャも、
  `s3api get-object` の並列実行（`xargs -P`）も、この環境では無限に待つことがある。
  スクリプトは **逐次 + `timeout 30` + 3 回リトライ** で回している。
  数十ファイルでも 1 分かからないので、速くしようとして並列に戻さないこと。
- **作業ディレクトリ**: `mktemp -d` を `/tmp` 直下に作ると書き込みが止まることがある。
  スクリプトは `TMPDIR` かカレント配下に作る。
- **タイムゾーン**: ログの日時は UTC。集計 awk は `TZ=UTC` で走らせている。
  レポートには UTC と JST を併記する。
- **User-Agent と Content-Type は URL エンコードされている**（`%20` など）。デコードせず出す。
- **ユニーク IP ≠ 人数**。モバイル回線・CGNAT・IPv6 のプレフィックス変動で数は動く。
  「〜人が見た」と断定せず「ユニーク IP 数」として報告する。

## 報告のしかた

- 数字をそのまま貼るのではなく、**総リクエスト数・ページビュー・ユニーク IP・ボット比率**を要約する。
- 除外 IP がその期間に 1 件も現れなかった場合、スクリプトが警告を出す。
  その場合は上位 IP を見て「これが自分の回線では？」と確認を促す。
- 明らかなボット（`curl`、`Claude-User`、クラウド事業者のレンジ）は人間のアクセスと分けて言う。
- ログの保持は 14 日。それ以前を聞かれたら「残っていない」と答える。

## 自分の IP

既定の除外 IP はスクリプト冒頭の `EXCLUDE_IPS` 配列にある。
`61.114.213.168` はコメントアウトして置いてある（2026-08-22〜23 のログで全体の 94% を
占めていたが、本人の回線か未確認のため無効）。有効にするならコメントを外す。
一時的に足すだけなら `--exclude-ip` を渡す。
