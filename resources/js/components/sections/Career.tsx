import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import SectionLead from '@/components/layout/SectionLead';
import type { ContentBlock, SectionProps } from '@/types';

/**
 * S-4 キャリアの変遷。
 *
 * **content/career.md に書かれた順にそのまま並べる。** ここでは並べ替えない。
 * 表示は上から順になるため、**Markdown 側を最新が先頭になるように書くこと。**
 *
 * 時間の流れは、丸の大きさの遠近感と**上向きの矢印**で表す。
 * 先頭（＝最新）ほど大きく・濃く、下にいくほど小さく・淡くなる。
 * 最新が上に来る並びのため、時間は下（過去）から上（現在）へ流れる。
 */
export default function Career({ section }: { section: SectionProps }) {
    if (!section.available) {
        return (
            <section className="max-w-3xl" id="career" data-testid="section-career">
                <SectionLead eyebrow="キャリアの変遷">
                    これまでの転職理由と経験です。
                </SectionLead>
                <div className="mt-8">
                    <ContentUnavailable sectionId={section.id} />
                </div>
            </section>
        );
    }

    const entries = section.blocks;

    return (
        <section className="max-w-3xl" id="career" data-testid="section-career">
            <SectionLead eyebrow="キャリアの変遷">
                これまでの転職理由と経験です。
            </SectionLead>

            {section.lead !== '' && (
                <div className="mt-8">
                    <MarkdownBlock html={section.lead} />
                </div>
            )}

            <ol className="mt-12" data-testid="career-timeline">
                {entries.map((entry, index) => (
                    <Entry
                        key={entry.key}
                        entry={entry}
                        index={index}
                        isLast={index === entries.length - 1}
                    />
                ))}
            </ol>
        </section>
    );
}

/**
 * 遠近感の段階。**先頭が最新**（最も大きく濃い）。
 *
 * 以前は index から幅・不透明度を計算して `style` 属性に渡していたが、
 * それだと CSP の `style-src` に `'unsafe-inline'` が必要になる（ADR-011 / I-2）。
 * 静的なクラスに置き換えることで、本番の CSP から `'unsafe-inline'` を外せる。
 *
 * 段数より項目が増えた場合は、最後の段（最も遠い）を使い続ける。
 * 転職して項目が増えても、既存の項目の見え方が変わらないという利点もある。
 */
const PERSPECTIVE = [
    { dot: 'h-7 w-7 opacity-100', heading: 'text-[20px]' },
    { dot: 'h-[22px] w-[22px] opacity-[0.8]', heading: 'text-[18px]' },
    { dot: 'h-[17px] w-[17px] opacity-[0.65]', heading: 'text-[17px]' },
    { dot: 'h-3 w-3 opacity-[0.45]', heading: 'text-[15px]' },
] as const;

function Entry({
    entry,
    index,
    isLast,
}: {
    entry: ContentBlock;
    index: number;
    isLast: boolean;
}) {
    // 新しいほど大きく・濃く。過去に向かって遠ざかる遠近感を出す。
    const step = PERSPECTIVE[Math.min(index, PERSPECTIVE.length - 1)];

    return (
        <li className="relative grid grid-cols-[3rem_1fr] gap-x-4 pb-12" data-testid={`career-${entry.key}`}>
            {/* 時の流れを示す縦の軸。最後（最も古い項目）には引かない */}
            {!isLast && (
                <span
                    aria-hidden="true"
                    className="absolute top-12 bottom-0 left-[1.5rem] w-px -translate-x-1/2 bg-[color:var(--border)]"
                />
            )}

            {/*
                矢印は上向き。最新が上に来る並びなので、
                時間は下（過去）から上（現在）へ流れる。
            */}
            {!isLast && (
                <span
                    aria-hidden="true"
                    className="absolute top-8 left-[1.5rem] -translate-x-1/2 text-sm text-[color:var(--fg-faint)]"
                >
                    ▲
                </span>
            )}

            <div className="flex justify-center pt-1">
                <span
                    aria-hidden="true"
                    className={`relative z-10 rounded-full bg-[color:var(--accent)] ring-4 ring-[color:var(--bg)] ${step.dot}`}
                />
            </div>

            <div>
                <h3 className={`font-bold tracking-tight ${step.heading}`}>
                    {entry.heading}
                </h3>
                <div className="mt-2">
                    <MarkdownBlock html={entry.html} />
                </div>
            </div>
        </li>
    );
}
