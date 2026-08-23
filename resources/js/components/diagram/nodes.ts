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
    /** public/aws-icons/ 配下の AWS 公式アイコン。無い場合は記号を描く */
    icon?: string;
    /** ノードの色味（AWS のサービスカテゴリ色に合わせる） */
    tint?: string;
    kind: NodeKind;
    /** 横並びレイアウト（デスクトップ）での左上座標 */
    wide: Point;
    /** 縦積みレイアウト（モバイル）での左上座標 */
    narrow: Point;
};

export type DiagramEdge = {
    from: string;
    to: string;
    /** 破線にする */
    dashed?: boolean;
    /** 光の粒を流すか。リクエストの流れだけを流す */
    animated?: boolean;
    /**
     * 線に添えるラベル。CloudFront がパスで行き先を振り分けていることを示す。
     * これが無いと「リクエストが S3 へ流れ込む」ようにも読めてしまう。
     */
    label?: string;
};

// 横一列。矢印が折り返さない配置にする。
export const WIDE = { width: 1120, height: 400, nodeW: 176, nodeH: 112 };
export const NARROW = { width: 360, height: 880, nodeW: 150, nodeH: 118 };

export const NODES: DiagramNodeDef[] = [
    {
        id: 'browser',
        label: 'Browser',
        caption: '閲覧者',
        // 特定の製品を指さない汎用のブラウザ記号（自作）。
        // 商標を持ち込まずに済み、CSP の img-src 'self' も満たす。
        icon: '/brand/browser.svg',
        kind: 'edge',
        tint: '#64748b',
        wide: { x: 20, y: 60 },
        narrow: { x: 105, y: 10 },
    },
    {
        id: 'cloudfront',
        label: 'CloudFront',
        caption: 'CDN / TLS 終端',
        heading: 'CloudFront',
        icon: '/aws-icons/cloudfront.svg',
        tint: '#8C4FFF',
        kind: 'core',
        wide: { x: 246, y: 60 },
        narrow: { x: 105, y: 150 },
    },
    {
        id: 's3',
        label: 'S3',
        caption: '静的アセット',
        heading: 'S3',
        icon: '/aws-icons/s3.svg',
        tint: '#7AA116',
        kind: 'storage',
        wide: { x: 246, y: 250 },
        narrow: { x: 10, y: 300 },
    },
    {
        id: 'apigateway',
        label: 'API Gateway',
        caption: 'HTTP API',
        heading: 'API Gateway',
        icon: '/aws-icons/api-gateway.svg',
        tint: '#8C4FFF',
        kind: 'core',
        wide: { x: 472, y: 60 },
        narrow: { x: 105, y: 440 },
    },
    {
        id: 'lambda',
        label: 'Lambda',
        caption: 'Bref / PHP 8.4',
        heading: 'Lambda (Bref)',
        icon: '/aws-icons/lambda.svg',
        tint: '#ED7100',
        kind: 'core',
        wide: { x: 698, y: 60 },
        narrow: { x: 105, y: 580 },
    },
    {
        id: 'laravel',
        label: 'Laravel',
        caption: 'Inertia / Markdown',
        heading: 'Laravel + Inertia.js',
        icon: '/brand/laravel.svg',
        tint: '#FF2D20',
        kind: 'core',
        wide: { x: 924, y: 60 },
        narrow: { x: 105, y: 720 },
    },
];

export const EDGES: DiagramEdge[] = [
    { from: 'browser', to: 'cloudfront', animated: true },
    // ラベルを '/*' にすると**コメントの開始に見える**ため、言葉で書く
    { from: 'cloudfront', to: 'apigateway', animated: true, label: '既定' },
    { from: 'apigateway', to: 'lambda', animated: true },
    { from: 'lambda', to: 'laravel', animated: true },
    // S3 は CloudFront から分岐する枝（docs/requirements.md §6）。
    // S3 が API Gateway を呼ぶわけではない。CloudFront がパスで振り分けている。
    { from: 'cloudfront', to: 's3', label: '/build/*' },
];

export function nodeById(id: string): DiagramNodeDef | undefined {
    return NODES.find((node) => node.id === id);
}

/** ノードに対応する `ContentBlock.key`。見出しを持たないノードは undefined */
export function nodeKey(node: DiagramNodeDef): string | undefined {
    return node.heading === undefined ? undefined : headingKey(node.heading);
}
