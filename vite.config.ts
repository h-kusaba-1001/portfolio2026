import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            // ビルド時プリレンダ用のエントリ（A-1 / ADR-020）。
            // 実行時には使わない。詳細は resources/js/ssr.tsx を参照。
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
            // 外部フォント（bunny）は使わない。理由は resources/css/app.css を参照。
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
