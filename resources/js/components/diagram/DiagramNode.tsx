import type { DiagramNodeDef } from './nodes';

type Props = {
    node: DiagramNodeDef;
    x: number;
    y: number;
    width: number;
    height: number;
    selected: boolean;
    onSelect: (id: string) => void;
};

const FILL: Record<DiagramNodeDef['kind'], string> = {
    edge: 'var(--diagram-node-quiet)',
    core: 'var(--diagram-node)',
    storage: 'var(--diagram-node-quiet)',
    extension: 'transparent',
};

/**
 * ノード 1 つ。見出しを持つノードだけが押せる。
 *
 * 押せないノード（Browser など）は説明が無いため、
 * カーソルもフォーカスも与えない。
 */
export default function DiagramNode({
    node,
    x,
    y,
    width,
    height,
    selected,
    onSelect,
}: Props) {
    const selectable = node.heading !== undefined;
    const dashed = node.kind === 'extension';

    return (
        <g
            transform={`translate(${x}, ${y})`}
            className={selectable ? 'cursor-pointer' : undefined}
            onClick={selectable ? () => onSelect(node.id) : undefined}
            onKeyDown={
                selectable
                    ? (event) => {
                          if (event.key === 'Enter' || event.key === ' ') {
                              event.preventDefault();
                              onSelect(node.id);
                          }
                      }
                    : undefined
            }
            tabIndex={selectable ? 0 : undefined}
            role={selectable ? 'button' : undefined}
            aria-pressed={selectable ? selected : undefined}
            aria-label={selectable ? `${node.label} の選定理由を見る` : undefined}
            data-testid={`diagram-node-${node.id}`}
        >
            <rect
                width={width}
                height={height}
                rx={10}
                fill={FILL[node.kind]}
                stroke={selected ? 'var(--accent)' : 'var(--diagram-border)'}
                strokeWidth={selected ? 2 : 1}
                strokeDasharray={dashed ? '6 4' : undefined}
            />

            <text
                x={width / 2}
                y={node.caption === undefined ? height / 2 + 5 : height / 2 - 3}
                textAnchor="middle"
                className="fill-[color:var(--fg)] text-[13px] font-medium"
            >
                {node.label}
            </text>

            {node.caption !== undefined && (
                <text
                    x={width / 2}
                    y={height / 2 + 15}
                    textAnchor="middle"
                    className="fill-[color:var(--fg-faint)] text-[10px]"
                >
                    {node.caption}
                </text>
            )}
        </g>
    );
}
