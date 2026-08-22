import type { PortfolioPageProps, SectionProps } from '@/types';

/**
 * UoW-2 時点の暫定表示。
 *
 * 体裁は整えない。`content/*.md` の編集が画面に反映されること（B-3）を
 * 確認するための最小表示にとどめる。
 * 見た目は UoW-3（静的セクション）と UoW-4（構成図）で作り直す。
 */
export default function Portfolio({ sections }: PortfolioPageProps) {
    return (
        <main
            className="min-h-screen bg-white px-6 py-16 text-slate-900"
            data-testid="portfolio-page"
        >
            <div className="mx-auto max-w-2xl space-y-16">
                <header>
                    <h1 className="text-3xl font-semibold tracking-tight">HK Portfolio</h1>
                    <p className="mt-3 text-slate-600">
                        月額 100 円未満で動く、サーバレスなポートフォリオ
                    </p>
                </header>

                {sections.map((section) => (
                    <Section key={section.id} section={section} />
                ))}
            </div>
        </main>
    );
}

function Section({ section }: { section: SectionProps }) {
    return (
        <section data-testid={`section-${section.id}`}>
            <h2 className="text-xl font-semibold">{section.title}</h2>

            {!section.available ? (
                <p className="mt-4 text-slate-500" data-testid={`section-${section.id}-unavailable`}>
                    コンテンツを読み込めませんでした
                </p>
            ) : (
                <>
                    {section.lead !== '' && (
                        <div
                            className="prose-basic mt-4"
                            data-testid={`section-${section.id}-lead`}
                            dangerouslySetInnerHTML={{ __html: section.lead }}
                        />
                    )}

                    {section.blocks.map((block) => (
                        <div key={block.key} className="mt-8" data-testid={`block-${block.key}`}>
                            <h3 className="font-medium">{block.heading}</h3>
                            <div
                                className="prose-basic mt-2"
                                dangerouslySetInnerHTML={{ __html: block.html }}
                            />
                        </div>
                    ))}
                </>
            )}
        </section>
    );
}
