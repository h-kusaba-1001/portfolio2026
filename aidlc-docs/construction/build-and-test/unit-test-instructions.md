# Unit Test Execution

## 実行

```bash
sail exec laravel.test ./vendor/bin/pest
```

特定のファイルだけ:

```bash
sail exec laravel.test ./vendor/bin/pest tests/Feature/ContentPipelineTest.php
```

## 期待される結果（2026-08-22 時点）

```
Tests:    30 passed (168 assertions)
Duration: 0.77s
```

**失敗は 0 件であること。**

## テストの構成

| ファイル | 対象 | 由来 |
|---|---|---|
| `Unit/DomainIsolationTest.php` | **Domain 層が `Illuminate\*` / `League\CommonMark\*` を import していないこと** | T-10 |
| `Feature/ContentPipelineTest.php` | Markdown の分割・正規化・失敗判定・キャッシュ | T-1〜T-9 |
| `Feature/DiagramNodesTest.php` | **構成図ノードの heading が `stack.md` に実在すること** | backlog §6 |
| `Feature/SecurityHeadersTest.php` | ヘッダ 5 件 + CSP + `X-Powered-By` の非開示 | NFR-S1 / SECURITY-09 |
| `Feature/CacheabilityTest.php` | レスポンスに `Set-Cookie` が付かないこと | P-1 の回帰防止 |
| `Feature/ErrorPageTest.php` | 404 で内部情報を漏らさないこと | NFR-S6 |
| `Feature/PortfolioPageTest.php` | props の形とセクションの並び | — |
| `Feature/SectionsRenderTest.php` | 各セクションの描画・OGP の静的出力 | UoW-3 |

## カバレッジについて

**測定していない。** カバレッジ率を目標に置くと、
「通したいだけのテスト」を書く動機が生まれるため。

代わりに **「壊れたときに困る経路」を名指しで押さえる方針**を採った（ADR-009）:

1. Markdown → props の変換（壊れると全ページが表示されなくなる）
2. セキュリティヘッダ（欠けると SECURITY-04 の非準拠）
3. 構成図ノードと見出しの対応（**実行時まで気付けない類の破綻**）
4. Domain 層の依存の向き（設計が崩れる）

## 落ちたときの手順

1. 出力から失敗したテスト名と行番号を読む
2. **テストが正しいか、実装が正しいかを先に判断する**
   - 仕様を変えたなら、テストの期待値を新しい仕様に合わせる
   - 仕様は変えていないなら、実装を直す
   - **期待値を緩めて通すことはしない**
3. 修正して再実行

**実例**: `content/stack.md` に `## S3` を追加した際、T-1 の「6 ブロック」が落ちた。
これは仕様変更（Q1 = A）によるものだったため、期待値を 7 に更新した。
