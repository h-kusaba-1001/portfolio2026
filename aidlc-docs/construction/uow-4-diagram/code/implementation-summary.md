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

---

## 6. 図の作り直し（2026-08-23。レビュー指摘への対応）

**指摘**: 「AWS 公式のアイコンを使ってほしい」「絵が思ったより地味」
「図とその中の文字をより派手に」「大きさはクラスメソッドのブログを参考に」

### 6-1. AWS 公式アイコンの導入

[AWS Architecture Icons](https://aws.amazon.com/architecture/icons/) の公式パッケージ
（`Icon-package_07312026`）から 4 種を取り出し、`public/aws-icons/` に配置した。

| ファイル | 元 |
|---|---|
| `cloudfront.svg` | `Arch_Networking-Content-Delivery/64/Arch_Amazon-CloudFront_64.svg` |
| `api-gateway.svg` | `Arch_Networking-Content-Delivery/64/Arch_Amazon-API-Gateway_64.svg` |
| `lambda.svg` | `Arch_Compute/64/Arch_AWS-Lambda_64.svg` |
| `s3.svg` | `Arch_Storage/64/Arch_Amazon-Simple-Storage-Service_64.svg` |

**自前ホストしている理由**: CSP が `img-src 'self' data:` のため、
外部から読み込むと表示されない（SECURITY-13 の方針とも一致）。

**ライセンスについて**: AWS は「アーキテクチャ図の作成」における利用を許諾している。
アイコンは改変せず、公式パッケージのまま配置している。
**利用条件の最終的な確認は AWS の規約に従うこと。**

### 6-2. ⚠️ 踏んだ問題: アイコンが本番で 404 になった

`public/aws-icons/` に置いてデプロイしたが、本番で **404**。

**原因**: Lift の `assets` は `/build/*` しか S3 に配信しない。
`/aws-icons/*` は既定ビヘイビアで Lambda に流れ、Bref の FPM ハンドラが
静的ファイルを返さないため 404 になっていた。

**対処**: `serverless.yml` の `assets` に `'/aws-icons/*': public/aws-icons` を追加。

**教訓**: **`public/` に置いただけでは本番で配信されない。**
Lift の `assets` に明示的にマッピングする必要がある。

### 6-3. 図の強調

| 変更 | 内容 |
|---|---|
| ノードサイズ | 150×62 → **180×108**（縦積みは 190×100） |
| 図の領域 | 860×430 → **1040×580** |
| ラベル | 13px → **16px / 太字 700** |
| 補足 | 10px → **12px** |
| サービス色の帯 | ノード上端に AWS のカテゴリ色（CloudFront/API GW = 紫、Lambda = 橙、S3 = 緑、Laravel = 赤） |
| 線 | 1.5px → **2.5px**、**矢印マーカーを追加** |
| 光の粒 | 半径 4 → **7**、**glow フィルタで発光** |
| 選択時 | 外周にサービス色のリングを追加 |

### 6-4. 文字サイズ（技術ブログ相当）

| 対象 | 変更 |
|---|---|
| 本文 | 16px（sm 以上で 17px）/ 行間 1.9 |
| セクションの結論 | text-xl → **text-2xl（sm 以上 text-3xl）/ 太字 700** |
| eyebrow | text-xs → **text-sm / 太字** |
| 本文中の H3 | 18px / 太字 700 |
