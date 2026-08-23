import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import SectionLead from '@/components/layout/SectionLead';
import type { SectionProps } from '@/types';

/**
 * S-3 やってきたこと。
 * レイアウト: 結論 + カード枠に入れた箇条書き（D-1 / D-2）。
 */
export default function Experience({ section }: { section: SectionProps }) {
    return (
        <section className="max-w-3xl scroll-mt-24" id="experience" data-testid="section-experience">
            <SectionLead eyebrow="これまでの経験">
                顧客折衝と技術力が強みです。
                <br />
            </SectionLead>

            <div className="mt-8 rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] px-6 py-6">
                {section.available ? (
                    <MarkdownBlock html={section.lead} />
                ) : (
                    <ContentUnavailable sectionId={section.id} />
                )}
            </div>
        </section>
    );
}
