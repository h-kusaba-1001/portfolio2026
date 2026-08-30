import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';

createInertiaApp({
    /*
        Inertia 同梱の進捗バー（nprogress）を無効にする。

        **これが要素に style 属性を直接書き込むため、CSP の
        `style-src 'self'`（ADR-018）に弾かれてコンソールにエラーが出ていた。**

        このサイトはアンカーリンクだけで、Inertia のクライアント遷移が
        1 つも無い。**そもそも進捗バーが出る場面が存在しない。**
        CSP を緩めるのではなく、使わない機能を止める。
    */
    progress: false,

    resolve: (name) => {
        // ディレクトリ名は inertia-laravel v3 の既定（resources/js/pages）に合わせる。
        // 既定から外すと config/inertia.php の公開が必要になるため、規約に乗る。
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });

        return pages[`./pages/${name}.tsx`] as never;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
