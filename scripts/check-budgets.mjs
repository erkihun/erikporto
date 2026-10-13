/**
 * Deterministic byte budgets from the production build (`npm run budgets`, after `npm run build`).
 * Lighthouse CI measures the same budgets in a browser; this check is exact and never flaky.
 *
 *   initial JS per page  ≤ 100 KB gzip  (app entry + page chunk + their static imports)
 *   stylesheet           ≤  20 KB gzip  (inlined into the HTML in production)
 *   first-view fonts     ≤ 100 KB       (the critical Latin + Ethiopic-core files)
 *   first-view fonts /am ≤ 150 KB       (the same plus the Amharic syllabary; a regression guard,
 *                                        Amharic body text cannot fit the 100 KB target)
 *
 * Exports `measureBuild()` for scripts that report the numbers (e.g. the colophon).
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { gzipSync } from 'node:zlib';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const KB = 1024;
export const BUDGETS = { js: 100 * KB, css: 20 * KB, fonts: 100 * KB, fontsAmharic: 150 * KB };

/** Fonts every first view downloads (see scripts/fonts.mjs): the unicode-range "critical" and "core" files. */
const FIRST_VIEW_FONTS = /resources\/fonts\/.*-(latin-critical|ethiopic-core|date-latin)\.woff2$/;
/** Added on /am: the Amharic syllabary. */
const AMHARIC_FONTS = /resources\/fonts\/.*-ethiopic-amharic\.woff2$/;

export function measureBuild(buildDir = join(root, 'public/build')) {
    const manifest = JSON.parse(readFileSync(join(buildDir, 'manifest.json'), 'utf8'));
    const gzip = (file) => gzipSync(readFileSync(join(buildDir, file))).length;
    const raw = (file) => readFileSync(join(buildDir, file)).length;

    const staticClosure = (key, seen = new Set()) => {
        if (seen.has(key) || !manifest[key]) return seen;
        seen.add(key);
        for (const dependency of manifest[key].imports ?? []) staticClosure(dependency, seen);
        return seen;
    };

    const entry = 'resources/js/app.tsx';
    const pages = Object.keys(manifest).filter((key) => key.startsWith('resources/js/pages/'));
    const js = Object.fromEntries(
        pages.map((page) => {
            const files = new Set([...staticClosure(entry), ...staticClosure(page)].map((key) => manifest[key].file));
            return [page.replace('resources/js/pages/', '').replace(/\.tsx$/, ''), [...files].reduce((sum, file) => sum + gzip(file), 0)];
        }),
    );

    const fontBytes = (pattern) => Object.keys(manifest)
        .filter((key) => pattern.test(key))
        .reduce((sum, key) => sum + raw(manifest[key].file), 0);
    const fonts = fontBytes(FIRST_VIEW_FONTS);

    return { js, css: gzip(manifest['resources/css/app.css'].file), fonts, fontsAmharic: fonts + fontBytes(AMHARIC_FONTS) };
}

if (import.meta.url === pathToFileURL(process.argv[1] ?? '').href) {
    const { js, css, fonts, fontsAmharic } = measureBuild();
    const kb = (bytes) => `${(bytes / KB).toFixed(1)} KB`;
    const rows = [
        ...Object.entries(js).map(([page, bytes]) => [`JS  ${page}`, bytes, BUDGETS.js]),
        ['CSS (inlined)', css, BUDGETS.css],
        ['Fonts, first view', fonts, BUDGETS.fonts],
        ['Fonts, first view /am', fontsAmharic, BUDGETS.fontsAmharic],
    ];
    let failed = false;
    for (const [label, bytes, budget] of rows) {
        const ok = bytes <= budget;
        failed ||= !ok;
        console.log(`${ok ? 'ok  ' : 'FAIL'}  ${label.padEnd(24)} ${kb(bytes).padStart(9)}  / ${kb(budget)}`);
    }
    if (failed) {
        console.error('\nBudget exceeded.');
        process.exit(1);
    }
}
