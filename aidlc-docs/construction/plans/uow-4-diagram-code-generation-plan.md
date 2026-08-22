# Code Generation Plan — UoW-4（構成図アニメーション + デザイン刷新）

**このドキュメントが Code Generation の唯一の正典です。**

**対象 Bolt**: B-5（構成図が動き、ノードから選定理由が開く）★最重要
**対応ストーリー**: US-2（技術構成と選定理由で技術力を判断したい）/ US-3（リクエストの流れを直感的に理解したい）

**含めるもの**: `docs/backlog.md` §3.5 の **D-1〜D-4（デザインの作り直し）**
`docs/requirements.md` §5.5 に要件として反映済み。

---

## 1. このユニットが最重要である理由

`docs/aidlc-inception.md` §4 に「**B-5 に最も時間を配分すること。ここが訴求の中核であり、他は手段**」と書いた。
ここまでの UoW-1〜3 は全てこのユニットのための土台。

加えて初回デプロイ後のレビューで「テキストが多すぎて分かりにくい」という指摘を受けたため、
**構成図の実装とデザインの作り直しを同時に行う。**

---

## 2. ⚠️ 最優先の技術的リスク

`content/stack.md` の H2 見出しと構成図ノードの照合は、
**正規化規則（business-rules.md R-2）をサーバ（PHP）とフロント（TypeScript）の 2 箇所に持つ。**
ずれても実行時まで気付けない。

**現在の `stack.md` のキー**

```
cloudfront / api gateway / lambda (bref)
laravel + inertia.js / デプロイ: osls / 拡張ポイント
```

**必ず実施する 3 点**（`docs/backlog.md` §6）

1. 全ノードの `key` が `stack.md` の `blocks` に実在することを検証するテスト
2. 一致しないノードでは固定文言を表示し、**画面を壊さない**
3. ノード定義を PHP からも読める形に置くことを検討する

---

## 3. 確認事項（承認時に回答してください）

### Question 1
構成図のノードと `stack.md` の H2 は、きれいに 1 対 1 になりません。

| 図に出すノード | 対応する H2 | 備考 |
|---|---|---|
| Browser | **なし** | 説明パネルなし |
| CloudFront | `cloudfront` | ✓ |
| S3（静的アセット） | **なし** | CloudFront の説明に含まれている |
| API Gateway | `api gateway` | ✓ |
| Lambda (Bref) | `lambda (bref)` | ✓ |
| Laravel + Inertia | `laravel + inertia.js` | ✓ |
| — | `デプロイ: osls` | **リクエストの流れではない**（デプロイの話） |
| 拡張ポイント（破線） | `拡張ポイント` | ✓ |

`デプロイ: osls` と S3 の扱いをどうしますか？

A) **`content/stack.md` に H2 を足す**（`## S3` を追加し、`## デプロイ: osls` は
   図の外に「デプロイ」として別枠で表示する）

B) **図のノードを増やす**（S3 と osls も図に描き、osls はデプロイ経路として別の線で表現）

C) **パネルを持たないノードを許容する**（S3 はクリックしても何も出ない。osls は図に出さない）

[Answer]:A

### Question 2
アニメーション（光の粒）の実装方式をどうしますか？

A) **SVG の `<animateMotion>`**（宣言的。JS 不要。CSP と相性が良い）

B) **CSS アニメーション**（`offset-path` でパスに沿わせる）

C) **JS で毎フレーム座標を更新**（自由度が高いが、`style` 属性を書き換えるため
   ADR-011 で `style-src 'unsafe-inline'` を許可した理由そのもの）

[Answer]:A

### Question 3
ノードの説明パネルの出し方をどうしますか？

A) **図の下に固定枠**を設け、選択中のノードの説明をそこに表示する（レイアウトが動かない）

B) **ノードの近くにポップオーバー**で出す（位置関係が分かりやすいが、モバイルで窮屈）

C) **モバイルは下部シート、デスクトップは右側パネル**（画面幅で出し分ける）

[Answer]:A

---

## 4. 実行ステップ

### フェーズ A: 構成図の土台

- [ ] A-1. `resources/js/components/diagram/nodes.ts`
      （ノード定義: `id` / `label` / `key` / 座標 / 接続先 / 種別）
- [ ] A-2. `resources/js/lib/headingKey.ts`
      （**PHP の `HeadingKey` と同じ正規化規則**。対になることをコメントに明記）
- [ ] A-3. `components/diagram/ArchitectureDiagram.tsx`（SVG 本体。座標は直書き: Q7 = A）
- [ ] A-4. `components/diagram/DiagramNode.tsx`（ノード 1 つの描画と選択状態）
- [ ] A-5. モバイルでの表示（横スクロールさせるか、縦積みに切り替えるか）

### フェーズ B: アニメーションと相互作用

- [ ] B-1. `components/diagram/FlowParticles.tsx`（光の粒。ループ）
- [ ] B-2. `prefers-reduced-motion` で粒を止め、**経路は静的に見える**ようにする
- [ ] B-3. `components/diagram/NodePanel.tsx`（選定理由の表示。`key` で `blocks` を引く）
- [ ] B-4. 不一致時のフォールバック表示
- [ ] B-5. `components/diagram/ExtensionPoints.tsx`（破線。DynamoDB / SQS / X-Ray / SSR / オリジン遮断）

### フェーズ C: デザインの作り直し（D-1〜D-4）

- [ ] C-1. **D-4**: Hero と構成図を最上部で一体化する
- [ ] C-2. **D-1**: 各セクションに「結論を一言」の見出しを足す
      （文言は React に直書き。`content/*.md` の本文は補足として残す）
- [ ] C-3. **D-2**: セクションごとにレイアウトを変える
      （技術構成 = 図 + パネル / やってきたこと = カード / キャリア = 年表 / これから = 左右分割）
- [ ] C-4. **D-3**: 「選ばなかったもの」を図または表で見せる（S-2 の核）
- [ ] C-5. 固定ヘッダのナビを新しい構成に合わせる

### フェーズ D: テストと検証

- [ ] D-1t. **全ノードの `key` が `stack.md` に実在することのテスト**（§2 の 1）
- [ ] D-2t. `headingKey.ts` と PHP の `HeadingKey` が同じ結果を返すことのテスト
- [ ] D-3t. 既存 27 テストが通ること
- [ ] D-4t. 型チェック・ビルド
- [ ] D-5t. `prefers-reduced-motion` で情報が読めること
- [ ] D-6t. 375px 幅での確認

### フェーズ E: 仕上げ

- [ ] E-1. `aidlc-docs/construction/uow-4-diagram/code/implementation-summary.md`
- [ ] E-2. `docs/backlog.md` の更新（B-2 と D-1〜D-4 を完了に）
- [ ] E-3. `aidlc-docs/aidlc-state.md` と `audit.md`
- [ ] E-4. デプロイ（`./bin/deploy.sh`）

---

## 5. 意図的にやらないこと

| やらないこと | 理由 |
|---|---|
| アニメーションライブラリの導入 | SVG と CSS で足りる。バンドルを増やさない |
| 外部画像の読み込み | CSP `img-src 'self' data:` / SECURITY-13 |
| 動きを他セクションに広げる | 「目玉は一つに絞る」（`docs/requirements.md` §6） |
| ブラウザテスト | ADR-009。目視確認で代替する |

---

## 6. 完了条件

- [ ] 構成図が**ページ最上部**にあり、リクエストの流れが動く
- [ ] 各ノードをクリック / タップすると `stack.md` の内容が開く
- [ ] 拡張ポイントが破線で示されている
- [ ] `prefers-reduced-motion` でアニメーションが止まり、情報が読める
- [ ] 各セクションが「結論を一言」→「補足」の 2 段になっている
- [ ] セクションごとにレイアウトが変えてある
- [ ] テスト・型チェック・ビルドが通る
