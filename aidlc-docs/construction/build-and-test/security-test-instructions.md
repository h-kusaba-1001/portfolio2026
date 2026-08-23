# Security Test Instructions

Security ベースライン拡張を有効にしている（ADR-010）ため、
検証手順を明示する。

## 1. 自動テストで担保している項目

```bash
sail exec laravel.test ./vendor/bin/pest
```

| SECURITY | 検証内容 | テスト |
|---|---|---|
| 04 | ヘッダ 5 件が設定ファイルの値と一致する | `SecurityHeadersTest` |
| 04 | **CSP の `script-src` が緩んでいない**（`unsafe-inline` / `unsafe-eval` が入っていない） | 同上 |
| 04 | エラーレスポンスにもヘッダが付く | 同上 |
| 09 | **`X-Powered-By` が出ていない**（PHP のバージョン非開示） | 同上 |
| 09 | 404 で内部パス・スタックトレースが出ない | `ErrorPageTest` |
| 05 相当 | **Markdown 中の生 HTML（`<script>` / `<iframe>`）が除去される** | `ContentPipelineTest` T-8 |
| 15 | 1 セクションが壊れてもページ全体が落ちない | 同上 T-6 |

## 2. 依存の脆弱性スキャン（毎デプロイ）

```bash
sail composer audit
sail npm audit --omit=dev
```

**`./bin/deploy.sh` がデプロイのたびに自動実行する。** 期待値は両方とも 0 件。

**2026-08-22 時点: 脆弱性 0 件。**

## 3. 手動で確認する項目（デプロイ後）

```bash
U=https://d3bttkxchvfb66.cloudfront.net

# ヘッダ 5 件
curl -s -D - -o /dev/null "$U/" | grep -iE "content-security-policy|strict-transport|x-content-type|x-frame|referrer-policy"

# X-Powered-By が無いこと
curl -s -D - -o /dev/null "$U/" | grep -i "x-powered-by" && echo "NG: 漏れている" || echo "OK"

# HTTP が HTTPS にリダイレクトされること
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" "http://d3bttkxchvfb66.cloudfront.net/"

# S3 が直接開けないこと
aws s3api get-object --bucket hk-portfolio-prod-websiteassets2a73bb69-a2dszugaqq74 --key index.html /dev/null
# → AccessDenied になること
```

## 4. SBOM の生成（NFR-S5 / SECURITY-10）

```bash
./bin/sbom.sh
```

CycloneDX 形式で、**本番に載る依存だけ**を出力する（開発依存は除外）。

| 出力 | 内容 |
|---|---|
| `sbom-composer.json` | PHP の依存（CycloneDX 1.5 / 93 components） |
| `sbom-npm.json` | JavaScript の依存（CycloneDX 1.6 / 49 components） |

**デプロイのたびには実行しない。** 時間がかかる割に、依存が変わらなければ中身も変わらないため。
実行するのは「依存を追加・更新したとき」と「公開前の確認」。

生成物は `.gitignore` 済み。配布が必要ならリリース成果物として添付する。

## 5. 未実施の項目（`docs/backlog.md` を参照）

| # | 内容 | backlog |
|---|---|---|
| CSP の厳格化 | `style-src` から `'unsafe-inline'` を外せるか未検証 | I-2 |

## 6. 実施しないこと

| 項目 | 理由 |
|---|---|
| ペネトレーションテスト | 認証・入力フォーム・データベースが無く、攻撃対象が「公開 HTML」しかない |
| 認証・認可のテスト | ユーザー認証が存在しない（SECURITY-08 / 12 は N/A） |
| 入力検証のテスト | 受け付ける入力パラメータが存在しない（SECURITY-05 は N/A） |

**N/A の判定根拠は各ステージの Security Compliance 表に記録している。**
「やっていない」ではなく「対象が無い」ことを、都度確認したうえでの判断。
