import SectionLead from '@/components/layout/SectionLead';

type Choice = {
    name: string;
    reason: string;
};

/**
 * 「選ばなかったもの」（docs/requirements.md S-2 の核 / D-3）。
 *
 * §1 のゴール「技術力の証明を『作れる』ではなく『選べる』に置く」に直結する。
 * 文章で並べると読み飛ばされるため、カードにして一覧性を上げる（D-2）。
 *
 * 内容は ADR に基づく。Markdown 化していないのは、
 * これが「編集者の主張」であって差し替え対象のコンテンツではないため。
 */
const REJECTED: Choice[] = [
    { name: 'RDS', reason: '最小構成でも月 2,000 円超。読み取りしかないので不要' },
    { name: 'NAT Gateway', reason: 'VPC を使わない構成にしたため出番がない' },
    { name: 'ALB', reason: '常時起動で固定費が出る。CloudFront で足りる' },
    { name: 'AWS WAF', reason: '月 5〜8 ドル。守る対象が費用なら、同時実行の上限の方が直接効く' },
    { name: 'Inertia SSR', reason: 'Lambda に Node をもう 1 つ足すことになる。OGP は Blade で足りる' },
    { name: 'X-Ray', reason: '外部依存もデータベースも無く、追跡する区間が実質 1 つしかない' },
];

export default function TradeOffs() {
    return (
        <section id="tradeoffs" className="scroll-mt-20" data-testid="section-tradeoffs">
            <SectionLead eyebrow="選ばなかったもの">
                何を使ったかより、
                <br />
                何を使わなかったかで決まりました。
            </SectionLead>

            <ul className="mt-8 grid gap-3 sm:grid-cols-2" data-testid="rejected-list">
                {REJECTED.map((choice) => (
                    <li
                        key={choice.name}
                        className="rounded-xl border border-[color:var(--border)] px-5 py-4"
                    >
                        <p className="font-medium text-[color:var(--fg)] line-through decoration-[color:var(--fg-faint)] decoration-1">
                            {choice.name}
                        </p>
                        <p className="mt-2 text-sm leading-relaxed text-[color:var(--fg-muted)]">
                            {choice.reason}
                        </p>
                    </li>
                ))}
            </ul>

            <p className="mt-6 text-sm text-[color:var(--fg-faint)]">
                判断の経緯は
                <a
                    href="https://github.com/h-kusaba-1001/portfolio2026/blob/main/docs/architecture-decisions.md"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-[color:var(--accent)] underline underline-offset-4"
                >
                    ADR
                </a>
                に全て残しています。判断を変えた記録も消していません。
            </p>
        </section>
    );
}
