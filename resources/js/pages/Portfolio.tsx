import ThemeToggle from '@/components/layout/ThemeToggle';
import Career from '@/components/sections/Career';
import Experience from '@/components/sections/Experience';
import Hero from '@/components/sections/Hero';
import Next from '@/components/sections/Next';
import Stack from '@/components/sections/Stack';
import TradeOffs from '@/components/sections/TradeOffs';
import type { PortfolioPageProps, SectionId, SectionProps } from '@/types';

const NAV = [
    { href: '#stack', label: '技術構成' },
    { href: '#tradeoffs', label: '選ばなかったもの' },
    { href: '#experience', label: 'やってきたこと' },
    { href: '#career', label: 'キャリア' },
    { href: '#next', label: 'これから' },
];

/**
 * トップページ。単一ページ構成（Q4 = A）。
 *
 * 並びは docs/requirements.md §5 のとおり。
 * 構成図は Hero と一体で最上部に置く（D-4）。
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

                <div className="space-y-24 sm:space-y-32">
                    {stack !== undefined && <Stack section={stack} />}
                    <TradeOffs />
                    {experience !== undefined && <Experience section={experience} />}
                    {career !== undefined && <Career section={career} />}
                    {next !== undefined && <Next section={next} />}
                </div>
            </main>

            <SiteFooter />
        </div>
    );
}

function SiteHeader() {
    return (
        <div className="sticky top-0 z-10 border-b border-[color:var(--border)] bg-[color:var(--bg)]/85 backdrop-blur">
            <div className="mx-auto flex max-w-3xl items-center justify-between gap-4 px-6 py-3">
                <a href="#top" className="text-sm font-semibold tracking-tight" data-testid="site-header-home">
                    HK Portfolio
                </a>

                <div className="flex items-center gap-4">
                    <nav className="hidden gap-4 text-sm text-[color:var(--fg-muted)] lg:flex">
                        {NAV.map((item) => (
                            <a key={item.href} href={item.href} className="hover:text-[color:var(--fg)]">
                                {item.label}
                            </a>
                        ))}
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
                このサイト自体が、上の構成図のとおりに動いています。
            </div>
        </footer>
    );
}
