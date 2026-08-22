# Implementation Summary — UoW-3（静的セクション）

**実施日**: 2026-08-22
**状態**: **完了。本番反映済み** — https://d3bttkxchvfb66.cloudfront.net

**確認事項の回答**: Q1 = C（自動 + 手動トグル）/ Q2 = C（緑系）/ Q3 = C（上部固定ヘッダ）

---

## 1. 生成したファイル

| パス | 内容 |
|---|---|
| `resources/css/app.css` | 色トークン（3 段構成）、`.prose-basic`、フェードイン |
| `hooks/usePrefersReducedMotion.ts` | 動きの抑制設定の検出。**初期値は true**（判定前に動かさない） |
| `hooks/useTheme.ts` | テーマ切り替え。`localStorage` の例外も握る |
| `components/layout/Section.tsx` | セクション枠。IntersectionObserver でフェードイン |
| `components/layout/ThemeToggle.tsx` | ライト / 自動 / ダーク の 3 択 |
| `components/content/MarkdownBlock.tsx` | **`dangerouslySetInnerHTML` をここ 1 箇所に閉じ込める** |
| `components/content/ContentUnavailable.tsx` | 失敗時の固定文言 |
| `components/ui/GitHubLink.tsx` | リポジトリリンク（`rel="noopener noreferrer"`） |
| `components/sections/Hero.tsx` | S-1。文言はコンポーネントに直接記述（Q1 = B） |
| `components/sections/Experience.tsx` | S-3。lead のみ |
| `components/sections/Career.tsx` | S-4。lead + 4 ブロックを縦の年表として表示 |
| `components/sections/Next.tsx` | S-5。lead のみ |
| `pages/Portfolio.tsx` | 固定ヘッダ + Hero + 4 セクション + フッタ |
| `tests/Feature/SectionsRenderTest.php` | UoW-3 のテスト 4 件 |

---

## 2. 検証結果

| 項目 | 結果 |
|---|---|
| テスト | **27 passed（159 assertions）** |
| `tsc --noEmit` | エラーなし |
| `npm run build` | 成功（JS 321.22 kB / CSS 40.80 kB） |
| デプロイ | **84 秒**（`./bin/deploy.sh`。trap による開発依存の復元も動作） |
| 本番 | 200 / 4 セクション全て `available: true` |

---

## 3. 判断したこと

### ダークモードのちらつき（CSP との関係）

CSP が `script-src 'self'` のためインラインスクリプトを使えず、
テーマを**明示指定した利用者**は初回描画時に一瞬ちらつく可能性がある。

- 既定（`system`）の利用者はちらつかない。CSS の `prefers-color-scheme` だけで切り替わるため
- 明示指定した場合のみ、JS 読み込み後にクラスが付く
- 解消するには nonce の導入が必要（ADR-011 の代償）

**現状は許容する。** 大多数は既定のままであり、影響は限定的。

### `@tailwindcss/typography` を入れなかった

Markdown 由来の HTML に必要な要素は段落・箇条書き・リンク・強調・コード・H3 のみ。
依存を 1 つ増やすより、`.prose-basic` として 60 行ほど書く方が軽い（ADR-004 の判断基準）。

### `usePrefersReducedMotion` の前倒し

Application Design では UoW-4 の担当としていたが、
スクロールフェードイン（UoW-3）に必要なため前倒しした。UoW-4 はこれを再利用する。

---

## 4. B-4 の完了判定について

**構造的には満たしている**（モバイル幅を基準に組み、本文の行長を `max-width: 42rem` に制限、
固定ヘッダのナビはデスクトップのみ表示、タップ領域は 44px 相当を確保）。

ただし **375px 幅での実際の見え方は、私が目視で確認できていない。**
ブラウザテストを書かない方針（ADR-009）のため、**人の目での確認が必要**。

---

## 5. 次のユニットへの引き渡し

| 引き渡すもの | 状態 |
|---|---|
| `Section` / `MarkdownBlock` / `ContentUnavailable` | UoW-4 の構成図セクションでも再利用する |
| `usePrefersReducedMotion` | UoW-4 のアニメーション制御で使う |
| `pages/Portfolio.tsx` の `StackPlaceholder` | **UoW-4 で `ArchitectureDiagram` に差し替える** |
| 色トークン | `--accent` は構成図のノード配色にも使える |
