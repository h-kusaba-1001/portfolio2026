import { headingKey } from '@/lib/headingKey';

/**
 * 構成図のノード定義。
 *
 * 座標はここに直書きする（Application Design Q7 = A）。
 * レイアウトを完全に制御できる代わりに、ノード追加時は手作業になる。
 *
 * `heading` は `content/stack.md` の H2 見出しと一致させること。
 * 一致しない場合、パネルには固定文言が出る（画面は壊れない）。
 * 実在チェックは tests/Feature/DiagramNodesTest.php で行う。
 */

export type NodeKind = 'edge' | 'core' | 'storage' | 'extension';

export type Point = { x: number; y: number };

export type DiagramNodeDef = {
    id: string;
    label: string;
    /** ノードの下に小さく添える補足 */
    caption?: string;
    /** content/stack.md の H2 見出し。無い場合はパネルを持たない */
    heading?: string;
    kind: NodeKind;
    /** 横並びレイアウト（デスクトップ）での左上座標 */
    wide: Point;
    /** 縦積みレイアウト（モバイル）での左上座標 */
    narrow: Point;
};

export type DiagramEdge = {
    from: string;
    to: string;
    /** 破線にする（拡張ポイントへの接続） */
    dashed?: boolean;
    /** 光の粒を流すか。リクエストの流れだけを流す */
    animated?: boolean;
};

export const WIDE = { width: 860, height: 430, nodeW: 150, nodeH: 62 };
export const NARROW = { width: 340, height: 700, nodeW: 200, nodeH: 62 };

export const NODES: DiagramNodeDef[] = [
    {
        id: 'browser',
        label: 'Browser',
        caption: '閲覧者',
        kind: 'edge',
        wide: { x: 20, y: 40 },
        narrow: { x: 70, y: 10 },
    },
    {
        id: 'cloudfront',
        label: 'CloudFront',
        caption: 'CDN',
        heading: 'CloudFront',
        kind: 'core',
        wide: { x: 240, y: 40 },
        narrow: { x: 70, y: 120 },
    },
    {
        id: 's3',
        label: 'S3',
        caption: '静的アセット',
        heading: 'S3',
        kind: 'storage',
        wide: { x: 240, y: 200 },
        narrow: { x: 10, y: 230 },
    },
    {
        id: 'apigateway',
        label: 'API Gateway',
        caption: 'HTTP API',
        heading: 'API Gateway',
        kind: 'core',
        wide: { x: 460, y: 40 },
        narrow: { x: 130, y: 340 },
    },
    {
        id: 'lambda',
        label: 'Lambda',
        caption: 'Bref / PHP 8.4',
        heading: 'Lambda (Bref)',
        kind: 'core',
        wide: { x: 680, y: 40 },
        narrow: { x: 130, y: 450 },
    },
    {
        id: 'laravel',
        label: 'Laravel + Inertia',
        caption: 'Markdown を読む',
        heading: 'Laravel + Inertia.js',
        kind: 'core',
        wide: { x: 680, y: 200 },
        narrow: { x: 130, y: 560 },
    },
    {
        id: 'extension',
        label: '拡張ポイント',
        caption: '今は無い層',
        heading: '拡張ポイント',
        kind: 'extension',
        wide: { x: 460, y: 320 },
        narrow: { x: 10, y: 620 },
    },
];

export const EDGES: DiagramEdge[] = [
    { from: 'browser', to: 'cloudfront', animated: true },
    { from: 'cloudfront', to: 'apigateway', animated: true },
    { from: 'apigateway', to: 'lambda', animated: true },
    { from: 'lambda', to: 'laravel', animated: true },
    { from: 'cloudfront', to: 's3' },
    { from: 'laravel', to: 'extension', dashed: true },
];

/** 拡張ポイントの中に並べる要素。図には出さず、パネル側で補足する */
export const EXTENSION_ITEMS = ['DynamoDB', 'SQS', 'Bref X-Ray', 'Inertia SSR', 'オリジン遮断'];

export function nodeById(id: string): DiagramNodeDef | undefined {
    return NODES.find((node) => node.id === id);
}

/** ノードに対応する `ContentBlock.key`。見出しを持たないノードは undefined */
export function nodeKey(node: DiagramNodeDef): string | undefined {
    return node.heading === undefined ? undefined : headingKey(node.heading);
}
