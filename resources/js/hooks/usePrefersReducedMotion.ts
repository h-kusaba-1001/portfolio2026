import { useEffect, useState } from 'react';

const QUERY = '(prefers-reduced-motion: reduce)';

/**
 * `prefers-reduced-motion: reduce` を検出する。
 *
 * **初期値は初回レンダリング時に同期で読む。**
 * 以前は初期値を true（＝動かさない）に固定していたが、そうすると
 * 「判定前に true を見た側が『動かさない』方の分岐を確定させてしまい、
 * 判定が false に変わっても元に戻らない」ため、
 * スクロールのフェードインが誰にも発火しなかった。
 *
 * SSR は使わない方針（ADR-008）なので、レンダリング時に window を読んでよい。
 * それでも念のため window の有無は確認する。
 */
export function usePrefersReducedMotion(): boolean {
    const [prefersReduced, setPrefersReduced] = useState(
        () => typeof window !== 'undefined' && window.matchMedia(QUERY).matches,
    );

    useEffect(() => {
        const query = window.matchMedia(QUERY);

        setPrefersReduced(query.matches);

        const onChange = (event: MediaQueryListEvent) => setPrefersReduced(event.matches);

        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    }, []);

    return prefersReduced;
}
