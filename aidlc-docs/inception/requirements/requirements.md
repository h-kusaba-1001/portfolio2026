# Requirements（AI-DLC / Requirements Analysis 成果物）

**方針**: Q2 の回答（A）により、**要件の正典は `docs/` 配下**とする。
本ファイルは AI-DLC のプロセス成果物として、意図分析・本ステージで新たに確定した事項・
制約とリスク・トレーサビリティのみを記録し、`docs/` の内容を複製しない。

| 正典 | 内容 |
|---|---|
| [`docs/requirements.md`](../../../docs/requirements.md) | ゴール、非ゴール、非機能要件（NFR-1〜6、NFR-S1〜S7）、技術構成、サイト構成、完了条件 |
| [`docs/architecture-decisions.md`](../../../docs/architecture-decisions.md) | ADR-001〜010 |
| [`docs/aidlc-inception.md`](../../../docs/aidlc-inception.md) | Intent、User Stories（US-1〜7）、Units of Work（UoW-1〜4）、Bolt Plan（B-1〜6） |

---

## 1. 意図分析（Intent Analysis）

| 項目 | 判定 |
|---|---|
| **User Request** | 「ではAI-DLCを始めてください」（先行して `docs/` に要件・ADR・Inception 成果物を整備済み） |
| **Request Type** | New Project |
| **Request Clarity** | Clear（ゴール・技術構成・完了条件が文書化済み） |
| **Scope Estimate** | System-wide（アプリケーション、フロントエンド、インフラ、デプロイの全て） |
| **Complexity Estimate** | Moderate |
| **Requirements Depth** | Standard |

**深度を Standard とした理由**: 先行ドキュメントによりゴールと技術選定が確定しており、
Comprehensive な要件の再構築は重複になる。一方で未確定事項（レンダリング方式、テスト方針、
セキュリティ要件）があったため Minimal では不足する。

---

## 2. 本ステージで確定した事項

| # | 論点 | 決定 | 反映先 |
|---|---|---|---|
| Q1 | スコープ | UoW-1 〜 UoW-4 の全て（サイト完成まで） | 本ファイル §3 |
| Q2 | ドキュメント構成 | `docs/` を正典とし、`aidlc-docs/` は参照のみ | 本ファイル冒頭 |
| Q3 | Laravel の配置 | ワークスペース直下に展開 | 本ファイル §4 |
| Q4 | PHP バージョン | PHP 8.4（Sail・Bref 双方） | ADR-007 に追記 |
| Q5 | AWS 認証情報 | 未準備（このマシンでの設定はこれから） | 本ファイル §5 CON-1 |
| Q6 | テスト方針 | 標準（Pest でユニット + Feature） | **ADR-009 を新規作成** |
| Q7 | SSR | 導入しない。OGP・meta は Blade で静的出力。将来 SSR の可能性は残す | **ADR-008 を新規作成** |
| Ext | Security ベースライン | **有効** | **ADR-010**、NFR-S1〜S7 |
| Ext | Resiliency ベースライン | 無効 | ADR-010 |
| Ext | Property-Based Testing | 無効 | ADR-010 |

---

## 3. スコープ（Q1: 全 UoW）

`docs/aidlc-inception.md` の UoW-1 〜 UoW-4 を全て対象とする。

| UoW | 内容 | 対応ストーリー |
|---|---|---|
| UoW-1 | 基盤構築（Sail + Laravel + Inertia + Tailwind + Bref + osls、デプロイ疎通まで） | — |
| UoW-2 | コンテンツ基盤（Markdown → CommonMark → Inertia props） | US-7 |
| UoW-3 | 静的セクション（Hero / やってきたこと / キャリア / これから / Contact） | US-1, US-4, US-5, US-6 |
| UoW-4 | 構成図アニメーション（リクエストの流れ + ノード別選定理由 + 拡張ポイント） | US-2, US-3 |

完了条件は `docs/requirements.md` §7 に従う。

---

## 4. リポジトリ構成（Q3: ルート直下）

```text
portfolio2026/
+-- app/                  # Laravel アプリケーションコード
+-- bootstrap/
+-- config/
+-- database/             # マイグレーションは使わない（ADR-002）
+-- public/
+-- resources/
|   +-- js/               # React + Inertia
|   +-- views/            # Blade（OGP・meta の静的出力を含む: ADR-008）
+-- routes/
+-- tests/                # Pest（ADR-009）
+-- content/              # 掲載コンテンツ Markdown（ADR-003）※既存
+-- docs/                 # 要件・ADR の正典 ※既存
+-- aidlc-docs/           # AI-DLC プロセス成果物 ※既存
+-- compose.yaml          # Laravel Sail（ADR-007）
+-- serverless.yml        # osls + Bref + Lift（ADR-001, ADR-005）
+-- CLAUDE.md             # AI-DLC ルール ※既存
+-- README.md             # ※既存
```

**注意**: `laravel new` は非空ディレクトリへの展開になる。既存の
`README.md` / `docs/` / `content/` / `CLAUDE.md` / `aidlc-docs/` / `.git` を
上書きしない手順が必要（Code Generation の計画で明示する）。

---

## 5. 制約とリスク

| ID | 内容 | 影響 | 対応 |
|---|---|---|---|
| **CON-1** | AWS 認証情報が未設定（Q5=B） | **Bolt B-2（デプロイ）が実行できない。** B-2 は「動く URL を先に確保する」意図で前倒し配置されている | Workflow Planning でこの依存の扱いを決める（下記 3 案） |
| CON-2 | ホストに PHP・Composer が無い | `composer install` を Docker 経由で実行する必要がある | ADR-007 の手順で対応済み |
| ~~CON-3~~ | ~~CSP を `unsafe-inline` なしで運用する場合の衝突~~ | — | **解決済み**（2026-08-22）。ADR-011 で `style-src` にのみ `'unsafe-inline'` を許可し、`script-src` は厳格に維持 |
| CON-4 | Security ベースラインのログ要件（NFR-S2, NFR-S3）が NFR-1 の費用目標に増分を与える | 月額数円〜十数円の見込み | 両立しない場合は ADR-010 を見直す |
| RISK-1 | `content/*.md` は本人にしか書けない実データ。文面の最終確認が必要 | 公開内容の正確性 | 公開前に本人が確認 |

### CON-1 の選択肢（Workflow Planning で決定）

- **案 A**: B-2 の前に AWS 認証情報を設定してもらい、Bolt 順序を変えない
- **案 B**: B-2 を後ろに送り、ローカル（Sail）で UoW-2 〜 UoW-4 を先に作る
- **案 C**: B-2 直前まで進め、そこで停止して認証情報の準備を待つ

---

## 6. トレーサビリティ

| 要件 | 出所 | 検証方法 |
|---|---|---|
| NFR-1〜6 | `docs/requirements.md` §3 | 完了条件 §7 |
| NFR-S1 | ADR-010 / SECURITY-04 | CloudFront の Response Headers Policy 設定のレビュー + デプロイ後に実 URL で確認（ADR-012 により Laravel 側では付与しないため Feature テストの対象外） |
| NFR-S8 | ADR-012 / SECURITY-04, 09 | 共有シークレット不一致で 403 になることの Feature テスト |
| NFR-S9 | ADR-013 / SECURITY-11 | `serverless.yml` の同時実行上限とキャッシュ設定のレビュー、AWS Budgets の設定確認 |
| NFR-S2 | ADR-010 / SECURITY-02 | `serverless.yml` のログ設定レビュー |
| NFR-S3 | ADR-010 / SECURITY-03, 14 | ログ設定とリテンション設定のレビュー |
| NFR-S4 | ADR-010 / SECURITY-06 | IAM ポリシー定義のレビュー |
| NFR-S5 | ADR-010 / SECURITY-10 | ロックファイルの存在、スキャン手順、イメージタグ固定の確認 |
| NFR-S6 | ADR-010 / SECURITY-09, 15 | `APP_DEBUG=false` の確認、エラーハンドラの Feature テスト |
| NFR-S7 | ADR-010 / SECURITY-01, 09 | S3 パブリックアクセスブロック設定の確認 |
| US-1〜7 | `docs/aidlc-inception.md` §2 | 各ストーリーの受け入れ条件 |

---

## 7. Security Compliance（Requirements Analysis ステージ）

本ステージの成果物は要件文書のため、「該当するセキュリティ要件が要件として捕捉されているか」で評価する。

| Rule | 判定 | 根拠 |
|---|---|---|
| SECURITY-01 暗号化 | 部分的に該当 → NFR-S7 | データストアは S3 の静的アセットのみ（DB なし: ADR-002）。S3 の暗号化とパブリックアクセスブロックを要件化 |
| SECURITY-02 中間層アクセスログ | 該当 → NFR-S2 | CloudFront・API Gateway が対象 |
| SECURITY-03 アプリケーションログ | 該当 → NFR-S3 | Laravel のログを CloudWatch Logs へ |
| SECURITY-04 HTTP セキュリティヘッダ | 該当 → NFR-S1 | HTML を返すため必須 |
| SECURITY-05 入力検証 | **N/A** | 受け付ける API パラメータが無い（読み取り専用の静的ページ。フォーム・クエリパラメータなし） |
| SECURITY-06 最小権限 | 該当 → NFR-S4 | Lambda 実行ロール、デプロイ用ポリシー |
| SECURITY-07 ネットワーク構成 | **N/A** | VPC を使わない（NAT Gateway 不使用: NFR-2）。セキュリティグループ・サブネットが存在しない |
| SECURITY-08 アプリ層アクセス制御 | **N/A** | 全ページが公開コンテンツ。認証・認可の対象リソースが無い |
| SECURITY-09 ハードニング | 該当 → NFR-S6, NFR-S7 | `APP_DEBUG=false`、デフォルトページの削除、S3 非公開 |
| SECURITY-10 サプライチェーン | 該当 → NFR-S5 | `composer.lock` / `package-lock.json`、脆弱性スキャン、イメージタグ固定 |
| SECURITY-11 セキュア設計 | 部分的に該当 | レート制限は CloudFront + Lambda の従量課金構成で、悪用時の主リスクは費用。設計時に検討する（Infrastructure Design で扱う） |
| SECURITY-12 認証・資格情報 | **N/A** | ユーザー認証が存在しない。AWS 認証情報はローカルの `~/.aws` を使い、コードに含めない |
| SECURITY-13 完全性検証 | 部分的に該当 | 外部 CDN からスクリプトを読まない方針（アセットは自前バンドル）。デシリアライズ対象の非信頼データ無し |
| SECURITY-14 アラート・監視 | 部分的に該当 → NFR-S3 | 認証イベントが無いためアラート対象は限定的。ログ保持 90 日以上を要件化 |
| SECURITY-15 例外処理 | 該当 → NFR-S6 | Markdown ファイル I/O のエラーハンドリング、グローバルエラーハンドラ |

**ブロッキング所見**: なし。該当する全ルールが NFR-S1〜S7 として要件に捕捉されている。
