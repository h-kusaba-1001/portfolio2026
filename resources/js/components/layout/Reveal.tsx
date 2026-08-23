import { type ReactNode, useEffect, useRef, useState } from 'react';
import { usePrefersReducedMotion } from '@/hooks/usePrefersReducedMotion';

/**
 * 画面に入ったときに、中身をふわりと出す（docs/requirements.md §6）。
 *
 * **見出しやアンカーには関与しない。** 各セクションは自分で `<section>` と
 * id を持っているため、ここは動きだけを担当する薄い入れ物にしてある。
 * 以前の Section コンポーネントは外枠と見出しまで抱えていて、
 * SectionLead と役割が重なるため使われないまま残っていた。
 *
 * 動きは控えめに保つ。目玉は構成図ひとつに絞る方針のため
 * （§6「動きを他セクションに広げない」）。
 */
export default function Reveal({ children }: { children: ReactNode }) {
    const prefersReducedMotion = usePrefersReducedMotion();
    const ref = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const element = ref.current;

        // 動きを嫌う設定、観測できない環境、要素が取れないときは、
        // **必ず表示側に倒す。** 出ないままになる方が害が大きい。
        if (
            prefersReducedMotion ||
            typeof IntersectionObserver === 'undefined' ||
            element === null
        ) {
            setVisible(true);

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
        <div ref={ref} className="fade-in" data-visible={visible}>
            {children}
        </div>
    );
}
