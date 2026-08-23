import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import SectionLead from '@/components/layout/SectionLead';
import GitHubLink from '@/components/ui/GitHubLink';
import type { SectionProps } from '@/types';

/**
 * S-5 これから。
 * レイアウト: 左右分割（結論を左、本文を右）。D-2 の「単調さを避ける」。
 */
export default function Next({ section }: { section: SectionProps }) {
    return (
        <section className="max-w-3xl scroll-mt-24" id="next" data-testid="section-next">
            <div className="grid gap-8 sm:grid-cols-5">
                <div className="sm:col-span-2">
                    <SectionLead eyebrow="これから">
                        ユーザと直接向き合う開発に、
                        <br />
                        軸足を置きたい。
                    </SectionLead>

                    <div className="mt-6">
                        <GitHubLink />
                    </div>
                </div>

                <div className="sm:col-span-3">
                    {section.available ? (
                        <MarkdownBlock html={section.lead} />
                    ) : (
                        <ContentUnavailable sectionId={section.id} />
                    )}
                </div>
            </div>
        </section>
    );
}
