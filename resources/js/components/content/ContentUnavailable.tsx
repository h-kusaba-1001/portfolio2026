/**
 * 読み込みに失敗したセクションの表示（Q6-a = A）。
 *
 * セクションの枠は残し、本文の位置に固定文言を出す。
 * 内部情報（ファイルパス・例外）は一切出さない（NFR-S6 / SECURITY-09）。
 */
export default function ContentUnavailable({ sectionId }: { sectionId: string }) {
    return (
        <p
            className="text-[color:var(--fg-faint)]"
            data-testid={`section-${sectionId}-unavailable`}
        >
            コンテンツを読み込めませんでした
        </p>
    );
}
