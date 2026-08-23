import { createInertiaApp } from '@inertiajs/react';
import { readFileSync } from 'node:fs';
import { renderToString } from 'react-dom/server';

/**
 * ビルド時プリレンダ用のエントリ（A-1 / ADR-020）。
 *
 * **実行時には使わない。** Inertia の SSR は通常 Node のサーバを常駐させるが、
 * それをやると Lambda に Node をもう 1 つ足すことになり、
 * ADR-008 で退けた構成に逆戻りする。
 *
 * このサイトの内容はデプロイ時点で確定している（Markdown を同梱している）ため、
 * **ビルド時に 1 回だけ描画して、その HTML を Blade に埋める**方式にする。
 * 実行時の構成は一切変わらない。
 *
 * 使い方: node bootstrap/ssr/ssr.js <page.json のパス>
 *   標準出力に {"head": [...], "body": "..."} を JSON で返す。
 */
const pagePath = process.argv[2];

if (pagePath === undefined) {
    process.stderr.write('page.json のパスを引数に渡してください\n');
    process.exit(1);
}

const page = JSON.parse(readFileSync(pagePath, 'utf-8'));

const rendered = await createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });

        return pages[`./pages/${name}.tsx`] as never;
    },
    setup: ({ App, props }) => <App {...props} />,
});

process.stdout.write(JSON.stringify(rendered));
