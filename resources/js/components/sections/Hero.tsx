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
        <header className="pt-16 pb-12 sm:pt-24" data-testid="section-hero">
            <p className="font-mono text-sm tracking-[0.22em] text-[color:var(--fg-faint)] uppercase">
                HK Portfolio
            </p>

            <h1 className="mt-7 text-[2.125rem] leading-[1.12] font-semibold tracking-[-0.03em] sm:text-[3.5rem]">
                AI-DLCとAWSでつくる
                <br className="hidden sm:block" />
                サーバレスなポートフォリオ
            </h1>

            <p className="mt-7 max-w-xl text-[1.0625rem] leading-[1.85] text-[color:var(--fg-muted)]">
                サーバレスアーキテクチャで、月々のAWSインフラ費用ゼロをコンセプトに作りました。
            </p>

            <div className="mt-8">
                <GitHubLink />
            </div>
        </header>
    );
}
