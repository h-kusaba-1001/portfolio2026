# Functional Design Plan — UoW-2（コンテンツ基盤）

**対象**: `content/*.md` → CommonMark → Inertia props（Bolt B-3）
**対応ストーリー**: US-7（コードを触らずにコンテンツを更新したい）

**入力**: `aidlc-docs/inception/application-design/`（components.md / component-methods.md /
services.md / component-dependency.md）、`content/*.md` の実データ

---

## 0. 実データの構造（確認済み）

設計時には見ていなかった、`content/` の実際の中身。

| ファイル | H1 | リード文（H2 より前の本文） | H2 の数 | H2 の内容 |
|---|---|---|---|---|
| `stack.md` | 技術構成 | あり（3 行） | **6** | CloudFront / API Gateway / Lambda (Bref) / Laravel + Inertia.js / デプロイ: osls / 拡張ポイント |
| `career.md` | キャリアの変遷 | あり（3 行） | **4** | 各社の経歴（構成図ノードとは無関係） |
| `experience.md` | やってきたこと（抜粋） | あり（箇条書き 10 行） | **0** | — |
| `next.md` | これから | あり（本文のみ） | **0** | — |

### ⚠️ 設計と実データの食い違い

**食い違い 1: H2 が無いファイルが 2 つある**

Application Design は `Section` を「`ContentBlock`（H2 見出し + HTML）の集まり」として設計した。
しかし `experience.md` と `next.md` には H2 が 1 つも無く、**このモデルでは中身が空になる。**

**食い違い 2: 「パース結果が空 = 失敗」の判定が誤作動する**

`component-methods.md` の `MarkdownContentRepository.find()` は
「パーサの結果が空配列なら `ContentUnavailable` を投げる」と定義している。
このままだと **`experience.md` と `next.md` が正常なのに「読み込めませんでした」と表示される。**

**食い違い 3: リード文の置き場所がない**

全ファイルに H1 直後のリード文がある。特に `stack.md` のリード文
（「このサイトは AWS Lambda の上で動く Laravel です…」）は、
構成図の上に出したい導入文にあたる。現行モデルには入れる場所がない。

以下の質問で、この 3 点を解消する。

---

## Part 1: 確認事項（回答が必要）

## Question 1
`Section` のモデルをどうしますか？（食い違い 1 と 3 の解消）

A) **`lead` を追加する**
   ```
   Section {
     id, title,
     lead:   string   // 最初の H2 より前の HTML（H1 は除く）
     blocks: ContentBlock[]   // H2 ごと。無ければ空配列
   }
   ```
   - `experience.md` / `next.md` は `lead` だけを持ち、`blocks` は空になる
   - `stack.md` のリード文が構成図の導入文として使える
   - **実データの形をそのまま表現できる**

B) **H2 が無い場合は、本文全体を見出し空の 1 ブロックにする**
   ```
   Section { id, title, blocks: [{ heading: '', html: '...' }] }
   ```
   - モデルは単純なまま
   - リード文と H2 ブロックが混在するファイル（`stack.md` / `career.md`）では、
     リード文の置き場所が依然として無い

C) **`content/*.md` の側を書き換えて、全ファイルに H2 を持たせる**
   - モデルを変えずに済む
   - コンテンツの書き方に制約がかかる（US-7 の趣旨と逆行する）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

とりあえずAで動作を見たい

---

## Question 2
セクションの見出し（画面に出すタイトル）はどこから取りますか？

A) **Markdown の H1 から取る**（`# キャリアの変遷` → `キャリアの変遷`）
   - タイトルも Markdown 編集だけで変えられる（US-7 に沿う）
   - H1 が無いファイルではフォールバックが必要

B) **`SectionId` enum が持つ固定値を使う**
   - コードで一元管理できる
   - タイトル変更にコード修正が必要になる（US-7 の対象外になる）

C) **H1 があればそれを使い、無ければ enum の値にフォールバックする**

X) Other (please describe after [Answer]: tag below)

[Answer]:C

---

## Question 3
本文の HTML に H1 を含めますか？

A) **含めない**（H1 は取り除き、タイトルとしてのみ使う）
   - 画面側がセクション見出しを組み立てるため、二重に出ない

B) **含める**（Markdown をそのまま HTML 化する）
   - 画面側でタイトルを別途出すと**見出しが 2 回表示される**

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 4
「読み込み失敗」と判定する条件をどうしますか？（食い違い 2 の解消）

現行の設計（`component-methods.md`）は「パース結果が空配列なら失敗」。
このままだと H2 の無い `experience.md` / `next.md` が誤って失敗扱いになります。

A) **ファイルが存在しない / 読めない場合のみ失敗**
   - 中身が空でもセクションは表示される（空欄になる）

B) **ファイルが無い、または本文（H1 を除いた中身）が空の場合に失敗**
   - 空ファイルを置いてしまった事故に気付ける

C) 上記に加えて、**HTML 変換で例外が出た場合**も失敗に含める

X) Other (please describe after [Answer]: tag below)

[Answer]:C

---

## Question 5
構成図ノードと突き合わせる **H2 見出しの正規化**をどうしますか？

`stack.md` の見出しには `Lambda (Bref)` や `デプロイ: osls` のように
記号・空白・英数字が混ざっています。フロント側の `DiagramNodeDef.heading` と
文字列一致させる際、表記ゆれをどこまで吸収するか。

A) **完全一致**（前後の空白のみ除去）
   - 単純で予測可能。見出しを 1 文字でも変えると対応が切れる

B) **前後空白の除去に加えて、全角/半角スペースと大文字小文字の差を吸収する**
   - 多少の揺れに耐える。吸収ルール自体が新たな仕様になる

C) **見出しから ID を生成して突き合わせる**（例: `Lambda (Bref)` → `lambda-bref`）
   - 記号や大小文字の揺れに強い
   - 生成規則を実装とフロントの両方で一致させる必要がある

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 6
`content/` に `SectionId` enum に無いファイルが置かれた場合の扱いは？

A) **無視する**（enum に定義されたファイルだけを読む）

B) **無視するが、警告としてログに残す**

C) 自動的にセクションとして追加する（表示順が不定になる）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 7
キャッシュ（Q3 = B で決定済み）の粒度と保持時間をどうしますか？

A) **セクション単位**でキャッシュし、TTL は設けない（ファイル更新時刻がキーに入るため自動失効）
   - Application Design の設計どおり

B) セクション単位 + TTL を明示的に設ける（例: 1 時間）

C) ページ全体（`PortfolioContent`）を 1 つのキーでキャッシュする
   - キャッシュ読み書きが 1 回で済む
   - 1 ファイルの更新で全体が失効する

X) Other (please describe after [Answer]: tag below)

[Answer]:A

ただし、APIで一括でページ全体を取っているのであればCでOKです

---

## Part 2: 実行ステップ（回答後に実施）

- [x] 回答の分析（曖昧・矛盾がないか検証。あれば追加質問）
      → Q7 の条件付き回答（「一括取得なら C でよい」）を評価。条件は満たされるが、
        失敗をキャッシュしない規則と承認済みの層設計を保つため **A を採用**（理由は business-logic-model.md §5）
- [x] `aidlc-docs/construction/uow-2-content/functional-design/domain-entities.md` を生成
      （`SectionId` / `Section` / `ContentBlock` / `PortfolioContent` の確定形）
- [x] `aidlc-docs/construction/uow-2-content/functional-design/business-logic-model.md` を生成
      （Markdown → HTML 変換のアルゴリズム、props への写像、キャッシュの流れ）
- [x] `aidlc-docs/construction/uow-2-content/functional-design/business-rules.md` を生成
      （分割規則、失敗判定、正規化規則、順序規則）
- [x] Application Design の `component-methods.md` を、確定内容に合わせて更新
- [x] `resources/js/types/index.ts` の型定義を更新（`lead` と `key` を追加。型チェック通過）
- [x] Security Compliance を評価 → 準拠 5 / N/A 10 / ブロッキング所見なし
- [x] `aidlc-docs/aidlc-state.md` と `aidlc-docs/audit.md` を更新

**フロントエンド成果物について**: UoW-2 は UI コンポーネントを持たない
（`MarkdownBlock` などは UoW-3 の担当）。そのため `frontend-components.md` は作成せず、
props の契約は `business-logic-model.md` に記載する。

---

## 引き継ぎ中の未解決事項（UoW-1 から）

- **P-2**: HTML が CloudFront にキャッシュされない（V-5 未達）。**UoW-2 の範囲外だが未解決のまま**
- **V-7**: アプリログの JSON 形式が本番で未確認
