/**
 * Lighthouse CI: `npm run perf` locally, the `lighthouse` job in CI.
 * Mobile defaults (simulated Slow 4G, 4x CPU), production build with SSR,
 * median of three runs per page. Any regression past these budgets fails.
 */
// HTTPS + HTTP/2 like production (see scripts/perf-server.mjs).
const base = `https://127.0.0.1:${process.env.LIGHTHOUSE_PORT ?? 8090}`;
// Categories are judged on the median run; each timing metric on its own median across the
// three runs, which is steadier on a busy machine (one slow run cannot fail the budget alone).
const median = { aggregationMethod: 'median-run' };
const metricMedian = { aggregationMethod: 'median' };

/** Shared by every page; the font and LCP guards differ per language below. */
const common = {
    'categories:performance': ['error', { minScore: 0.95, ...median }],
    'categories:accessibility': ['error', { minScore: 1, ...median }],
    'categories:best-practices': ['error', { minScore: 1, ...median }],
    'categories:seo': ['error', { minScore: 1, ...median }],
    'cumulative-layout-shift': ['error', { maxNumericValue: 0.02, ...metricMedian }],
    'total-blocking-time': ['error', { maxNumericValue: 100, ...metricMedian }],
    // Transfer sizes (gzip). The stylesheet is inlined, so its bytes count in the document.
    'resource-summary:script:size': ['error', { maxNumericValue: 100 * 1024, ...median }],
    'resource-summary:stylesheet:size': ['error', { maxNumericValue: 20 * 1024, ...median }],
};

module.exports = {
    ci: {
        collect: {
            startServerCommand: 'node scripts/perf-server.mjs',
            startServerReadyPattern: 'Lighthouse server ready',
            startServerReadyTimeout: 180000,
            url: [`${base}/`, `${base}/work/euisis`, `${base}/notes/storing-ethiopian-dates`, `${base}/now`, `${base}/am`],
            numberOfRuns: 3,
            settings: {
                chromeFlags: '--headless=new --no-sandbox --ignore-certificate-errors',
            },
        },
        assert: {
            assertMatrix: [
                {
                    // English and single-language pages.
                    matchingUrlPattern: '^https://[^/]+/(?!am(/|$))',
                    assertions: {
                        ...common,
                        // Target: performance 100 and LCP <= 1.5 s. Under Lighthouse's simulated Slow 4G the
                        // ~91 KB of first-view fonts count as LCP-blocking even though font-display: swap paints
                        // text first, which puts simulated LCP at 1.7-2.0 s (applied Slow 4G measures 1.3-1.4 s).
                        // This is a regression guard at the measured level until the font budget shrinks.
                        'largest-contentful-paint': ['error', { maxNumericValue: 2200, ...metricMedian }],
                        'resource-summary:font:size': ['error', { maxNumericValue: 100 * 1024, ...median }],
                    },
                },
                {
                    // Amharic pages also need the Amharic syllabary (~57 KB, see scripts/fonts.mjs), so the
                    // first view is ~149 KB of fonts and simulated LCP ~2.2 s. Guards at the measured level.
                    matchingUrlPattern: '^https://[^/]+/am(/|$)',
                    assertions: {
                        ...common,
                        'largest-contentful-paint': ['error', { maxNumericValue: 2500, ...metricMedian }],
                        'resource-summary:font:size': ['error', { maxNumericValue: 155 * 1024, ...median }],
                    },
                },
            ],
        },
        upload: {
            target: 'filesystem',
            outputDir: '.lighthouseci/reports',
        },
    },
};
