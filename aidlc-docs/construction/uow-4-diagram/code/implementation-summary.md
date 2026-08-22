# Implementation Summary — UoW-4（構成図 + デザイン刷新）

**実施日**: 2026-08-22
**状態**: **完了。本番反映済み** — https://d3bttkxchvfb66.cloudfront.net

**回答**: Q1 = A（`stack.md` に S3 を追加）/ Q2 = A（`<animateMotion>`）/ Q3 = A（図の下に固定枠）

---

## 1. 生成したファイル

| パス | 内容 |
|---|---|
| `lib/headingKey.ts` | **PHP の `HeadingKey` と対になる正規化**。対であることをコメントに明記 |
| `components/diagram/nodes.ts` | ノード 7 / エッジ 6。**横並びと縦積みの座標を両方持つ** |
| `components/diagram/ArchitectureDiagram.tsx` | SVG 本体。画面幅で 2 レイアウトを出し分け |
| `components/diagram/DiagramNode.tsx` | ノード 1 つ。見出しを持つものだけ押せる |
| `components/diagram/FlowParticle.tsx` | 光の粒。`<animateMotion>` のみ |
| `components/diagram/NodePanel.tsx` | 選定理由の表示。不一致時は固定文言 |
| `components/layout/SectionLead.tsx` | 「結論を一言」（D-1） |
| `components/sections/Stack.tsx` | 構成図ブロック（最上部） |
| `components/sections/TradeOffs.tsx` | **選ばなかったもの**（S-2 の核 / D-3） |
| `tests/Feature/DiagramNodesTest.php` | ノードと `stack.md` の対応検証 |

**変更**: `content/stack.md`（`## S3` 追加）、`Hero` / `Experience` / `Career` / `Next` / `Portfolio.tsx`、
`app.css`（図用の色トークン）、`ContentPipelineTest`（6 → 7 ブロック）

---

## 2. 検証結果

| 項目 | 結果 |
|---|---|
| テスト | **30 passed（168 assertions）** |
| 型チェック / ビルド | 通過（JS 330 kB / CSS 42 kB） |
| デプロイ | **54 秒** |
| 本番 | 200 / 4 セクション / ブロック 7 件 |

---

## 3. Q2 = A の効果（記録）

粒のアニメーションを `<animateMotion>` だけで実装したため、
**JavaScript も `style` 属性の書き換えも使っていない。**

ADR-011 で `style-src 'unsafe-inline'` を許可した理由は
「構成図アニメーションが `style` 属性を書き換えるため」だったが、
**実装してみるとその必要が無かった。**

→ `docs/backlog.md` に「CSP の `style-src` から `'unsafe-inline'` を外せるか検証する」を追加。
ただし Tailwind や React が別の箇所でインラインスタイルを使っている可能性があるため、
**外す前に実際のページで検証が必要。**

---

## 4. デザイン刷新（D-1〜D-4）

| # | 対応 |
|---|---|
| D-1 | 各セクションに `SectionLead`（結論を一言 + eyebrow）を追加 |
| D-2 | レイアウトを変えた: 技術構成 = 図 + パネル / 選ばなかったもの = カード 2 列 / やってきたこと = 枠付き / キャリア = 年表 / これから = 左右分割 |
| D-3 | 「選ばなかったもの」を 6 枚のカードで可視化（取り消し線 + 理由） |
| D-4 | 構成図を Hero 直下の最上部に配置 |

**新設**: 「選ばなかったもの」セクション。
`docs/requirements.md` §1 のゴール「技術力の証明を『作れる』ではなく『選べる』に置く」に直結する。

---

## 5. 未確認事項

| # | 内容 |
|---|---|
| **目視確認** | 構成図の見え方、粒の速度、モバイル（375px）の縦積みレイアウトは**目視していない**。ブラウザテストを書かない方針（ADR-009）のため、**人の目での確認が必要** |
| 拡張ポイントの表現 | 現状は破線のノード 1 つ + パネル内のバッジ。図中に個別要素を描いていない |
