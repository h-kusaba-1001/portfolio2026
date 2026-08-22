/**
 * 経路を流れる光の粒。
 *
 * SVG の `<animateMotion>` で動かす（Q2 = A）。
 * JavaScript も `style` 属性の書き換えも使わないため、
 * CSP を厳しく保ったまま動かせる。
 *
 * `prefers-reduced-motion` のときは呼び出し側でそもそも描画しない。
 */
export default function FlowParticle({ path, delay }: { path: string; delay: number }) {
    return (
        <circle r={4} fill="var(--accent)" opacity={0.9}>
            <animateMotion
                dur="2.4s"
                begin={`${delay}s`}
                repeatCount="indefinite"
                path={path}
                keyPoints="0;1"
                keyTimes="0;1"
                calcMode="linear"
            />
            <animate
                attributeName="opacity"
                dur="2.4s"
                begin={`${delay}s`}
                repeatCount="indefinite"
                values="0;0.9;0.9;0"
                keyTimes="0;0.15;0.85;1"
            />
        </circle>
    );
}
