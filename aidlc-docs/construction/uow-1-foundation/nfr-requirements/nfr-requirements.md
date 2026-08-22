# NFR Requirements — UoW-1（基盤構築）

**対象**: Sail + Laravel + Inertia + Tailwind + Bref + osls、デプロイ疎通まで（Bolt B-1, B-2）
**確定日**: 2026-08-22

上位要件は `docs/requirements.md`（NFR-1〜6、NFR-S1〜S9）。本書はそれを UoW-1 の
実装単位に落とし込んだもの。

---

## 1. スケーラビリティ

| ID | 要件 | 根拠・備考 |
|---|---|---|
| U1-SC-1 | スケーリングは Lambda の自動スケールに委ねる。プロビジョンド同時実行は使わない | 固定費が発生するため（NFR-1） |
| U1-SC-2 | **Lambda の予約済み同時実行数に上限を設ける（初期値 10）** | 暴走時の課金上限。上限到達時は 429 を返す（Q8 = A） |
| U1-SC-3 | 想定負荷はピークで数リクエスト/秒。容量計画は行わない | 個人ポートフォリオ。負荷試験の対象外 |

**設計上の含意**: U1-SC-2 は可用性を犠牲にして費用を守る選択である。
アクセス集中時にサイトが 429 を返す可能性を受け入れる。

---

## 2. 性能

| ID | 要件 | 目標 |
|---|---|---|
| U1-PF-1 | Lambda メモリは 512 MB とする | Q5 = A。無料枠の消費を抑える |
| U1-PF-2 | Lambda タイムアウトは 28 秒とする | API Gateway HTTP API の上限（29 秒）に合わせる |
| U1-PF-3 | 静的アセット（JS / CSS / 画像）は CloudFront でキャッシュする | Lambda 到達を減らす（Q8 = C） |
| U1-PF-4 | HTML レスポンスも CloudFront でキャッシュする | 内容がデプロイ間で不変のため。TTL は Infrastructure Design で確定 |
| U1-PF-5 | コールドスタート時間の数値目標は設けない | 計測要件は削除済み（ADR-010 の経緯参照） |

**U1-PF-4 の注意**: HTML をキャッシュすると、デプロイ後に古い HTML が配信され得る。
Vite のハッシュ付きファイル名との組み合わせで不整合が起きないよう、
キャッシュ無効化の方針を Infrastructure Design で決める。

---

## 3. 可用性

| ID | 要件 | 備考 |
|---|---|---|
| U1-AV-1 | 稼働率の目標値は設定しない | Resiliency 拡張は無効（ADR-010） |
| U1-AV-2 | 復旧手段は `osls deploy` の再実行とする | 状態を持たないため、再デプロイで完全復旧する |
| U1-AV-3 | バックアップは不要 | 永続データが存在しない。コンテンツは Git にある（ADR-003） |
| U1-AV-4 | マルチリージョン構成は取らない | `ap-northeast-1` の単一リージョン（Q1 = A） |

---

## 4. セキュリティ

| ID | 要件 | 対応する上位要件 |
|---|---|---|
| U1-SE-1 | セキュリティヘッダは **CloudFront の Response Headers Policy** で付与する | NFR-S1 / SECURITY-04（Q3 = B） |
| U1-SE-2 | **CloudFront 経由でないリクエストを Lambda 側で 403 拒否する**。CloudFront がオリジンリクエストに付与する共有シークレットヘッダを検証する | NFR-S8 / SECURITY-04, 09（Q3-a = B） |
| U1-SE-3 | 共有シークレットは環境変数（SSM Parameter Store 経由）で供給し、コードに含めない | SECURITY-12（ハードコード禁止） |
| U1-SE-4 | CSP は `script-src 'self'` を維持し、`style-src` にのみ `'unsafe-inline'` を許可する | NFR-S1 / SECURITY-04（Q2 = B、ADR-011） |
| U1-SE-5 | HSTS は `max-age=31536000; includeSubDomains` とする | SECURITY-04 |
| U1-SE-6 | S3 バケットはパブリックアクセスをブロックし、CloudFront から OAC 経由でのみ読ませる | NFR-S7 / SECURITY-01, 09 |
| U1-SE-7 | S3 のサーバサイド暗号化（SSE-S3 以上）を有効にする | NFR-S7 / SECURITY-01 |
| U1-SE-8 | Lambda 実行ロールは CloudWatch Logs への書き込みのみに限定する | NFR-S4 / SECURITY-06 |
| U1-SE-9 | デプロイ用 IAM は専用ポリシーとし、必要サービスに限定する（リソースはワイルドカードを許容し、その旨を記録） | NFR-S4 / SECURITY-06（Q6 = B） |
| U1-SE-10 | 本番で `APP_DEBUG=false`、`APP_ENV=production` とする | NFR-S6 / SECURITY-09 |
| U1-SE-11 | Laravel の既定ルート・サンプルページを残さない | SECURITY-09 |
| U1-SE-12 | `composer audit` と `npm audit` をビルド手順に含める。SBOM は CycloneDX 形式で生成する | NFR-S5 / SECURITY-10（Q7 = A） |
| U1-SE-13 | Docker イメージとランタイムのバージョンを固定する（`latest` を使わない） | NFR-S5 / SECURITY-10 |

### CSP の具体値（U1-SE-4）

```
default-src 'self';
script-src 'self';
style-src 'self' 'unsafe-inline';
img-src 'self' data:;
font-src 'self';
connect-src 'self';
frame-ancestors 'none';
base-uri 'self';
form-action 'none'
```

**`'unsafe-inline'` を `style-src` に限定した正当化**: ADR-011 に記録。
`script-src` は厳格なまま維持するため、XSS の主要な攻撃経路（スクリプト実行）は塞がれている。
`form-action 'none'` はフォームが存在しないため設定できる。

---

## 5. 信頼性

| ID | 要件 | 備考 |
|---|---|---|
| U1-RL-1 | グローバルエラーハンドラを設定し、未捕捉例外を構造化ログに記録して汎用エラーページを返す | NFR-S6 / SECURITY-15 |
| U1-RL-2 | 利用者向けエラー画面にスタックトレース・内部パス・フレームワークバージョンを出さない | NFR-S6 / SECURITY-09 |
| U1-RL-3 | 外部依存が存在しないため、リトライ・サーキットブレーカ・タイムアウト設計は行わない | Resiliency 拡張は無効（ADR-010） |
| U1-RL-4 | **AWS Budgets で予算アラートを設定する（しきい値 1 USD、メール通知）** | Q8 = A。費用暴走の検知 |

---

## 6. 可観測性

| ID | 要件 | 備考 |
|---|---|---|
| U1-OB-1 | アプリケーションログは **JSON 構造化ログ**（Monolog JsonFormatter）で stderr に出力する | NFR-S3 / SECURITY-03（Q4 = A） |
| U1-OB-2 | ログには timestamp / level / message / リクエスト ID を含める | SECURITY-03 |
| U1-OB-3 | ログにシークレット・トークン・PII を出力しない（共有シークレットヘッダの値を含む） | SECURITY-03 |
| U1-OB-4 | CloudWatch Logs のロググループに保持期間 **14 日**を設定する | NFR-S3 / **SECURITY-14 のログ保持要件は適用外**（ADR-014） |
| U1-OB-5 | CloudFront と API Gateway のアクセスログを有効にする | NFR-S2 / SECURITY-02 |
| U1-OB-6 | Lambda 実行ロールにロググループの削除権限を与えない | SECURITY-14（この項目は引き続き適用） |
| U1-OB-8 | アクセスログ保存先 S3 バケットにライフサイクルルールを設定し、**14 日**で削除する | NFR-S3 / ADR-014 |
| U1-OB-7 | 監視ダッシュボード・セキュリティイベントアラートは作成しない | 認証イベントが存在しない。Resiliency 拡張は無効 |

---

## 7. 保守性

| ID | 要件 | 備考 |
|---|---|---|
| U1-MT-1 | ローカル開発は Laravel Sail（Docker）で行う | ADR-007 |
| U1-MT-2 | PHP は 8.4 に固定する（Sail・Bref の両方） | ADR-007 |
| U1-MT-3 | テストは Pest。UoW-1 の対象は「トップページが 200 を返す」「CloudFront 経由でないリクエストが 403 になる」の 2 点 | ADR-009 |
| U1-MT-4 | `Domain/` が `Illuminate\*` / `League\CommonMark\*` を import していないことを検証する手段を用意する | Application Design の不変条件 |
| U1-MT-5 | 依存はロックファイル（`composer.lock` / `package-lock.json`）で固定し、コミットする | NFR-S5 / SECURITY-10 |

---

## 8. ユーザビリティ

| ID | 要件 | 備考 |
|---|---|---|
| U1-US-1 | モバイル幅（375px）で破綻しないこと | NFR-4 |
| U1-US-2 | `prefers-reduced-motion` を尊重する土台を用意する（実装は UoW-4） | `docs/requirements.md` §6 |
| U1-US-3 | OGP・meta description を Blade で静的に出力する | ADR-008 |

---

## 9. NFR-1（費用）との整合性検証

| 項目 | 課金 | 見込み |
|---|---|---|
| Lambda | 従量課金（512 MB） | 無料枠内（月 100 万リクエスト + 40 万 GB-秒）→ **0 円** |
| API Gateway HTTP API | 従量課金 | 月 100 万リクエストまで 12 ヶ月無料。以降は 100 万件あたり約 1.0 USD → 想定アクセス数では数円未満 |
| CloudFront | 従量課金 | 永年無料枠（月 1 TB 転送 + 1000 万リクエスト）→ **0 円** |
| S3 | 保存 + リクエスト | アセット数 MB 程度 → 月数円 |
| CloudWatch Logs | 取り込み + 保存 | 取り込み月 5 GB まで無料。この規模では 0 円近辺。保存は 14 日分で 1 円未満（ADR-014） |
| CloudFront アクセスログ（S3 保存） | S3 保存料 | ログ量に比例。14 日のライフサイクル削除により上限が抑えられる（U1-OB-8） |
| AWS Budgets | 2 予算まで無料 | **0 円** |
| AWS WAF | — | **導入しない**（月 5〜8 USD の固定費を回避。Q8 = A） |

**結論**: NFR-1（月額 100 円以下）と両立する見込み。
ログ保持を 14 日に統一したこと（ADR-014）により、
アクセス増加時に最も伸びやすかった S3 保存料にも上限がかかる。

**未検証の前提**: 上記は AWS の料金体系に関する一般的な理解に基づく見積りであり、
実際の請求額で検証していない。乖離した場合は ADR-010 と NFR-1 を見直す。

---

## 10. Security Compliance（NFR Requirements ステージ）

| Rule | 判定 | 根拠 |
|---|---|---|
| SECURITY-01 暗号化 | **準拠** | U1-SE-6, U1-SE-7（S3 の暗号化とパブリックアクセスブロック）。TLS は CloudFront が終端し、HTTP はリダイレクトする |
| SECURITY-02 アクセスログ | **準拠** | U1-OB-5 |
| SECURITY-03 アプリログ | **準拠** | U1-OB-1〜3 |
| SECURITY-04 セキュリティヘッダ | **準拠** | U1-SE-1（CloudFront で付与）+ **U1-SE-2（オリジン直アクセスを遮断）**。この 2 点により、HTML を返す経路が CloudFront のみとなる。当初の Q3 = B 単独では非準拠だったが Q3-a = B で解消 |
| SECURITY-05 入力検証 | **N/A** | 受け付ける入力パラメータが存在しない |
| SECURITY-06 最小権限 | **準拠（例外あり）** | U1-SE-8（実行ロールは最小）。U1-SE-9 のデプロイ用ポリシーはリソースをワイルドカードとする。CloudFormation が作成前のリソースを対象とするため、リソース単位の限定が原理的に困難。**この例外を ADR-012 に文書化する** |
| SECURITY-07 ネットワーク | **N/A** | VPC を使用しない。セキュリティグループ・サブネット・NACL が存在しない |
| SECURITY-08 アクセス制御 | **N/A** | 全コンテンツが公開。認可対象のリソースが存在しない |
| SECURITY-09 ハードニング | **準拠** | U1-SE-6, U1-SE-10, U1-SE-11, U1-RL-2 |
| SECURITY-10 サプライチェーン | **準拠** | U1-SE-12, U1-SE-13, U1-MT-5 |
| SECURITY-11 セキュア設計 | **準拠** | レート制限は WAF ではなく、CloudFront キャッシュ（U1-PF-3, U1-PF-4）+ Lambda 同時実行上限（U1-SC-2）+ 予算アラート（U1-RL-4）の組み合わせで対応。**濫用ケースの想定**: 大量リクエストによる課金増加を主リスクと定め、上限で頭打ちにする設計とした。ADR-013 に記録 |
| SECURITY-12 認証 | **準拠（適用範囲を限定）** | ユーザー認証は存在しない。資格情報の扱いとして U1-SE-3（共有シークレットをコードに含めない）が該当 |
| SECURITY-13 完全性検証 | **準拠** | 外部 CDN からスクリプトを読み込まないため SRI 不要。非信頼データのデシリアライズなし |
| SECURITY-14 アラート・監視 | **部分的に適用外**（ADR-014） | **ログ保持要件（最低 90 日）は適用外とし、14 日とする**（U1-OB-4, U1-OB-8）。その他の項目は適用: U1-OB-6（ロググループ削除権限を与えない）。認証イベントが存在しないため認証失敗アラートは N/A。この適用外は `aidlc-docs/aidlc-state.md` の Extension Configuration に記録済み |
| SECURITY-15 例外処理 | **準拠** | U1-RL-1, U1-RL-2 |

**ブロッキング所見: なし**
- SECURITY-04 の非準拠は Q3-a = B（オリジン遮断）により解消
- SECURITY-14 のログ保持要件は、ADR-014 により**適用対象から外す**判断を明示的に行った
  （ブロッキング所見としてではなく、拡張の適用範囲の変更として扱う）
