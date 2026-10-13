import preact from '@preact/preset-vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { visualizer } from 'rollup-plugin-visualizer';
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        // JSX via Preact and Prefresh HMR. The react aliases are defined below so
        // `react` can point at our compat module.
        preact({ prerender: { enabled: false }, reactAliasesEnabled: false }),
        tailwindcss(),
        // `npm run analyze` writes a gzip-aware treemap of the client bundle.
        mode === 'analyze' && visualizer({ filename: 'storage/app/bundle-stats.html', template: 'treemap', gzipSize: true }),
    ],
    resolve: {
        alias: [
            { find: '@', replacement: resolve(__dirname, 'resources/js') },
            // React API via Preact, for our code and for @inertiajs/react.
            { find: /^react$/, replacement: resolve(__dirname, 'resources/js/lib/preact-react.js') },
            { find: /^react\/jsx-(dev-)?runtime$/, replacement: 'preact/jsx-runtime' },
            { find: /^react-dom$/, replacement: 'preact/compat' },
            { find: /^react-dom\/(client|server)$/, replacement: 'preact/compat/$1' },
        ],
    },
    ssr: {
        // Bundle every dependency into the SSR build so @inertiajs/react resolves
        // `react` through the preact/compat alias instead of Node's resolver.
        noExternal: true,
    },
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
}));
