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

const ICON = 46;

/**
 * ノード 1 つ。
 *
 * AWS のサービスは公式アイコン（public/aws-icons/）を使う。
 * 自前ホストなので CSP の img-src 'self' を満たす（外部から読み込まない）。
 *
 * 見出しを持つノードだけが押せる。押せないノードにはフォーカスも与えない。
 */
export default function DiagramNode({ node, x, y, width, height, selected, onSelect }: Props) {
    const selectable = node.heading !== undefined;
    const dashed = node.kind === 'extension';
    const tint = node.tint ?? 'var(--diagram-border)';

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
            {/* 選択時に外側を光らせる */}
            {selected && (
                <rect
                    x={-5}
                    y={-5}
                    width={width + 10}
                    height={height + 10}
                    rx={16}
                    fill="none"
                    stroke={tint}
                    strokeWidth={2}
                    opacity={0.45}
                />
            )}

            <rect
                width={width}
                height={height}
                rx={12}
                fill={dashed ? 'transparent' : 'var(--diagram-node)'}
                stroke={selected ? tint : dashed ? tint : 'var(--diagram-border)'}
                strokeWidth={selected ? 2.5 : dashed ? 2 : 1.5}
                strokeDasharray={dashed ? '8 5' : undefined}
            />

            {/* サービス色の帯。図全体に色を入れて単調さを消す */}
            {!dashed && (
                <rect width={width} height={4} rx={2} fill={tint} opacity={selected ? 1 : 0.85} />
            )}

            {node.icon !== undefined ? (
                <image
                    href={node.icon}
                    x={(width - ICON) / 2}
                    y={14}
                    width={ICON}
                    height={ICON}
                    preserveAspectRatio="xMidYMid meet"
                />
            ) : (
                <circle cx={width / 2} cy={14 + ICON / 2} r={ICON / 2 - 6} fill={tint} opacity={0.18} />
            )}

            <text
                x={width / 2}
                y={height - 26}
                textAnchor="middle"
                className="fill-[color:var(--fg)]"
                style={{ fontSize: 18, fontWeight: 700 }}
            >
                {node.label}
            </text>

            {node.caption !== undefined && (
                <text
                    x={width / 2}
                    y={height - 9}
                    textAnchor="middle"
                    className="fill-[color:var(--fg-muted)]"
                    style={{ fontSize: 12.5 }}
                >
                    {node.caption}
                </text>
            )}
        </g>
    );
}
