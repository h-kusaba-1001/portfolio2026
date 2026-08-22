import { type ReactNode, useEffect, useRef, useState } from 'react';
import { usePrefersReducedMotion } from '@/hooks/usePrefersReducedMotion';

type Props = {
    id: string;
    title: string;
    children: ReactNode;
};

/**
 * セクションの外枠。見出し・アンカー・スクロールフェードインを担う。
 *
 * 動きは控えめに保つ。目玉は構成図ひとつに絞る方針のため
 * （docs/requirements.md §6「動きを他セクションに広げない」）。
 */
export default function Section({ id, title, children }: Props) {
    const prefersReducedMotion = usePrefersReducedMotion();
    const ref = useRef<HTMLElement>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (prefersReducedMotion) {
            setVisible(true);

            return;
        }

        const element = ref.current;

        if (element === null) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { rootMargin: '0px 0px -10% 0px' },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, [prefersReducedMotion]);

    return (
        <section
            ref={ref}
            id={id}
            className="fade-in scroll-mt-20"
            data-visible={visible}
            data-testid={`section-${id}`}
        >
            <h2 className="text-sm font-semibold tracking-widest text-[color:var(--accent)] uppercase">
                {title}
            </h2>

            <div className="mt-6">{children}</div>
        </section>
    );
}
