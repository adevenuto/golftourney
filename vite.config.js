import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { generateRandomString } from './resources/utils';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => ['md-linedivider'].includes(tag),
                },
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                }
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: `[name]-` + generateRandomString() + `.js`,
                chunkFileNames: `[name]-` + generateRandomString() + `.js`,
                assetFileNames: `[name]-` + generateRandomString() + `.[ext]`
            }
        }
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
