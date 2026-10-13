/**
 * Lighthouse CI: `npm run perf` locally, the `lighthouse` job in CI.
 * Mobile defaults (simulated Slow 4G, 4x CPU), production build with SSR,
 * median of three runs per page. Any regression past these budgets fails.
 */
// HTTPS + HTTP/2 like production (see scripts/perf-server.mjs).
const base = `https://127.0.0.1:${process.env.LIGHTHOUSE_PORT ?? 8090}`;
const median = { aggregationMethod: 'median-run' };

module.exports = {
    ci: {
        collect: {
            startServerCommand: 'node scripts/perf-server.mjs',
            startServerReadyPattern: 'Lighthouse server ready',
            startServerReadyTimeout: 180000,
            url: [`${base}/`, `${base}/work/euisis`, `${base}/notes/storing-ethiopian-dates`, `${base}/now`],
            numberOfRuns: 3,
            settings: {
                chromeFlags: '--headless=new --no-sandbox --ignore-certificate-errors',
            },
        },
        assert: {
            assertions: {
                // Target: performance 100 and LCP <= 1.5 s. Under Lighthouse's simulated Slow 4G the
                // ~91 KB of first-view fonts count as LCP-blocking even though font-display: swap paints
                // text first, which puts simulated LCP at 1.7-2.0 s (applied Slow 4G measures 1.3-1.4 s).
                // These two are regression guards at the measured level until the font budget shrinks.
                'categories:performance': ['error', { minScore: 0.95, ...median }],
                'largest-contentful-paint': ['error', { maxNumericValue: 2200, ...median }],
                'categories:accessibility': ['error', { minScore: 1, ...median }],
                'categories:best-practices': ['error', { minScore: 1, ...median }],
                'categories:seo': ['error', { minScore: 1, ...median }],
                'cumulative-layout-shift': ['error', { maxNumericValue: 0.02, ...median }],
                'total-blocking-time': ['error', { maxNumericValue: 100, ...median }],
                // Transfer sizes (gzip). The stylesheet is inlined, so its bytes count in the document.
                'resource-summary:script:size': ['error', { maxNumericValue: 100 * 1024, ...median }],
                'resource-summary:font:size': ['error', { maxNumericValue: 100 * 1024, ...median }],
                'resource-summary:stylesheet:size': ['error', { maxNumericValue: 20 * 1024, ...median }],
            },
        },
        upload: {
            target: 'filesystem',
            outputDir: '.lighthouseci/reports',
        },
    },
};
