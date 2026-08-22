import GitHubLink from '@/components/ui/GitHubLink';

/**
 * S-1 Hero。
 *
 * 文言は Markdown 化せず、このコンポーネントに直接記述する（Q1 = B）。
 * 分量が少なく更新頻度も低いため。US-7 の対象は S-2〜S-5。
 *
 * 金額は構成上の見込み。AWS の請求額と食い違ったら差し替える
 * （docs/requirements.md S-1「根拠より小さい数字は書かないこと」）。
 */
export default function Hero() {
    return (
        <header className="pt-20 pb-16 sm:pt-28" data-testid="section-hero">
            <p className="text-sm tracking-widest text-[color:var(--fg-faint)] uppercase">
                HK Portfolio
            </p>

            <h1 className="mt-6 text-3xl leading-tight font-semibold tracking-tight sm:text-4xl">
                月額 100 円未満で動く、
                <br className="hidden sm:block" />
                サーバレスなポートフォリオ
            </h1>

            <p className="mt-6 max-w-xl leading-relaxed text-[color:var(--fg-muted)]">
                データベースを持たず、固定費を限りなくゼロに近づけた構成で動いています。
                何を選び、何を選ばなかったのかを公開しています。
            </p>

            <div className="mt-8 flex flex-wrap items-center gap-3">
                <a
                    href="#stack"
                    className="inline-flex items-center gap-2 rounded-full bg-[color:var(--accent)] px-4 py-2 text-sm font-medium text-[color:var(--bg)] transition-opacity hover:opacity-90"
                    data-testid="hero-stack-link"
                >
                    技術構成を見る
                    <span aria-hidden="true">↓</span>
                </a>

                <GitHubLink />
            </div>
        </header>
    );
}
