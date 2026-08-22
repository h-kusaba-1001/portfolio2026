import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import Section from '@/components/layout/Section';
import type { SectionProps } from '@/types';

/** S-5 これから。H2 を持たないため lead のみ。 */
export default function Next({ section }: { section: SectionProps }) {
    return (
        <Section id={section.id} title={section.title}>
            {section.available ? (
                <MarkdownBlock html={section.lead} />
            ) : (
                <ContentUnavailable sectionId={section.id} />
            )}
        </Section>
    );
}
