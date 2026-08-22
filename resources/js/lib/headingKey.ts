/**
 * 見出しを照合用のキーに正規化する。
 *
 * ⚠️ **PHP の `App\Domain\Content\HeadingKey` と同じ規則を実装している。**
 *    サーバが返す `ContentBlock.key` と、ここで正規化した構成図ノードの見出しを
 *    突き合わせるため、**片方だけ変更すると実行時まで気付けない不一致が生まれる。**
 *
 *    規則の正典: aidlc-docs/construction/uow-2-content/functional-design/business-rules.md R-2
 *    ずれていないことは `tests/Feature/DiagramNodesTest.php` で検証している。
 *
 * 規則（適用順）
 *   1. 前後の空白を除去（半角空白類 + 全角空白 U+3000）
 *   2. 連続する空白を半角空白 1 つに畳む
 *   3. ASCII の英字のみ小文字化（日本語やその他の文字は変えない）
 */
const WHITESPACE = /[\s　]+/g;
const EDGE_WHITESPACE = /^[\s　]+|[\s　]+$/g;

export function headingKey(heading: string): string {
    return heading
        .replace(EDGE_WHITESPACE, '')
        .replace(WHITESPACE, ' ')
        .replace(/[A-Z]/g, (char) => char.toLowerCase());
}
