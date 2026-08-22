# AI-DLC State Tracking

## Project Information
- **Project Name**: portfolio2026（HK Portfolio）
- **Project Type**: Greenfield
- **Start Date**: 2026-08-22T08:51:01Z
- **Current Stage**: CONSTRUCTION - UoW-1 完了 / UoW-2 未着手

## Workspace State
- **Existing Code**: No（Markdown ドキュメントのみ。ソースコード・ビルドファイルなし）
- **Programming Languages**: なし（予定: PHP / JavaScript）
- **Build System**: なし（予定: Composer / npm）
- **Project Structure**: Empty（ドキュメント先行）
- **Reverse Engineering Needed**: No
- **Workspace Root**: /home/kusaba/projects/portfolio2026

## Pre-Existing Artifacts（AI-DLC ワークフロー開始前に手書きされた成果物）
| ファイル | 内容 | AI-DLC 上の位置づけ |
|---|---|---|
| `docs/requirements.md` | ゴール・非ゴール・非機能要件・サイト構成・完了条件 | Requirements Analysis の入力 |
| `docs/architecture-decisions.md` | ADR-001〜007 | 技術的意思決定の記録 |
| `docs/aidlc-inception.md` | Intent / User Stories / Units of Work / Bolt Plan | User Stories・Units Generation の入力 |
| `content/*.md` | 掲載コンテンツ本文（stack / experience / career / next） | 実データ |
| `README.md` | プロジェクト概要・セットアップ手順 | 参考 |

## Local Toolchain（検出結果）
- Docker: 26.1.0（利用可）
- Node.js: v20.12.2（利用可）
- AWS CLI: 2.15.42（利用可）
- PHP: 未インストール
- Composer: 未インストール

→ PHP / Composer がホストに無いため、ADR-007（Laravel Sail によるローカル環境構築）の前提と整合。

## Code Location Rules
- **Application Code**: Workspace root（NEVER in aidlc-docs/）
- **Documentation**: aidlc-docs/ only
- **Structure patterns**: See code-generation.md Critical Rules

## Extension Configuration
| Extension | Enabled | Decided At |
|---|---|---|
| Security Baseline | Yes（一部適用外あり） | Requirements Analysis |
| Resiliency Baseline | No | Requirements Analysis |
| Property-Based Testing | No | Requirements Analysis |

根拠は ADR-010（`docs/architecture-decisions.md`）。
Security 有効化に伴い、`security-baseline.md` をロード済み。全ステージで強制する。

### Security Baseline の適用外項目
| Rule | 適用外の範囲 | 決定 | 根拠 |
|---|---|---|---|
| SECURITY-14 | **ログ保持期間の最低 90 日要件のみ**（14 日とする）。同ルールの他の項目は適用する | CONSTRUCTION / UoW-1 / NFR Requirements | ADR-014 |

### 文書化された例外（ルールが例外を認めている範囲）
| Rule | 例外の内容 | 根拠 |
|---|---|---|
| SECURITY-06 | デプロイ用 IAM ポリシーのリソースをワイルドカードとする（CloudFormation が未作成リソースを操作するため限定困難）。Lambda 実行ロールは最小権限を維持 | ADR-012 |

## Key Decisions（Requirements Analysis で確定）
- スコープ: UoW-1 〜 UoW-4 の全て
- ドキュメント正典: `docs/`（`aidlc-docs/` は参照のみ、複製しない）
- Laravel 配置: ワークスペース直下
- PHP: 8.4（Sail・Bref 双方）
- テスト: Pest（ユニット + Feature）、ブラウザテストなし
- SSR: 導入しない。OGP・meta は Blade で静的出力

## Open Constraints
- ~~**CON-1**: AWS 認証情報が未設定~~ → **解消済み**（IAM Identity Center / プロファイル `portfolio`）

## Execution Plan Summary
- **Total Stages**: 13（INCEPTION 7 + CONSTRUCTION 6）
- **Stages to Execute**: Workspace Detection / Requirements Analysis / Workflow Planning / Application Design / Functional Design（UoW-2）/ NFR Requirements（UoW-1）/ NFR Design（UoW-1）/ Infrastructure Design（UoW-1）/ Code Generation（UoW-1〜4）/ Build and Test
- **Stages to Skip**: Reverse Engineering（greenfield）/ User Stories（US-1〜7 が `docs/aidlc-inception.md` に既存）/ Units Generation（UoW-1〜4 が既存）
- **Risk Level**: Medium
- 詳細: `aidlc-docs/inception/plans/execution-plan.md`

## Stage Progress

### 🔵 INCEPTION PHASE
- [x] Workspace Detection
- [x] Reverse Engineering — SKIPPED（greenfield のため）
- [x] Requirements Analysis — 承認済み（2026-08-22）
- [x] User Stories — SKIP（US-1〜7 が既存）
- [x] Workflow Planning — 承認済み（2026-08-22）
- [x] Application Design — 承認済み（2026-08-22）
- [ ] Units Generation — SKIP（UoW-1〜4 が既存）

### 🟢 CONSTRUCTION PHASE
- [ ] Functional Design — EXECUTE（UoW-2 のみ）
- [x] NFR Requirements — EXECUTE（UoW-1 のみ）※承認済み（2026-08-22）
- [x] NFR Design — EXECUTE（UoW-1 のみ）※承認済み（2026-08-22）
- [x] Infrastructure Design — EXECUTE（UoW-1 のみ）※承認済み（2026-08-22）
- [ ] Code Generation — EXECUTE（UoW-1〜4）※**UoW-1 完了（承認待ち）**、UoW-2〜4 未着手
- [ ] Build and Test — EXECUTE

### 🟡 OPERATIONS PHASE
- [ ] Operations（プレースホルダ）

## Current Status
- **Lifecycle Phase**: CONSTRUCTION
- **Current Stage**: UoW-1 / Code Generation 完了（B-1・B-2 とも完了）
- **Next Stage**: UoW-2 の Functional Design
- **Status**: 承認待ち

## 🌐 公開 URL
**https://d3bttkxchvfb66.cloudfront.net**（スタック `hk-portfolio-prod` / `ap-northeast-1`）

## 実行環境
Docker はグループ未所属で直接実行できないが、`sg docker -c '<コマンド>'` 経由で実行可能。**再ログイン不要**。
（ユーザーが `usermod -aG docker` を実行済み。現行シェルには反映されないため sg を使う）

## UoW-1 の実装状況
- **B-1（ローカル起動）完了**。Laravel 13.26.1 / PHP 8.4.24 / Vite 8 / Inertia 3.3 / Pest 4 / Bref 3 / osls 4.1.0
- テスト 9 件通過、型チェック通過、ビルド成功、脆弱性 0 件
- `osls print` / `osls package` で `serverless.yml` の妥当性を検証済み
- **B-2（デプロイ）完了**（2026-08-22）。失敗 2 回を経て成功: ① 権限セットに土台権限が無かった ② アカウントの Lambda 同時実行上限が 10 で予約ができなかった（ADR-016）
- 検証 V-1〜V-10: 合格 8 / 未達 1（V-5 キャッシュ）/ 未確認 1（V-7 ログ形式）
- Laravel Boost 導入、Sail の Node を 24 に固定、Sail からビルド・デプロイ可能に

## 未解決の問題
- ~~**P-1**（Set-Cookie）~~ → **解決済み**。`web` グループからセッションと CSRF を除去（Laravel 13 では `PreventRequestForgery` に改名されている点に注意）
- **P-2**: **HTML が CloudFront にキャッシュされない**（V-5 未達）。Lift が Lambda 側ビヘイビアに `CachingDisabled` を適用しているため、アプリが `Cache-Control` を返しても無視される。対応案 A/B/C を implementation-summary.md §9 に記載。**判断待ち**
- **V-7**: アプリログの JSON 形式が本番で未確認（正常系ではログが出ないため）
- ~~D-4~~ → 確定。`docs/deploy-iam-policy.json`。初回のみ必要な権限の内訳は implementation-summary.md §10
- ~~D-5~~ → 確定。`jojo1889jojo@gmail.com`

## 解決済みの論点
- ~~**オリジン方式**~~ → Q1 = A（Lift + HTTP API を維持）。直アクセスは閉じない。K-1 として拡張ポイントに図示
- ~~**CON-1**~~ → **解消**。IAM Identity Center（SSO）の一時認証情報を利用。準備済み

## Infrastructure Design で確定した内容
- AWS リソース 10 件（Lambda / 実行ロール / ロググループ×2 / HTTP API / CloudFront / S3×2 / Budgets / CFN スタック）
- ステージは `prod` のみ、スタック名 `hk-portfolio-prod`
- CloudFront アクセスログは `extensions.distribution` で追加。**ログ用 S3 は `ObjectOwnership: ObjectWriter` が必須**（レガシー標準ログの ACL 要件）
- Lambda 実行ロールは CloudWatch Logs の書き込みのみ（削除権限なし）
- 費用見込み: 月 10 円前後

## Code Generation への引き渡し事項（未確定）
- D-1: Lift の `extensions` のマージ挙動（実機確認）
- D-2: `bref/laravel-bridge` の `/tmp` 自動処理範囲
- D-3: 本番デプロイ時の開発用依存の除外手順
- D-4: デプロイ用 IAM ポリシーの最終内容
- D-5: 予算アラートの通知先メールアドレス

## UoW-1 で確定した主要決定
- リージョン `ap-northeast-1` / Lambda メモリ 512 MB / 同時実行はアカウント上限 10 に委ねる（ADR-016）
- CSP: `script-src 'self'` 厳格、`style-src` のみ `'unsafe-inline'`（ADR-011）
- セキュリティヘッダは Laravel ミドルウェアで付与（ADR-015。ADR-012 を Superseded）
- HTML は 60 秒キャッシュの想定だったが **未達**（P-2）。静的アセットは CachingOptimized。Invalidation は使わない
- エラーページは Inertia、429 はそのまま返す、相関 ID は Lambda リクエスト ID
- AWS WAF を使わず、キャッシュ + 同時実行上限 + 予算アラートで濫用対策（ADR-013）
- JSON 構造化ログ、ログ保持は一律 14 日（ADR-014。SECURITY-14 のログ保持要件は適用外）
- デプロイ IAM は専用ポリシー（リソースはワイルドカード。SECURITY-06 の例外として記録）
- 依存スキャンは `composer audit` / `npm audit`、SBOM は CycloneDX

## Unit Progress
| Unit | ディレクトリ | 状態 |
|---|---|---|
| UoW-1 基盤構築 | `aidlc-docs/construction/uow-1-foundation/` | **完了（デプロイ済み）**。承認待ち |
| UoW-2 コンテンツ基盤 | `aidlc-docs/construction/uow-2-content/` | 未着手 |
| UoW-3 静的セクション | `aidlc-docs/construction/uow-3-sections/` | 未着手 |
| UoW-4 構成図 | `aidlc-docs/construction/uow-4-diagram/` | 未着手 |

## Application Design で確定した設計判断
- レイヤ: Domain / Application / Infrastructure / Http（軽量 3 層 + ポート）
- ユースケースは `GetPortfolioContent` の 1 つのみ
- 構成図ノードと `stack.md` の H2 見出しを文字列一致で対応付ける（要検証テスト）
- キャッシュは Repository のデコレータとして分離
- 読み込み失敗はセクション枠を残し固定文言を表示、ページ全体は生存
- 構成図 SVG のノード座標はコンポーネントに直書き
- Hero の文言と GitHub リンクはコンポーネントに直接記述

## Open Items
- なし（フロントエンドの言語は TypeScript で確定。ADR-006 に追記済み）
