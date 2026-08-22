import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import SectionLead from '@/components/layout/SectionLead';
import type { SectionProps } from '@/types';

/**
 * S-4 キャリアの変遷。
 *
 * lead（導入文）＋ H2 ごとのブロック（各社の経歴）で構成される。
 * 「何を得たか」で語る構成のため、見出しと本文を対にして縦に並べる。
 */
export default function Career({ section }: { section: SectionProps }) {
    return (
        <section id="career" className="scroll-mt-20" data-testid="section-career">
            <SectionLead eyebrow="キャリアの変遷">
                銀行の営業から、
                <br />
                サーバレスを設計する側へ。
            </SectionLead>

            {!section.available && <div className="mt-8"><ContentUnavailable sectionId={section.id} /></div>}

            {section.available && section.lead !== '' && (
                <div className="mt-8">
                    <MarkdownBlock html={section.lead} />
                </div>
            )}

            <ol className="mt-10 space-y-8 border-l border-[color:var(--border)] pl-6">
                {section.available && section.blocks.map((block) => (
                    <li key={block.key} className="relative" data-testid={`career-${block.key}`}>
                        <span
                            aria-hidden="true"
                            className="absolute top-2 -left-[1.8rem] size-2 rounded-full bg-[color:var(--accent)]"
                        />
                        <h3 className="font-medium">{block.heading}</h3>
                        <div className="mt-2">
                            <MarkdownBlock html={block.html} />
                        </div>
                    </li>
                ))}
            </ol>
        </section>
    );
}
