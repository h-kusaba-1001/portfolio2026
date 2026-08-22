import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import Section from '@/components/layout/Section';
import type { SectionProps } from '@/types';

/**
 * S-4 キャリアの変遷。
 *
 * lead（導入文）＋ H2 ごとのブロック（各社の経歴）で構成される。
 * 「何を得たか」で語る構成のため、見出しと本文を対にして縦に並べる。
 */
export default function Career({ section }: { section: SectionProps }) {
    if (!section.available) {
        return (
            <Section id={section.id} title={section.title}>
                <ContentUnavailable sectionId={section.id} />
            </Section>
        );
    }

    return (
        <Section id={section.id} title={section.title}>
            {section.lead !== '' && <MarkdownBlock html={section.lead} />}

            <ol className="mt-10 space-y-8 border-l border-[color:var(--border)] pl-6">
                {section.blocks.map((block) => (
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
        </Section>
    );
}
