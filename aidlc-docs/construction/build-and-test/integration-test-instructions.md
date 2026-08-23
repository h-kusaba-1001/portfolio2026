# Integration Test Instructions

## このプロジェクトにおける「結合テスト」の位置づけ

**独立した結合テストのスイートは作っていない。**

理由: このシステムには**プロセスをまたぐ結合点が存在しない**。
データベース・外部 API・キュー・他サービスのいずれも持たない（ADR-002 / ADR-004）ため、
「サービス間の相互作用」を検証する対象がない。

代わりに、**ユニット間の結合は Feature テストが実際に通して検証している。**
Laravel のコンテナで実物を組み立て、HTTP リクエストを通すため、
モックで置き換えた偽の結合ではない。

## Feature テストが実際に通している結合

| # | 結合点 | 検証しているテスト |
|---|---|---|
| 1 | `content/*.md`（ファイル） → `CommonMarkParser` → `Section` | T-1〜T-5, T-8 |
| 2 | `MarkdownContentRepository` → `CachedContentRepository`（デコレータ） | T-9 |
| 3 | リポジトリ → `GetPortfolioContent`（失敗の変換） | T-6, T-7 |
| 4 | ユースケース → `PortfolioController` → Inertia props | `PortfolioPageTest` |
| 5 | ミドルウェア → レスポンス（ヘッダ・クッキー） | `SecurityHeadersTest`, `CacheabilityTest` |
| 6 | **サーバの `HeadingKey` ↔ フロントの `headingKey.ts`** | `DiagramNodesTest` |

**6 が最も重要。** 唯一「実装が 2 箇所にある」結合点であり、
ずれても実行時まで気付けない（business-rules.md R-2）。

## 実行

```bash
sail exec laravel.test ./vendor/bin/pest
```

**別途のサービス起動は不要。** データベースもキューも使わないため、
`sail up -d` でアプリケーションコンテナが動いていれば足りる。

## デプロイ後の結合確認（手動）

AWS 上でしか確認できない結合は、デプロイ後に手動で行う。

| # | 確認内容 | 方法 |
|---|---|---|
| I-1 | CloudFront → API Gateway → Lambda → Laravel | `curl -s -o /dev/null -w "%{http_code}" https://d3bttkxchvfb66.cloudfront.net/` → 200 |
| I-2 | CloudFront → S3（静的アセット） | HTML 内の `/build/assets/*.js` を取得して 200 |
| I-3 | Lambda → CloudWatch Logs | `aws logs tail /aws/lambda/hk-portfolio-prod-web --since 10m` |
| I-4 | CloudFront → S3（アクセスログ） | `aws s3 ls s3://hk-portfolio-prod-accesslogsbucket-*/cloudfront/`（**配信まで最大 1 時間**） |
| I-5 | S3 が直接開けないこと | バケットの URL に直接アクセスして 403 |

**I-1〜I-5 は 2026-08-22 に実施済み。全て期待どおり。**

## 後片付け

一時ディレクトリを使うテスト（T-3, T-5〜T-9）は `sys_get_temp_dir()` に書く。
コンテナを落とせば消えるため、明示的な後片付けは不要。
