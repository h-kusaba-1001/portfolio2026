# AI-DLC State Tracking

## Project Information
- **Project Name**: portfolio2026（HK Portfolio）
- **Project Type**: Greenfield
- **Start Date**: 2026-08-22T08:51:01Z
- **Current Stage**: INCEPTION - Workflow Planning

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
- **CON-1**: AWS 認証情報が未設定 → Bolt B-2（デプロイ）が実行不可。Workflow Planning で順序を決定する

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
- [x] NFR Requirements — EXECUTE（UoW-1 のみ）※承認待ち
- [ ] NFR Design — EXECUTE（UoW-1 のみ）
- [ ] Infrastructure Design — EXECUTE（UoW-1 のみ）
- [ ] Code Generation — EXECUTE（UoW-1〜4）
- [ ] Build and Test — EXECUTE

### 🟡 OPERATIONS PHASE
- [ ] Operations（プレースホルダ）

## Current Status
- **Lifecycle Phase**: INCEPTION
- **Current Stage**: CONSTRUCTION - UoW-1 / NFR Requirements Complete
- **Next Stage**: UoW-1 の NFR Design
- **Status**: 承認待ち

## UoW-1 で確定した主要決定
- リージョン `ap-northeast-1` / Lambda メモリ 512 MB / 同時実行上限 10
- CSP: `script-src 'self'` 厳格、`style-src` のみ `'unsafe-inline'`（ADR-011）
- セキュリティヘッダは CloudFront で付与、オリジン直アクセスは共有シークレットで遮断（ADR-012）
- AWS WAF を使わず、キャッシュ + 同時実行上限 + 予算アラートで濫用対策（ADR-013）
- JSON 構造化ログ、ログ保持は一律 14 日（ADR-014。SECURITY-14 のログ保持要件は適用外）
- デプロイ IAM は専用ポリシー（リソースはワイルドカード。SECURITY-06 の例外として記録）
- 依存スキャンは `composer audit` / `npm audit`、SBOM は CycloneDX

## Unit Progress
| Unit | ディレクトリ | 状態 |
|---|---|---|
| UoW-1 基盤構築 | `aidlc-docs/construction/uow-1-foundation/` | NFR Requirements 進行中 |
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
