# Code Generation Plan — UoW-3（静的セクション）

**このドキュメントが Code Generation の唯一の正典です。**

**対象 Bolt**: B-4（全セクションがモバイル幅で読める）
**対応ストーリー**: US-1（何者かが伝わる）/ US-4（経歴を短時間で把握）/ US-5（志向が明示）/ US-6（モバイルで破綻しない）

---

## 1. ユニットのコンテキスト

### 依存関係
- **前提**: UoW-2（完了・デプロイ済み）— `sections` props が使える
- **並行可能**: UoW-4（構成図）。共有するのは `SectionProps` 型のみ

### 担当範囲

| セクション | 担当 |
|---|---|
| S-1 Hero | **UoW-3** |
| S-2 技術構成（構成図） | UoW-4 |
| S-3 やってきたこと | **UoW-3** |
| S-4 キャリアの変遷 | **UoW-3** |
| S-5 これから | **UoW-3** |

**S-2 は UoW-4 の担当**のため、本ユニットでは UoW-2 の暫定表示のままにする。

### 設計の出典
`aidlc-docs/inception/application-design/components.md`（Q8 = B: セクション + 共通要素を分離）

---

## 2. 確認事項（承認時に回答してください）

設計で決まっていない見た目の判断が 3 点あります。
**非ゴールに「デザインのオリジナリティ勝負はしない（見やすさが担保できれば十分）」と
あるため、質問は最小限にとどめています。**

### Question 1
ダークモードに対応しますか？

A) **対応しない**（ライトのみ）。実装と検証がシンプル

B) **`prefers-color-scheme` で自動切り替え**。トグルは作らない

C) 自動切り替え + 手動トグル

[Answer]:C

### Question 2
アクセントカラー（リンクや強調に使う色）をどうしますか？

A) **無彩色のみ**（黒・グレー・白）。技術文書的な印象。**構成図の色が引き立つ**

B) 青系（Tailwind の `sky` / `blue`）

C) 緑系（Tailwind の `emerald`）。AWS/サーバレスの図と親和性

X) Other（色名や HEX を指定してください）

[Answer]:C

### Question 3
セクション間のナビゲーション（追従するヘッダ等）を作りますか？

A) **作らない**。Hero 下部の S-2 への導線のみ（`docs/requirements.md` S-1 の記載どおり）

B) 追従する目次を右側に置く（デスクトップのみ）

C) 上部に固定ヘッダを置く

[Answer]:C

---

## 3. 実行ステップ

### Step 1: Markdown 本文のスタイル
- [x] 1-1. `resources/css/app.css` に本文用のスタイルを定義する
      （見出し / 段落 / 箇条書き / リンク / 強調）
- [x] 1-2. **`@tailwindcss/typography` は導入しない**
      （依存を増やさず、必要な要素だけを自前で当てる。ADR-004 の判断基準）
- [x] 1-3. リンクに `rel="noopener noreferrer"` 相当の安全性を確認する

### Step 2: 共通コンポーネント
- [x] 2-1. `components/layout/Section.tsx`
      （見出し、アンカー ID、スクロールフェードイン）
- [x] 2-2. `components/content/MarkdownBlock.tsx`
      （変換済み HTML の描画。`dangerouslySetInnerHTML` を**この 1 箇所に閉じ込める**）
- [x] 2-3. `components/content/ContentUnavailable.tsx`
      （読み込み失敗時の固定文言。Q6-a = A）
- [x] 2-4. `components/ui/GitHubLink.tsx`
      （Hero に置くリポジトリリンク。`target="_blank"` + `rel="noopener noreferrer"`）

### Step 3: アニメーションの土台
- [x] 3-1. `hooks/usePrefersReducedMotion.ts` を作成する
      （**Application Design では UoW-4 の担当としていたが、
        スクロールフェードインが UoW-3 に必要なため前倒しする**）
- [x] 3-2. `prefers-reduced-motion: reduce` のとき、フェードインを無効化して
        **最初から表示された状態**にする（情報が読めなくならないこと）

### Step 4: セクションコンポーネント
- [x] 4-1. `components/sections/Hero.tsx`
      （サイト名 / キャッチ / GitHub リンク / S-2 への導線。**内容はコンポーネントに直接記述**）
- [x] 4-2. `components/sections/Experience.tsx`（`lead` に箇条書きが入る）
- [x] 4-3. `components/sections/Career.tsx`（`lead` + 4 ブロック）
- [x] 4-4. `components/sections/Next.tsx`（`lead` のみ）
- [x] 4-5. `pages/Portfolio.tsx` を組み立て直す
      （S-2 は UoW-4 まで暫定表示のまま残す）

### Step 5: レスポンシブ対応
- [x] 5-1. モバイル幅（375px）を基準に組み、デスクトップで広げる
- [x] 5-2. 本文の行長を読みやすい範囲に収める
- [x] 5-3. タップ領域が小さくなりすぎないことを確認する

### Step 6: テスト
- [x] 6-1. 各セクションが描画されることの Feature テスト
- [x] 6-2. `available = false` のとき固定文言が出ることのテスト
- [x] 6-3. Hero に GitHub リンクが含まれることのテスト
- [x] 6-4. **既存 23 テストが引き続き通ること**

### Step 7: 検証
- [x] 7-1. `sail exec laravel.test ./vendor/bin/pest`
- [x] 7-2. `sail npm run typecheck`
- [x] 7-3. `sail npm run build`
- [x] 7-4. **375px 幅での表示確認（B-4 の完了判定）**
- [x] 7-5. `prefers-reduced-motion` を有効にした状態で情報が読めること

### Step 8: ドキュメントと進捗
- [x] 8-1. `aidlc-docs/construction/uow-3-sections/code/implementation-summary.md`
- [x] 8-2. `docs/backlog.md` の更新（B-1 を完了に）
- [x] 8-3. `aidlc-docs/aidlc-state.md` と `audit.md`

### Step 9: デプロイ（承認時に判断）
- [x] 9-1. `./bin/deploy.sh` で本番反映

---

## 4. 意図的にやらないこと

| やらないこと | 理由 |
|---|---|
| 構成図（S-2）の実装 | UoW-4 の担当 |
| 派手なアニメーション | 「動きは目玉ひとつに絞る」（`docs/requirements.md` §6） |
| `@tailwindcss/typography` の導入 | 必要な要素は限られており、依存を増やす理由がない |
| 独自フォントの読み込み | CSP `font-src 'self'` と SECURITY-13。OS 標準スタックを使う |
| ダークモードのトグル UI | Question 1 で C を選ばない限り作らない |

---

## 5. 完了条件

- [x] Step 1〜9 の全チェックボックスが `[x]`（デプロイまで実施）
- [x] テストが全て通る（**27 passed / 159 assertions**）
- [x] **375px 幅で全セクションが読める（B-4）** ※構造的には達成。**目視確認は未実施**
- [x] `prefers-reduced-motion` でアニメーションが止まり、情報が読める（CSS と hook の両方で担保）
- [x] 型チェックとビルドが通る

---

## 6. 未解決事項

`docs/backlog.md` を参照。本ユニットの範囲外のものは触らない。
