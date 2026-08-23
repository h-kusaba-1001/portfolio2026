import type { ReactNode } from 'react';

/**
 * セクションの「結論を一言」（デザイン要件 D-1）。
 *
 * 読み手が上の一言だけ拾って流し読みできることを狙う。
 * 本文（content/*.md 由来）はその下に補足として置く。
 *
 * 文言は React に直書きする。Markdown の本文が「補足」であり、
 * この一言は編集者の主張そのものなので、構造として分けている。
 */
export default function SectionLead({
    eyebrow,
    children,
}: {
    eyebrow: string;
    children: ReactNode;
}) {
    return (
        <div className="max-w-2xl">
            <p className="text-lg font-bold tracking-widest text-[color:var(--accent)] uppercase">
                {eyebrow}
            </p>
            <p className="mt-4 text-2xl leading-snug font-bold tracking-tight sm:text-3xl">
                {children}
            </p>
        </div>
    );
}
