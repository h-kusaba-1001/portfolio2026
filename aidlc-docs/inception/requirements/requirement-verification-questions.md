# Requirements Verification Questions

各質問の `[Answer]:` の後に選択肢の記号を記入してください。
どの選択肢も当てはまらない場合は最後の選択肢（Other）を選び、`[Answer]:` の後に内容を書いてください。

**前提**: `docs/requirements.md` / `docs/architecture-decisions.md` / `docs/aidlc-inception.md` は
既に整備済みのため、そこで確定している内容（技術スタック、ADR-001〜007、非機能要件、サイト構成）は
再質問しません。以下は未確定・曖昧な点のみです。

---

## Question 1
今回の AI-DLC で扱うスコープはどこまでですか？

A) UoW-1 のみ（基盤構築: Sail + Laravel + Inertia + Tailwind + Bref + osls、デプロイ疎通まで）

B) UoW-1 と UoW-2（基盤 + Markdown コンテンツ基盤）

C) UoW-1 〜 UoW-4 の全て（サイト完成まで）

X) Other (please describe after [Answer]: tag below)

[Answer]:C

---

## Question 2
既存の `docs/` 配下（requirements.md / architecture-decisions.md / aidlc-inception.md）と、
AI-DLC が生成する `aidlc-docs/` 配下の関係をどうしますか？

A) `docs/` を正典として残し、`aidlc-docs/` からは参照するだけ（重複を作らない）

B) `aidlc-docs/` に集約し、`docs/` は削除する

C) `docs/` は公開用・人間向け、`aidlc-docs/` は AI-DLC のプロセス記録として両方維持する（内容の重複を許容）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 3
Laravel アプリケーションのファイルはどこに展開しますか？
（現在ワークスペース直下には `README.md` / `docs/` / `content/` / `CLAUDE.md` があり、
`laravel new` は非空ディレクトリへの展開になります）

A) ワークスペース直下に展開する（`app/` `config/` `routes/` 等がルートに並ぶ。AI-DLC の「アプリコードはルート」ルールに準拠）

B) サブディレクトリ（例: `app/` ではなく `src/` や `website/`）に展開し、ルートはドキュメント専用に保つ

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 4
PHP のバージョンをどれにしますか？（Sail のイメージと Bref のランタイム両方に影響します）

A) PHP 8.4（最新。Bref 3.0 対応）

B) PHP 8.3（実績重視）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 5
AWS へのデプロイ（Bolt B-2）に使う認証情報の準備状況は？

A) 準備済み（`aws configure` 済み、または環境変数で有効なクレデンシャルが使える）

B) 未準備（AWS アカウントはあるが、このマシンでの設定はこれから）

C) AWS アカウント自体がこれから

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 6
テストの方針はどうしますか？

A) 最小限（Markdown 読み込みなど中核ロジックのみ Pest でテスト）

B) 標準（中核ロジック + Inertia のページレンダリングを含む Feature テスト）

C) 手厚く（上記 + ブラウザテストで構成図アニメーションの動作確認まで）

D) テストは書かない（個人ポートフォリオのため）

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 7
Inertia の SSR（サーバサイドレンダリング）を導入しますか？
SSR 無しだと初期 HTML が空に近くなり、検索エンジンやリンクプレビューに内容が出ません。
一方で SSR は Lambda 上で Node プロセスを別途動かす必要があり、構成が重くなります。

A) SSR なし（クライアントレンダリングのみ。構成をシンプルに保つ）

B) SSR あり（SEO・OGP を優先）

C) SSR なし。ただし OGP・meta description は Blade 側で静的に出力する

X) Other (please describe after [Answer]: tag below)

[Answer]:C
ただし今後の展望としてはSSRアリもあり得る

---

## Question: Security Extensions
セキュリティ拡張ルールをこのプロジェクトに適用しますか？

A) はい — SECURITY ルールを全てブロッキング制約として強制する（本番相当のアプリケーション向け推奨）

B) いいえ — SECURITY ルールを全てスキップする（PoC・プロトタイプ・実験的プロジェクト向け）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question: Resiliency Extensions
レジリエンシ・ベースラインをこのプロジェクトに適用しますか？

**この拡張が何か**: 有効にすると、**AWS Well-Architected Framework（信頼性の柱）** とレジリエンス・レビュー
ガイダンスに由来する、**設計時のベストプラクティス指針**が適用されます。要件・設計・コードを、耐障害性・
高可用性・可観測性・復旧性の方向へ導きます（ビジネス目標、変更管理、可観測性、高可用性、災害復旧、
継続的改善にまたがる 15 の実践領域）。

**この拡張が何ではないか**: 有効にしても、ワークロードが本番対応になるわけではなく、可用性・RTO・RPO の
目標を認証・保証するものでもありません。あくまで良いレジリエンシ判断を早期に足場として組む**出発点**であり、
構築済みシステムに対する正式な **AWS Well-Architected レビュー**の代替ではありません。

A) はい — レジリエンシ・ベースラインを設計時の指針として適用する（ビジネスクリティカルなワークロード向け推奨）

B) いいえ — レジリエンシ・ベースラインをスキップする（PoC・プロトタイプ・実験的プロジェクト向け）

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question: Property-Based Testing Extension
プロパティベーステスト（PBT）のルールをこのプロジェクトに適用しますか？

A) はい — PBT ルールを全てブロッキング制約として強制ゼーション、状態を持つコンポーネントがあする（ビジネスロジック、データ変換、シリアライるプロジェクト向け推奨）

B) 部分的 — 純粋関数とシリアライゼーションの往復のみ PBT を強制する（アルゴリズム的複雑さが限定的なプロジェクト向け）

C) いいえ — PBT ルールを全てスキップする（単純な CRUD、UI のみのプロジェクト、ビジネスロジックの薄い連携層向け）

X) Other (please describe after [Answer]: tag below)

[Answer]:C

---
