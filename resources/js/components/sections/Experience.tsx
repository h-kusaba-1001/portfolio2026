import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import Section from '@/components/layout/Section';
import type { SectionProps } from '@/types';

/**
 * S-3 やってきたこと。
 * H2 を持たないため lead に箇条書き全体が入る（business-rules.md R-1-4）。
 */
export default function Experience({ section }: { section: SectionProps }) {
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
