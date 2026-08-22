import { useState } from 'react';
import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import ArchitectureDiagram from '@/components/diagram/ArchitectureDiagram';
import NodePanel from '@/components/diagram/NodePanel';
import SectionLead from '@/components/layout/SectionLead';
import type { SectionProps } from '@/types';

/**
 * 構成図のブロック（Hero と一体で最上部に置く。D-4）。
 *
 * 図そのものはここ。図の「読み解き」（何を選ばなかったか）は
 * 下の TradeOffs セクションが担う。
 */
export default function Stack({ section }: { section: SectionProps }) {
    const [selectedId, setSelectedId] = useState<string | null>(null);

    return (
        <section id="stack" className="scroll-mt-20" data-testid="section-stack">
            <SectionLead eyebrow="技術構成">
                リクエストは 5 つの箱を通るだけ。
                <br />
                データベースはどこにもありません。
            </SectionLead>

            <div className="mt-8 rounded-2xl border border-[color:var(--border)] bg-[color:var(--bg-subtle)] p-4 sm:p-6">
                <ArchitectureDiagram selectedId={selectedId} onSelect={setSelectedId} />
            </div>

            <p className="mt-3 text-xs text-[color:var(--fg-faint)]">
                図の要素を押すと、それを選んだ理由が下に出ます。破線は「今は無い層」です。
            </p>

            <div className="mt-6">
                <NodePanel selectedId={selectedId} stack={section} />
            </div>

            {!section.available && (
                <div className="mt-6">
                    <ContentUnavailable sectionId={section.id} />
                </div>
            )}

            {section.available && section.lead !== '' && (
                <details className="mt-8 rounded-xl border border-[color:var(--border)] px-5 py-4">
                    <summary className="cursor-pointer text-sm font-medium">
                        この構成をひとことで
                    </summary>
                    <div className="mt-3">
                        <MarkdownBlock html={section.lead} />
                    </div>
                </details>
            )}
        </section>
    );
}
