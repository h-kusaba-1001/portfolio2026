# Infrastructure Design — UoW-1（基盤構築）

**確定日**: 2026-08-22
**決定**: Q1 = A（Lift + HTTP API 維持）、Q2 = A（`extensions` でアクセスログ追加）、
Q3 = A（本番のみ）、Q4 = B（SSO。認証情報は準備済み）

---

## 1. 論理コンポーネント → AWS サービスの対応

| 論理コンポーネント | AWS サービス | 定義場所 |
|---|---|---|
| LC-1 `SecurityHeaders` | （アプリ内。インフラ要素なし） | Laravel ミドルウェア |
| LC-2 `config/security.php` | （同上） | アプリ設定 |
| LC-3 `RequestId` | Lambda の実行コンテキスト | Bref 経由 |
| LC-4 JSON ログ | CloudWatch Logs | `provider.logRetentionInDays` |
| LC-5 例外ハンドラ | （アプリ内） | `bootstrap/app.php` |
| LC-6 `Pages/Error` | （アプリ内） | React |
| LC-7 CloudFront ビヘイビア | CloudFront + S3 | Lift `server-side-website` |
| LC-8 予約済み同時実行 | Lambda | `functions.web.reservedConcurrency` |
| LC-9 予算アラート | AWS Budgets | `resources.Resources.CostBudget` |
| LC-10 ログ保持 | CloudWatch Logs + S3 ライフサイクル | `provider` と `resources` |

---

## 2. AWS リソース一覧

| # | リソース | 作成元 | 備考 |
|---|---|---|---|
| R-1 | Lambda 関数 `web` | serverless（Bref） | 512 MB / 28 秒 / 予約済み同時実行 10 |
| R-2 | Lambda 実行ロール | serverless | CloudWatch Logs への書き込みのみ |
| R-3 | Lambda ロググループ | serverless | 保持 14 日 |
| R-4 | API Gateway HTTP API | serverless | ワイルドカードルート |
| R-5 | API Gateway アクセスログ用ロググループ | serverless（`logs.httpApi`） | 保持 14 日 |
| R-6 | CloudFront ディストリビューション | Lift | 既定オリジン = API Gateway、`/build/*` = S3 |
| R-7 | S3 バケット（静的アセット） | Lift | パブリックアクセスブロック済み。OAI/OAC 経由 |
| R-8 | **S3 バケット（アクセスログ）** | `resources` で自前定義 | ライフサイクル 14 日。**ACL 設定に注意（§5）** |
| R-9 | **AWS Budgets 予算** | `resources` で自前定義 | 1 USD / メール通知 |
| R-10 | CloudFormation スタック | serverless | スタック名 `hk-portfolio-prod` |

**VPC・セキュリティグループ・サブネット・NAT Gateway は作成しない**（NFR-2 / SECURITY-07 が N/A である根拠）。

---

## 3. `serverless.yml` の構造

```yaml
service: hk-portfolio

provider:
  name: aws
  region: ap-northeast-1
  stage: prod
  runtime: provided.al2
  logRetentionInDays: 14          # R-3 の保持期間（ADR-014）
  logs:
    httpApi: true                 # R-5: API Gateway アクセスログ（NFR-S2）
  environment:
    APP_ENV: production
    APP_DEBUG: 'false'            # NFR-S6 / SECURITY-09
    LOG_CHANNEL: stderr
    LOG_LEVEL: info
    CACHE_STORE: file
    # storage / cache の書き込み先を /tmp に向ける設定は §7 を参照

plugins:
  - ./vendor/bref/bref
  - serverless-lift

functions:
  web:
    handler: public/index.php
    runtime: php-84-fpm           # ADR-007
    memorySize: 512               # Q5 = A
    timeout: 28                   # U1-PF-2
    reservedConcurrency: 10       # LC-8 / ADR-013
    events:
      - httpApi: '*'

constructs:
  website:
    type: server-side-website
    assets:
      '/build/*': public/build
    extensions:
      distribution:               # F-3 への対応（Q2 = A）
        Properties:
          DistributionConfig:
            Logging:
              Bucket: !GetAtt AccessLogsBucket.DomainName
              Prefix: cloudfront/
              IncludeCookies: false

resources:
  Resources:
    AccessLogsBucket:             # R-8
      Type: AWS::S3::Bucket
      Properties:
        OwnershipControls:
          Rules:
            - ObjectOwnership: ObjectWriter    # §5 の注意を参照
        PublicAccessBlockConfiguration:
          BlockPublicAcls: true
          BlockPublicPolicy: true
          IgnorePublicAcls: true
          RestrictPublicBuckets: true
        BucketEncryption:
          ServerSideEncryptionConfiguration:
            - ServerSideEncryptionByDefault:
                SSEAlgorithm: AES256           # NFR-S7 / SECURITY-01
        LifecycleConfiguration:
          Rules:
            - Id: expire-access-logs
              Status: Enabled
              ExpirationInDays: 14             # LC-10 / ADR-014

    CostBudget:                   # R-9 / ADR-013
      Type: AWS::Budgets::Budget
      Properties:
        Budget:
          BudgetName: hk-portfolio-monthly
          BudgetType: COST
          TimeUnit: MONTHLY
          BudgetLimit:
            Amount: 1
            Unit: USD
        NotificationsWithSubscribers:
          - Notification:
              NotificationType: ACTUAL
              ComparisonOperator: GREATER_THAN
              Threshold: 80
            Subscribers:
              - SubscriptionType: EMAIL
                Address: ${env:BUDGET_ALERT_EMAIL}
```

**未確定**: 上記は設計案であり、Code Generation で実際に `osls deploy` を通して検証する。
特に Lift の `extensions` のマージ挙動（§5）と、`/tmp` 関連の環境変数（§7）は実機確認が必要。

---

## 4. IAM 設計

### 4.1 Lambda 実行ロール（R-2）— 最小権限（NFR-S4 / SECURITY-06）

| 許可するアクション | リソース | 用途 |
|---|---|---|
| `logs:CreateLogStream` | 自身のロググループ | ログ出力 |
| `logs:PutLogEvents` | 自身のロググループ | ログ出力 |

**許可しないもの**
- `logs:DeleteLogGroup` / `logs:DeleteLogStream`（SECURITY-14: 自身の監査ログを消せないようにする）
- S3・DynamoDB・SSM など、他サービスへの一切のアクセス（呼び出す先が無い）

serverless は既定で `logs:CreateLogStream` と `logs:PutLogEvents` を自身のロググループに限定して付与する。
**`provider.iam.role.statements` には何も追加しない**ことで最小権限を維持する。

### 4.2 デプロイ用ポリシー（Q6 = B）

`osls deploy` に必要なサービス。**リソースはワイルドカード**（ADR-012 に記録した SECURITY-06 の例外）。

| サービス | 主なアクション |
|---|---|
| CloudFormation | スタックの作成・更新・削除、変更セット |
| Lambda | 関数・バージョン・エイリアス・同時実行設定 |
| IAM | 実行ロールの作成・ポリシーのアタッチ、`iam:PassRole` |
| API Gateway | HTTP API・ステージ・ルート |
| CloudFront | ディストリビューション・OAC・キャッシュポリシー |
| S3 | バケット作成、デプロイパッケージとアセットのアップロード |
| CloudWatch Logs | ロググループの作成、保持設定 |
| Budgets | 予算の作成 |

**方針**: 初回は権限不足で失敗しながら詰める（Q6 = B の想定どおり）。
確定したポリシーは `docs/` 配下に JSON として残し、再現可能にする。

---

## 5. ⚠️ 実装上の注意: CloudFront アクセスログと S3 の ACL

**CloudFront の標準ログ（レガシー）は、保存先 S3 バケットで ACL が有効である必要があります。**

- 2023 年 4 月以降、新規 S3 バケットは既定で ACL が無効（`BucketOwnerEnforced`）
- `ObjectOwnership: BucketOwnerEnforced` のバケットにはログを配信できない
- CloudFront はログ配信のため `awslogsdelivery` アカウントに `FULL_CONTROL` を付与する
  → バケットの `ObjectOwnership` を **`ObjectWriter`** にする必要がある

§3 の `AccessLogsBucket` で `ObjectOwnership: ObjectWriter` を指定しているのはこのため。
**パブリックアクセスブロックは別軸の設定**であり、ACL を有効にしてもバケットは非公開のまま
（SECURITY-09 の「パブリックアクセスをブロックする」要件は満たす）。

### 代替案（採用しない）

**CloudFront 標準ログ v2** は CloudWatch Logs の配信機構（`AWS::Logs::DeliverySource` /
`DeliveryDestination` / `Delivery`）を使い、**ACL を必要としない**。

採用しない理由: CloudFormation リソースが 3 つ増え、Lift の構成と組み合わせた前例が少ない。
今回はレガシー方式の制約（ACL 有効化）を受け入れる方が単純で、
バケットの公開範囲にも影響しない。

**再検討の条件**: AWS がレガシー方式を廃止した場合、または ACL 有効化が
組織のポリシーに抵触する場合は v2 に切り替える。

---

## 6. ログ構成のまとめ

| ログ | 出力先 | 保持 | 実現方法 |
|---|---|---|---|
| アプリケーションログ | stderr → CloudWatch Logs | 14 日 | `provider.logRetentionInDays` |
| API Gateway アクセスログ | CloudWatch Logs | 14 日 | `provider.logs.httpApi: true` |
| CloudFront アクセスログ | S3（`AccessLogsBucket`） | 14 日 | `extensions.distribution` + ライフサイクル |

いずれも ADR-014 に基づき 14 日。

---

## 7. 環境変数と `/tmp` 問題

**Lambda 上で書き込めるのは `/tmp` のみ。** Laravel は既定で `storage/` 配下に
キャッシュ・コンパイル済みビュー・ログを書くため、そのままでは失敗する。

| 対象 | 対応 |
|---|---|
| ログ | `LOG_CHANNEL=stderr`（ファイルに書かない） |
| コンパイル済みビュー | `VIEW_COMPILED_PATH` を `/tmp` 配下に向ける |
| アプリケーションキャッシュ（Q3 = B のコンテンツキャッシュ） | キャッシュストアの出力先を `/tmp` 配下に向ける |
| セッション | 使わない（`SESSION_DRIVER=array`） |

**未確定**: `bref/laravel-bridge` がこれらの一部を自動で処理する可能性がある。
**Code Generation で実際の挙動を確認し、必要な環境変数のみを設定する。**
現時点で「全て手動設定が必要」と決めつけない。

---

## 8. 費用の再検証（NFR-1）

| 項目 | 月額見込み |
|---|---|
| Lambda（512 MB、想定アクセス数） | 0 円（無料枠内） |
| API Gateway HTTP API | 数円未満 |
| CloudFront（転送 + リクエスト） | 0 円（永年無料枠内） |
| S3（アセット数 MB） | 数円 |
| S3（アクセスログ、14 日で削除） | 数円 |
| CloudWatch Logs（取り込み 5 GB まで無料、保持 14 日） | 0〜1 円 |
| AWS Budgets（2 予算まで無料） | 0 円 |
| **合計** | **10 円前後の見込み** |

**NFR-1（月額 100 円以下）と両立する。**

**未検証の前提**: AWS の料金体系に関する一般的な理解に基づく見積りであり、
実際の請求額では検証していない。B-2 のデプロイ後、初回の請求で確認する。

---

## 9. Security Compliance（Infrastructure Design ステージ）

| Rule | 判定 | 根拠 |
|---|---|---|
| SECURITY-01 暗号化 | **準拠** | R-7・R-8 とも `BucketEncryption` を設定。転送時は CloudFront が TLS を終端し、HTTP は HTTPS へリダイレクト |
| SECURITY-02 アクセスログ | **準拠** | CloudFront（`extensions`）と API Gateway（`logs.httpApi`）の双方で有効化 |
| SECURITY-03 アプリログ | **準拠** | stderr → CloudWatch Logs |
| SECURITY-04 セキュリティヘッダ | **準拠** | アプリ側ミドルウェア（ADR-015） |
| SECURITY-05 入力検証 | **N/A** | 入力パラメータが存在しない |
| SECURITY-06 最小権限 | **準拠（例外あり）** | 実行ロールは CloudWatch Logs の書き込みのみ。デプロイ用ポリシーのワイルドカードは ADR-012 に記録済みの例外 |
| SECURITY-07 ネットワーク | **N/A** | VPC・セキュリティグループ・サブネットを作成しない |
| SECURITY-08 アクセス制御 | **N/A** | 認可対象のリソースが存在しない |
| SECURITY-09 ハードニング | **準拠** | `APP_DEBUG=false`、S3 のパブリックアクセスブロック（ACL 有効化とは別軸）、既定ページの削除は Code Generation で対応 |
| SECURITY-10 サプライチェーン | **準拠** | Bref のランタイムをバージョン指定（`php-84-fpm`）。ロックファイルのコミット |
| SECURITY-11 セキュア設計 | **準拠（既知の未対応あり）** | キャッシュ + 同時実行上限 + 予算アラート。**K-1（API Gateway 直アクセスによるキャッシュ迂回）は Q1 = A により未対応のまま**。費用は同時実行上限で頭打ち。拡張ポイントとして図示する |
| SECURITY-12 認証 | **N/A** | ユーザー認証なし。デプロイ認証情報は SSO の一時認証情報（Q4 = B）で、長期キーをローカルに置かない |
| SECURITY-13 完全性検証 | **準拠** | 外部 CDN を使わない |
| SECURITY-14 アラート・監視 | **部分的に適用外** | ログ保持は ADR-014 により 14 日。実行ロールにロググループ削除権限を与えない点は準拠 |
| SECURITY-15 例外処理 | **準拠** | アプリ側で対応済み |

**ブロッキング所見: なし。**

**K-1 についての補足**: SECURITY-11 は「レート制限または throttling」を求めており、
予約済み同時実行数がその役割を果たす。オリジン迂回は「キャッシュを迂回できる」問題であって
「制限が無い」問題ではないため、非準拠には当たらないと判断した。
