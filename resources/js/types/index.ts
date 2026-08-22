/**
 * サーバから渡ってくる props の型。
 *
 * サーバ側の `PortfolioController::toProps()` と対応させる。
 * 片方だけを変更した場合にビルドで検出できるようにするのが、
 * TypeScript を採用した主な理由（ADR-006）。
 *
 * 中身は UoW-2（コンテンツ基盤）で確定する。
 * UoW-1 時点では形だけを定義し、空配列が渡る。
 */

export type SectionId = 'stack' | 'experience' | 'career' | 'next';

/** content/stack.md の H2 見出しと本文の組（Q2 = A の規約対応の実体） */
export type ContentBlock = {
    heading: string;
    html: string;
};

export type SectionProps = {
    id: SectionId;
    title: string;
    /** false のとき、本文の代わりに固定文言を表示する（Q6-a = A） */
    available: boolean;
    blocks: ContentBlock[];
};

export type PortfolioPageProps = {
    sections: SectionProps[];
};

export type ErrorPageProps = {
    status: number;
};
