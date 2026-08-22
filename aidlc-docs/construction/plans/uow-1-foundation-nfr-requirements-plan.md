# NFR Requirements Plan — UoW-1（基盤構築）

**対象**: UoW-1（Sail + Laravel + Inertia + Tailwind + Bref + osls、デプロイ疎通まで）
**Bolt**: B-1, B-2

**前提の補足**: 本来 NFR Requirements は Functional Design の後に実行するが、
承認済みの実行計画（`aidlc-docs/inception/plans/execution-plan.md`）では
Functional Design を UoW-2 のみで実行する。UoW-1 は業務ロジックを持たない基盤構築のため、
Application Design の成果物を入力として本ステージを実行する。

**入力**: `aidlc-docs/inception/application-design/`、`docs/requirements.md`（NFR-1〜6, NFR-S1〜S7）、
`docs/architecture-decisions.md`（ADR-001〜010）

---

## Part 1: 確認事項（回答が必要）

`[Answer]:` に記号を記入してください。

---

## Question 1
AWS のリージョンをどこにしますか？

A) `ap-northeast-1`（東京）

B) `us-east-1`（バージニア北部。Lambda の単価がやや安く、CloudFront との相性の話題も多い）

C) `ap-northeast-3`（大阪）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 2
**CSP（Content-Security-Policy）の厳格度**をどうしますか？（CON-3 の解決）

NFR-S1 / SECURITY-04 は `unsafe-inline` を「文書化された正当化なしに使わない」と定めています。
一方、UoW-4 の構成図アニメーション（光の粒がノード間を流れる）は、
実装方法によって要素の `style` 属性を動的に書き換えます。
**`style-src` に `unsafe-inline` が無いと、`style` 属性による指定はブラウザに拒否されます。**

A) **厳格を維持する**。アニメーションは CSS アニメーション（スタイルシート内で定義）と
   SVG の `<animateMotion>` で実装し、`style` 属性を一切使わない。
   → CSP は `default-src 'self'` を維持。UoW-4 の実装方法に制約がかかる

B) **`style-src` にのみ `unsafe-inline` を許可**し、ADR に正当化を記録する。
   `script-src` は厳格なまま維持する。
   → UoW-4 の実装が自由になる。CSP の防御力は script 側で維持される

C) **nonce 方式**を導入する。Blade で nonce を発行し、許可する style にのみ付与する。
   → `style` 属性（インライン属性）には nonce を付けられないため、
     結局 A と同じ制約が残る点に注意

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 3
セキュリティヘッダ（NFR-S1）をどこで付与しますか？

A) Laravel のミドルウェア（`SecurityHeaders`）で付与する
   → コードとして残り、ローカルでも同じ挙動を確認できる。静的アセットには付かない

B) CloudFront の Response Headers Policy で付与する
   → 静的アセットを含む全レスポンスに付く。ローカル開発では確認できない

C) 両方（ミドルウェアで付与し、CloudFront でも同じポリシーを設定する）
   → 二重管理になるが、どちらの経路でも欠落しない

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 4
アプリケーションログ（NFR-S3 / SECURITY-03）の形式をどうしますか？

A) JSON 構造化ログ（Monolog の JsonFormatter）。CloudWatch Logs Insights で検索しやすい

B) Laravel のデフォルト（行ベース）のまま。設定を増やさない

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 5
Lambda のメモリサイズをどうしますか？
（Lambda の課金は「メモリ × 実行時間」。メモリを増やすと CPU も増え、実行時間が短くなるため、
必ずしも増やすほど高くなるわけではありません）

A) 512 MB（無料枠の消費が最も少ない。PHP の起動には十分）

B) 1024 MB（CPU 割り当てが増え、コールドスタートが短くなる傾向。標準的な選択）

C) 1792 MB（vCPU 1 個分。速度優先）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 6
デプロイに使う IAM 権限をどうしますか？（NFR-S4 / SECURITY-06「最小権限」との兼ね合い）

`osls deploy` は CloudFormation・Lambda・S3・CloudFront・API Gateway・IAM・CloudWatch Logs の
作成権限を必要とします。最小権限を厳密に追求すると、ポリシー作成に相当の試行錯誤が発生します。

A) 開発用 IAM ユーザーに `AdministratorAccess` を付与して進める。
   デプロイ用の絞り込みは行わず、その旨を ADR に記録する（**SECURITY-06 の例外として文書化が必要**）

B) デプロイ専用ポリシーを作成し、必要なサービスに限定する（リソースはワイルドカードを許容）。
   初回は権限不足で失敗しながら詰めていく

C) AWS IAM Identity Center（SSO）の PowerUserAccess + IAM 権限で運用する

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 7
依存の脆弱性スキャンと SBOM（NFR-S5 / SECURITY-10）をどう用意しますか？

A) ビルド手順に `composer audit` と `npm audit` を含める。SBOM は CycloneDX で生成する

B) GitHub の Dependabot を有効にする（リポジトリが公開されているため利用可能）。
   SBOM は GitHub の Dependency graph から出力する

C) 両方（ローカル/ビルド時のスキャン + Dependabot）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 8
**レート制限・濫用対策**（SECURITY-11）をどうしますか？

構成上、悪用された場合の主なリスクは情報漏洩ではなく **請求額の増加**です。
公開 API も認証もないため、攻撃者が取れるのは「大量にリクエストして課金を発生させる」ことです。

A) **AWS WAF を導入しない**。代わりに AWS Budgets で予算アラートを設定する（無料）。
   Lambda の同時実行数に上限を設定し、暴走時の上限を作る
   → NFR-1（月額 100 円以下）を維持できる

B) **AWS WAF を導入する**（CloudFront に関連付け、レート制限ルールを設定）
   → **月額 5〜8 ドル程度が固定で発生し、NFR-1 と正面から衝突する**

C) CloudFront のキャッシュ設定を強めることで Lambda への到達を減らし、
   追加コストなしで実質的な緩和とする（A と併用可能）

X) Other (please describe after [Answer]: tag below)

[Answer]:AとC

---

## Part 2: 実行ステップ（回答後に実施）

- [ ] 回答の分析（曖昧・矛盾がないか検証。あれば追加質問）
- [ ] `aidlc-docs/construction/uow-1-foundation/nfr-requirements/nfr-requirements.md` を生成
      （スケーラビリティ、性能、可用性、セキュリティ、信頼性、保守性、ユーザビリティの各要件）
- [ ] `aidlc-docs/construction/uow-1-foundation/nfr-requirements/tech-stack-decisions.md` を生成
      （技術選定とその根拠。ADR との対応）
- [ ] NFR-1（費用）との整合性を検証
- [ ] Security Compliance（SECURITY-01〜15）を評価
- [ ] 新たな技術的決定が生じた場合は `docs/architecture-decisions.md` に ADR を追加
- [ ] `aidlc-docs/aidlc-state.md` を更新
- [ ] `aidlc-docs/audit.md` に記録
