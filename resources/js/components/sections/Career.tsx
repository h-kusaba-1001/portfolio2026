import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import SectionLead from '@/components/layout/SectionLead';
import type { ContentBlock, SectionProps } from '@/types';

/**
 * S-4 キャリアの変遷。
 *
 * **最新を上に並べる**（content/career.md は古い順に書かれているため反転する）。
 * 時間の流れは、丸の大きさの遠近感と**上向きの矢印**で表す。
 * 新しいほど大きく・濃く、過去に向かうほど小さく・淡くなる。
 * 最新が上に来る並びのため、時間は下（過去）から上（現在）へ流れる。
 */
export default function Career({ section }: { section: SectionProps }) {
    if (!section.available) {
        return (
            <section className="max-w-3xl" id="career" data-testid="section-career">
                <SectionLead eyebrow="キャリアの変遷">
                    銀行の営業から、
                    <br />
                    サーバレスを設計する側へ。
                </SectionLead>
                <div className="mt-8">
                    <ContentUnavailable sectionId={section.id} />
                </div>
            </section>
        );
    }

    // 古い順 → 新しい順に書かれた Markdown を、表示時に反転する
    const entries = [...section.blocks].reverse();

    return (
        <section className="max-w-3xl" id="career" data-testid="section-career">
            <SectionLead eyebrow="キャリアの変遷">
                銀行の営業から、
                <br />
                サーバレスを設計する側へ。
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
                        total={entries.length}
                        isLast={index === entries.length - 1}
                    />
                ))}
            </ol>
        </section>
    );
}

function Entry({
    entry,
    index,
    total,
    isLast,
}: {
    entry: ContentBlock;
    index: number;
    total: number;
    isLast: boolean;
}) {
    // 新しいほど大きく・濃く。過去に向かって遠ざかる遠近感を出す。
    const ratio = total <= 1 ? 1 : 1 - index / (total - 1);
    const size = 12 + ratio * 16; // 直径 12〜28px
    const opacity = 0.45 + ratio * 0.55;

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
                    className="relative z-10 rounded-full bg-[color:var(--accent)] ring-4 ring-[color:var(--bg)]"
                    style={{ width: size, height: size, opacity }}
                />
            </div>

            <div>
                <h3
                    className="font-bold tracking-tight"
                    style={{ fontSize: 15 + ratio * 5 }}
                >
                    {entry.heading}
                </h3>
                <div className="mt-2">
                    <MarkdownBlock html={entry.html} />
                </div>
            </div>
        </li>
    );
}
