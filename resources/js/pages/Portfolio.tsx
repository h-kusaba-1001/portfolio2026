import type { PortfolioPageProps } from '@/types';

/**
 * UoW-1 時点の雛形。
 *
 * セクションの中身は UoW-3（静的セクション）と UoW-4（構成図）で実装する。
 * ここでは props が届いていることと、スタイルが効いていることだけを確認する。
 */
export default function Portfolio({ sections }: PortfolioPageProps) {
    return (
        <main
            className="min-h-screen bg-white px-6 py-16 text-slate-900"
            data-testid="portfolio-page"
        >
            <div className="mx-auto max-w-2xl">
                <h1 className="text-3xl font-semibold tracking-tight">HK Portfolio</h1>
                <p className="mt-3 text-slate-600">
                    月額 100 円未満で動く、サーバレスなポートフォリオ
                </p>

                <p className="mt-10 text-sm text-slate-500" data-testid="portfolio-scaffold-note">
                    基盤構築（UoW-1）まで完了。読み込み済みセクション数:{' '}
                    <span data-testid="portfolio-section-count">{sections.length}</span>
                </p>
            </div>
        </main>
    );
}
