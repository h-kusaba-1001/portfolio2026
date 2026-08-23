import SectionLead from '@/components/layout/SectionLead';

type Choice = {
    name: string;
    reason: string;
};

/**
 * 「選ばなかったもの」（docs/requirements.md S-2 の核）。
 *
 * §1 のゴール「技術力の証明を『作れる』ではなく『選べる』に置く」に直結する。
 * 見せ方は箇条書き + ネストした理由。カードにすると視線が散り、
 * 「一覧をざっと読む」という本来の読まれ方に合わないため。
 *
 * 内容は編集者の主張であり差し替え対象のコンテンツではないため、
 * Markdown 化せずここに持つ。
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
        <section className="max-w-3xl scroll-mt-24" id="tradeoffs" data-testid="section-tradeoffs">
            <SectionLead eyebrow="選ばなかったもの">
                何を使ったかより、
                <br />
                何を使わなかったかで決まりました。
            </SectionLead>

            <ul className="prose-basic mt-8" data-testid="rejected-list">
                {REJECTED.map((choice) => (
                    <li key={choice.name}>
                        <span className="font-semibold text-[color:var(--fg)]">{choice.name}</span>
                        <ul className="mt-1">
                            <li className="text-[color:var(--fg-muted)]">{choice.reason}</li>
                        </ul>
                    </li>
                ))}
            </ul>

            <p className="mt-8 text-[color:var(--fg-muted)]">
                いずれも「使えないから避けた」のではなく、
                <strong className="font-semibold text-[color:var(--fg)]">
                    この要件では要らないと判断して外した
                </strong>
                ものです。必要になれば足せる状態は保っています。
            </p>
        </section>
    );
}
