import ContentUnavailable from '@/components/content/ContentUnavailable';
import MarkdownBlock from '@/components/content/MarkdownBlock';
import ThemeToggle from '@/components/layout/ThemeToggle';
import Section from '@/components/layout/Section';
import Career from '@/components/sections/Career';
import Experience from '@/components/sections/Experience';
import Hero from '@/components/sections/Hero';
import Next from '@/components/sections/Next';
import type { PortfolioPageProps, SectionId, SectionProps } from '@/types';

/**
 * トップページ。単一ページ構成（Q4 = A）。
 *
 * S-2（技術構成）は UoW-4 で構成図に差し替えるため、
 * ここでは暫定表示のまま残している。
 */
export default function Portfolio({ sections }: PortfolioPageProps) {
    const find = (id: SectionId): SectionProps | undefined =>
        sections.find((section) => section.id === id);

    const stack = find('stack');
    const experience = find('experience');
    const career = find('career');
    const next = find('next');

    return (
        <div className="min-h-screen">
            <SiteHeader />

            <main className="mx-auto max-w-3xl px-6 pb-28">
                <Hero />

                <div className="space-y-24">
                    {stack !== undefined && <StackPlaceholder section={stack} />}
                    {experience !== undefined && <Experience section={experience} />}
                    {career !== undefined && <Career section={career} />}
                    {next !== undefined && <Next section={next} />}
                </div>
            </main>

            <SiteFooter />
        </div>
    );
}

/** 上部の固定ヘッダ（Q3 = C） */
function SiteHeader() {
    return (
        <div className="sticky top-0 z-10 border-b border-[color:var(--border)] bg-[color:var(--bg)]/85 backdrop-blur">
            <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-3">
                <a
                    href="#top"
                    className="text-sm font-semibold tracking-tight"
                    data-testid="site-header-home"
                >
                    HK Portfolio
                </a>

                <div className="flex items-center gap-4">
                    <nav className="hidden gap-4 text-sm text-[color:var(--fg-muted)] sm:flex">
                        <a href="#stack" className="hover:text-[color:var(--fg)]">
                            技術構成
                        </a>
                        <a href="#experience" className="hover:text-[color:var(--fg)]">
                            やってきたこと
                        </a>
                        <a href="#career" className="hover:text-[color:var(--fg)]">
                            キャリア
                        </a>
                        <a href="#next" className="hover:text-[color:var(--fg)]">
                            これから
                        </a>
                    </nav>

                    <ThemeToggle />
                </div>
            </div>
        </div>
    );
}

function SiteFooter() {
    return (
        <footer className="border-t border-[color:var(--border)]">
            <div className="mx-auto max-w-3xl px-6 py-8 text-sm text-[color:var(--fg-faint)]">
                このサイトは AWS Lambda 上で動いています。ソースは GitHub に公開しています。
            </div>
        </footer>
    );
}

/**
 * S-2 の暫定表示。UoW-4 で ArchitectureDiagram に差し替える。
 * ここでは lead とブロックを素朴に並べるだけ。
 */
function StackPlaceholder({ section }: { section: SectionProps }) {
    return (
        <Section id={section.id} title={section.title}>
            {section.available ? (
                <>
                    {section.lead !== '' && <MarkdownBlock html={section.lead} />}

                    <div className="mt-8 space-y-8">
                        {section.blocks.map((block) => (
                            <div key={block.key} data-testid={`block-${block.key}`}>
                                <h3 className="font-medium">{block.heading}</h3>
                                <div className="mt-2">
                                    <MarkdownBlock html={block.html} />
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            ) : (
                <ContentUnavailable sectionId={section.id} />
            )}
        </Section>
    );
}
