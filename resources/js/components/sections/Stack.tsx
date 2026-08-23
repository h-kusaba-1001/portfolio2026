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
        <section id="stack" className="scroll-mt-24" data-testid="section-stack">
            <SectionLead eyebrow="技術構成">
                リクエストは 5 つの箱を通るだけ。
                <br />
                データベースはどこにもありません。
            </SectionLead>

            {/* content/stack.md のリード文。見出しの直下に置く */}
            {section.available && section.lead !== '' && (
                <div className="mt-6 max-w-3xl" data-testid="stack-lead">
                    <MarkdownBlock html={section.lead} />
                </div>
            )}

            <div className="mt-8 rounded-2xl border border-[color:var(--border)] bg-[color:var(--bg-subtle)] p-3 sm:p-8">
                <ArchitectureDiagram selectedId={selectedId} onSelect={setSelectedId} />
            </div>

            <p className="mt-4 text-sm text-[color:var(--fg-faint)]">
                図の要素を押すと、それを選んだ理由が下に出ます。
            </p>

            <div className="mt-6 max-w-3xl">
                <NodePanel selectedId={selectedId} stack={section} />
            </div>

            {!section.available && (
                <div className="mt-6">
                    <ContentUnavailable sectionId={section.id} />
                </div>
            )}
        </section>
    );
}
