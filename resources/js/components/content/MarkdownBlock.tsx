/**
 * 変換済み HTML を描画する。
 *
 * `dangerouslySetInnerHTML` の使用箇所を**このコンポーネント 1 つに閉じ込める**。
 * 危険な要素の除去は変換元（CommonMark の html_input = strip）で行っており、
 * ここは描画に徹する（business-rules.md R-4）。
 */
export default function MarkdownBlock({ html }: { html: string }) {
    return <div className="prose-basic" dangerouslySetInnerHTML={{ __html: html }} />;
}
