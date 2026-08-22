import { useEffect, useState } from 'react';

/**
 * `prefers-reduced-motion: reduce` を検出する。
 *
 * Application Design では UoW-4 の担当としていたが、
 * スクロールフェードイン（UoW-3）にも必要なため前倒しした。
 *
 * 初期値を true（＝動かさない）にしているのは、判定が済む前に
 * アニメーションが走ってしまうのを避けるため。
 */
export function usePrefersReducedMotion(): boolean {
    const [prefersReduced, setPrefersReduced] = useState(true);

    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');

        setPrefersReduced(query.matches);

        const onChange = (event: MediaQueryListEvent) => setPrefersReduced(event.matches);

        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    }, []);

    return prefersReduced;
}
