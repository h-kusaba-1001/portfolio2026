import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';

createInertiaApp({
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
