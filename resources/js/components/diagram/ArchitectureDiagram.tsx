import { usePrefersReducedMotion } from '@/hooks/usePrefersReducedMotion';
import DiagramNode from './DiagramNode';
import FlowParticle from './FlowParticle';
import { EDGES, NARROW, NODES, WIDE, type DiagramNodeDef, type Point } from './nodes';

type Layout = typeof WIDE;

type Props = {
    selectedId: string | null;
    onSelect: (id: string) => void;
};

/**
 * アーキテクチャ構成図。
 *
 * 座標はノード定義に直書きし、ここでは描画だけを行う（Q7 = A）。
 * 横並び（デスクトップ）と縦積み（モバイル）の 2 つのレイアウトを持ち、
 * CSS で出し分ける。同じノード定義を両方で使う。
 */
export default function ArchitectureDiagram({ selectedId, onSelect }: Props) {
    return (
        <>
            <div className="hidden sm:block" data-testid="architecture-diagram">
                <Svg layout={WIDE} pick={(node) => node.wide} selectedId={selectedId} onSelect={onSelect} />
            </div>

            <div className="sm:hidden" data-testid="architecture-diagram-narrow">
                <Svg layout={NARROW} pick={(node) => node.narrow} selectedId={selectedId} onSelect={onSelect} />
            </div>
        </>
    );
}

function Svg({
    layout,
    pick,
    selectedId,
    onSelect,
}: {
    layout: Layout;
    pick: (node: DiagramNodeDef) => Point;
    selectedId: string | null;
    onSelect: (id: string) => void;
}) {
    const prefersReducedMotion = usePrefersReducedMotion();

    const box = (node: DiagramNodeDef) => {
        const origin = pick(node);

        return {
            x: origin.x,
            y: origin.y,
            cx: origin.x + layout.nodeW / 2,
            cy: origin.y + layout.nodeH / 2,
            right: origin.x + layout.nodeW,
            bottom: origin.y + layout.nodeH,
        };
    };

    return (
        <svg
            viewBox={`0 0 ${layout.width} ${layout.height}`}
            className="w-full"
            role="img"
            aria-label="Browser から CloudFront、API Gateway、Lambda、Laravel へとリクエストが流れる構成図"
        >
            {EDGES.map((edge) => {
                const from = NODES.find((n) => n.id === edge.from);
                const to = NODES.find((n) => n.id === edge.to);

                if (from === undefined || to === undefined) {
                    return null;
                }

                const d = connector(box(from), box(to));

                return (
                    <g key={`${edge.from}-${edge.to}`}>
                        <path
                            id={`edge-${edge.from}-${edge.to}`}
                            d={d}
                            fill="none"
                            stroke="var(--diagram-border)"
                            strokeWidth={1.5}
                            strokeDasharray={edge.dashed === true ? '6 5' : undefined}
                        />

                        {edge.animated === true && !prefersReducedMotion && (
                            <FlowParticle path={d} delay={EDGES.indexOf(edge) * 0.45} />
                        )}
                    </g>
                );
            })}

            {NODES.map((node) => {
                const origin = pick(node);

                return (
                    <DiagramNode
                        key={node.id}
                        node={node}
                        x={origin.x}
                        y={origin.y}
                        width={layout.nodeW}
                        height={layout.nodeH}
                        selected={selectedId === node.id}
                        onSelect={onSelect}
                    />
                );
            })}
        </svg>
    );
}

type Box = {
    x: number;
    y: number;
    cx: number;
    cy: number;
    right: number;
    bottom: number;
};

/**
 * 2 つのノードを結ぶ線。
 * 同じ行なら水平、同じ列なら垂直、それ以外は L 字にする。
 */
function connector(from: Box, to: Box): string {
    const sameRow = Math.abs(from.cy - to.cy) < 8;
    const sameColumn = Math.abs(from.cx - to.cx) < 8;

    if (sameRow) {
        return from.cx < to.cx
            ? `M ${from.right} ${from.cy} H ${to.x}`
            : `M ${from.x} ${from.cy} H ${to.right}`;
    }

    if (sameColumn) {
        return from.cy < to.cy
            ? `M ${from.cx} ${from.bottom} V ${to.y}`
            : `M ${from.cx} ${from.y} V ${to.bottom}`;
    }

    // L 字。いったん下（または上）に降りてから横へ
    const midY = from.cy < to.cy ? from.bottom + (to.y - from.bottom) / 2 : from.y - (from.y - to.bottom) / 2;

    return `M ${from.cx} ${from.cy < to.cy ? from.bottom : from.y} V ${midY} H ${to.cx} V ${
        from.cy < to.cy ? to.y : to.bottom
    }`;
}
