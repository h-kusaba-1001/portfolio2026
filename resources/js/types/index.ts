/**
 * サーバから渡ってくる props の型。
 *
 * サーバ側の `PortfolioController::toProps()` と対応させる。
 * 片方だけを変更した場合にビルドで検出できるようにするのが、
 * TypeScript を採用した主な理由（ADR-006）。
 *
 * 定義の根拠:
 * aidlc-docs/construction/uow-2-content/functional-design/
 */

export type SectionId = 'stack' | 'experience' | 'career' | 'next';

/** H2 見出しと、その配下の本文の組 */
export type ContentBlock = {
    /** H2 の原文。画面に表示するのはこちら */
    heading: string;
    /**
     * 正規化済みの見出し。構成図ノードとの照合に使うのはこちら。
     * 正規化規則は business-rules.md R-2（前後空白の除去 →
     * 連続空白の畳み込み → ASCII 小文字化）。
     * フロント側で同じ規則を適用する必要がある点に注意。
     */
    key: string;
    html: string;
};

export type SectionProps = {
    id: SectionId;
    /** Markdown の H1 由来。無ければサーバ側の既定値 */
    title: string;
    /** false のとき、lead と blocks を無視して固定文言を表示する（Q6-a = A） */
    available: boolean;
    /**
     * 最初の H2 より前の本文（H1 は含まない）。
     * H2 を持たないセクション（experience / next）は、ここに全ての本文が入り
     * blocks が空配列になる。これは正常な状態で、失敗ではない。
     */
    lead: string;
    /** H2 ごとのブロック。0 個でもよい */
    blocks: ContentBlock[];
};

export type PortfolioPageProps = {
    sections: SectionProps[];
};

export type ErrorPageProps = {
    status: number;
};
