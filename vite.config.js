import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import checker from 'vite-plugin-checker';
import { collectModuleAssetsPaths, collectModulePlugins } from './vite-module-loader.js';

const paths = [
    'resources/js/app.ts',
    'resources/css/app.css',
    'resources/css/filament/admin/theme.css',
];

// Ensure modulePaths is always an array
let modulePaths = collectModuleAssetsPaths('extensions');
if (!Array.isArray(modulePaths)) modulePaths = [];

// Ensure additionalPlugins is always an array
let additionalPlugins = collectModulePlugins('extensions');
if (!Array.isArray(additionalPlugins)) additionalPlugins = [];

export default defineConfig({
    build: { sourcemap: true },
    optimizeDeps: {
        include: ['@inertiajs/vue3', 'axios', 'lodash', 'vue'],
    },
    plugins: [
        laravel({ input: [...paths, ...modulePaths], refresh: true }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
        checker({ typescript: true, vueTsc: true, lintCommand: 'eslint "./**/*.{ts,vue}"' }),
        ...additionalPlugins,
    ],
});
