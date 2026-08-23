import MarkdownBlock from '@/components/content/MarkdownBlock';
import type { SectionProps } from '@/types';
import { nodeById, nodeKey } from './nodes';

/**
 * 選択中のノードの選定理由を表示する（Q3 = A: 図の下に固定枠）。
 *
 * 内容は content/stack.md から供給する。ハードコードしない。
 *
 * 見出しが一致しなかった場合は固定文言を出す。
 * **画面は壊さない**（business-rules.md R-2 の緩和策 2）。
 */
export default function NodePanel({
    selectedId,
    stack,
}: {
    selectedId: string | null;
    stack: SectionProps | undefined;
}) {
    if (selectedId === null) {
        return (
            <div
                className="rounded-xl border border-dashed border-[color:var(--border)] px-5 py-6 text-sm text-[color:var(--fg-faint)]"
                data-testid="node-panel-empty"
            >
                図の要素を選ぶと、なぜそれを選んだのかが出ます。
            </div>
        );
    }

    const node = nodeById(selectedId);

    if (node === undefined) {
        return null;
    }

    const key = nodeKey(node);
    const block = key === undefined ? undefined : stack?.blocks.find((b) => b.key === key);

    return (
        <div
            className="rounded-xl border border-[color:var(--border)] bg-[color:var(--bg-subtle)] px-5 py-6"
            data-testid={`node-panel-${node.id}`}
        >
            <h3 className="font-semibold">{node.label}</h3>

            {block === undefined ? (
                <p className="mt-3 text-sm text-[color:var(--fg-faint)]" data-testid="node-panel-missing">
                    この要素の説明はまだありません。
                </p>
            ) : (
                <div className="mt-3">
                    <MarkdownBlock html={block.html} />
                </div>
            )}

        </div>
    );
}
