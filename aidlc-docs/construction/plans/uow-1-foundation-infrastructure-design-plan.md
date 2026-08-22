# Infrastructure Design Plan — UoW-1（基盤構築）

**目的**: NFR Design の論理コンポーネント（LC-1〜LC-10）を、実際の AWS サービスと
`serverless.yml` の記述に対応付ける。

**入力**: `aidlc-docs/construction/uow-1-foundation/nfr-design/`、ADR-001〜015

---

## 0. 事前調査の結果（一次情報で確認済み）

NFR Design で K-3 として残していた「Lift の設定可能範囲」を確認しました。

### Lift `server-side-website` がサポートする設定

出典: [Lift 公式ドキュメント](https://github.com/getlift/lift/blob/master/docs/server-side-website.md)

| オプション | 内容 |
|---|---|
| `assets` | 静的アセットの URL パターンとローカルパスの対応 |
| `versionedAssets` | ハッシュ付きアセットのゼロダウンタイムデプロイ |
| `apiGateway` | **`"rest"`（v1）または `"http"`（v2）を選択できる** |
| `domain` / `certificate` / `redirectToMainDomain` | 独自ドメイン（本プロジェクトでは不使用: NFR-5） |
| `errorPage` | 静的な HTML エラーページ |
| `extensions` | **CloudFormation リソースの上書き。キーは `distribution` と `bucket`** |

### 確認できた事実

| # | 事実 | 影響 |
|---|---|---|
| F-1 | **Lift は API Gateway を前提としており、Lambda Function URL をオリジンにする経路は提供されていない** | Function URL を使うなら Lift を外す必要がある |
| F-2 | **オリジンカスタムヘッダの設定オプションは存在しない** | ADR-012 の共有シークレット方式は `extensions.distribution` での上書きが必要。ただし `Origins` は配列プロパティのため、上書きは Lift の生成内容を丸ごと置き換える形になり壊れやすい |
| F-3 | **CloudFront アクセスログの設定オプションも存在しない** | **NFR-S2 / SECURITY-02 の実現に `extensions.distribution` が必要**（`Logging` はスカラープロパティのため上書きは比較的安全）。ログ保存用 S3 バケットも別途作る必要がある |
| F-4 | `apiGateway: "rest"` を選べる | REST API のリソースポリシーが使える（下記 F-5） |
| F-5 | Serverless Framework は `provider.apiGateway.resourcePolicy` で **`aws:SourceIp` による制限**をサポートする（REST API のみ。HTTP API にリソースポリシーは無い） | IP 制限による直アクセス遮断が可能。ただし CloudFront のオリジン向け IP レンジを列挙・維持する必要がある |
| F-6 | **CloudFront は Lambda Function URL に対する OAC をサポートする**（2024 年 4 月）。Function URL の `AuthType` を `AWS_IAM` にし、CloudFront が SigV4 署名する。署名の無いリクエストは AWS 側で拒否される | **直アクセスを完全に閉じられる。唯一の「本当に閉じる」方法** |
| F-7 | Bref は `url: true` で Function URL に対応している | F-6 の前提は満たせる |
| F-8 | F-6 の構成では、ディストリビューション作成後に **AWS CLI で Function URL のリソースポリシーを更新する手順**が必要 | デプロイ手順に手動または追加スクリプトのステップが増える |

---

## 1. カテゴリの適用判断

| カテゴリ | 適用 | 判断 |
|---|---|---|
| **Deployment Environment** | 質問 3, 4 | ステージ構成と認証情報の準備（CON-1）が未確定 |
| **Compute Infrastructure** | 確定済み | Lambda 512 MB / 28 秒 / 同時実行 10（NFR Design の設定値一覧） |
| **Storage Infrastructure** | 質問 2 の一部 | S3 は静的アセットとアクセスログのみ。データベースは無い（ADR-002） |
| **Messaging Infrastructure** | **対象外** | キュー・トピック・イベント駆動処理が存在しない。SQS は構成図の拡張ポイントに図示するのみ |
| **Networking Infrastructure** | **質問 1** | オリジン方式が最大の論点。VPC は使わない（NFR-2） |
| **Monitoring Infrastructure** | 質問 2 | ログ設定の実現方法。ダッシュボード・アラートは作らない（ADR-010） |
| **Shared Infrastructure** | **対象外** | 単一アプリケーション・単一スタック。共有リソース・マルチテナントの要素が無い |

---

## Question 1
**オリジン方式**をどうしますか？（前回の質問の続き。事実確認済みの内容で再提示します）

### A) 現状維持: Lift + API Gateway HTTP API
- **直アクセスは閉じられない**（HTTP API にリソースポリシーが無い: F-5）
- 迂回は既知の未対応事項として拡張ポイントに記載（ADR-015 の K-1）
- 実装量: 最小。`serverless.yml` は Lift の標準構成のまま
- 構成図: 現状のまま（`Browser → CloudFront → API Gateway → Lambda`）

### B) Lift + API Gateway REST API + リソースポリシー（IP 制限）
- `apiGateway: "rest"` に変更し、`provider.apiGateway.resourcePolicy` で
  CloudFront のオリジン向け IP レンジからのみ許可する（F-4, F-5）
- **直アクセスは「ほぼ」閉じられる**が、完全ではない:
  - CloudFront のオリジン向け IP レンジは AWS が公開しており**変動する**。
    列挙した IP をデプロイのたびに更新する運用が必要
  - 同じ IP レンジは**他人の CloudFront ディストリビューションも使う**ため、
    厳密には「CloudFront 経由なら誰のものでも通る」状態になる
- REST API は HTTP API より単価が高い（$3.50/M vs $1.00/M）。この規模では無料枠内
- 実装量: 中。構成図は変わらない

### C) Lift をやめ、Lambda Function URL + CloudFront OAC（**完全に閉じられる**）
- Function URL の `AuthType` を `AWS_IAM` にし、CloudFront の OAC が SigV4 署名する（F-6, F-7）
- **署名の無いリクエストは AWS 側で 403。URL を知られても開けない**
- **API Gateway が構成から消える** → その分の課金がゼロになる（NFR-1 に有利）
- 代償:
  - **ADR-005（Lift の採用）を置き換えることになる**。CloudFront・S3・OAC・
    アセットのアップロードを自前で定義する必要がある（Lift が自動化していた部分）
  - デプロイ後に AWS CLI で Function URL のリソースポリシーを更新する手順が必要（F-8）
  - **構成図から API Gateway のノードが消える**。`content/stack.md` の
    `## API Gateway` セクションと README の構成図を書き換える
  - 実装量: 最大

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 2
**CloudFront アクセスログ（NFR-S2 / SECURITY-02）の実現方法**をどうしますか？

**F-3 の再掲**: Lift にはアクセスログの設定オプションがありません。
有効化するには `extensions.distribution` で CloudFormation の `Logging` プロパティを
上書きし、ログ保存用の S3 バケットを別途作る必要があります。

A) **`extensions.distribution` で追加する**（Question 1 で A・B を選ぶ場合）
   - `Logging` はスカラープロパティのため、上書きは比較的安全
   - ログ用 S3 バケットを `resources:` セクションで定義し、14 日のライフサイクルを設定する
   - 実装量: 小〜中

B) **Question 1 で C を選ぶため、自前の CloudFront 定義に直接書く**
   - Lift を使わないため、上書きの問題自体が発生しない

C) **CloudFront のアクセスログを有効化しない**
   - **SECURITY-02 の非準拠（ブロッキング所見）になります。**
     選ぶ場合は SECURITY-14 のときと同様、拡張の適用範囲変更として記録が必要
   - API Gateway のアクセスログのみ有効にする

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 3
**ステージ構成**をどうしますか？

A) **本番のみ**（`prod` 1 つ）
   - AWS リソースが 1 セットだけ。費用も手間も最小
   - 検証はローカル（Sail）で行い、本番へ直接デプロイする

B) **開発 + 本番**（`dev` と `prod`）
   - 本番に影響を与えずにデプロイ検証ができる
   - CloudFront ディストリビューションが 2 つになる（作成に各 15 分程度かかる）
   - 無料枠は**アカウント単位**のため、2 セットでも枠内に収まる見込み

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 4
**AWS 認証情報の準備方法**（CON-1 の解消）をどうしますか？

Bolt B-2（デプロイ）に必要です。B-1（ローカル起動）と並行して準備する方針でした。

A) **IAM ユーザー + アクセスキー**を作成し、`aws configure` で設定する
   - 手順が単純。長期の認証情報がローカルに残る

B) **IAM Identity Center（SSO）** を設定し、`aws sso login` で一時認証情報を使う
   - 長期の認証情報を持たずに済む（SECURITY-12 の観点で望ましい）
   - 初期設定の手順が増える

C) 既に別の方法で用意できている

X) Other (please describe after [Answer]: tag below)

[Answer]:B
すでに用意済みなので、こちらでexportします

---

## Part 2: 実行ステップ（回答後に実施）

- [x] 回答の分析（曖昧・矛盾がないか検証。あれば追加質問）
      → 曖昧さなし。Q4 の「すでに用意済み」により CON-1 が解消
- [x] `aidlc-docs/construction/uow-1-foundation/infrastructure-design/infrastructure-design.md` を生成
      （論理コンポーネント → AWS サービスの対応、AWS リソース 10 件、`serverless.yml` の構造、IAM 設計）
- [x] `aidlc-docs/construction/uow-1-foundation/infrastructure-design/deployment-architecture.md` を生成
      （デプロイ構成図、認証情報、デプロイ手順、検証項目 V-1〜V-10、ロールバック手順）
- [x] NFR-1（費用）との整合性を再検証
      → 月 10 円前後の見込み。両立する
- [x] Security Compliance（SECURITY-01〜15）を評価
      → 準拠 9 / N/A 4 / 準拠（例外あり）1 / 部分的に適用外 1。ブロッキング所見なし
- [x] 新たな技術的決定が生じた場合は `docs/architecture-decisions.md` に ADR を追加
      → **Q1 = A のため ADR-005 / ADR-015 の見直しは不要**。追加 ADR なし
- [x] `content/stack.md` と README の構成図の更新要否を判定
      → **更新不要**（API Gateway を維持したため構成図は現状のまま）
- [x] **追加調査**: CloudFront 標準ログ（レガシー）の S3 ACL 要件を確認し、
      `ObjectOwnership: ObjectWriter` の必要性を設計に反映（infrastructure-design.md §5）
- [x] `aidlc-docs/aidlc-state.md` を更新
- [x] `aidlc-docs/audit.md` に記録
