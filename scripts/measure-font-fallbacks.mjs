/**
 * Measures the size-adjust each local fallback needs so its text runs as wide as the web font,
 * per weight / stretch range actually used in the CSS. Same widths mean the same line breaks,
 * so swapping to the web font moves nothing (CLS 0 even on slow connections).
 *
 *   node scripts/measure-font-fallbacks.mjs   →  writes scripts/font-fallbacks.json (committed)
 *
 * Needs a Chromium: Playwright's (`npx playwright install chromium`) or set CHROME_PATH.
 * Re-run after changing the subset fonts, then `npm run fonts`.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const { chromium } = await import('playwright-core').catch(() => import('@playwright/test'));

/** Representative copy: headings, labels and body text from the site. */
const SAMPLE = 'Systems that run institutions. Identities that represent them. Two disciplines, one standard. '
    + 'From paper process to working system. Where I have worked and studied. Let us build something that lasts. '
    + 'Software Developer & Graphic Designer. Case study 2 of 4, 2026. Employee records, digital IDs and recruitment.';

/**
 * The fallback faces: each maps a weight/stretch range of the web font to a local font, measured at
 * the setting the CSS uses most inside that range.
 */
export const FALLBACKS = [
    { family: 'Archivo', local: 'Arial', weight: [300, 599], stretch: [62, 95], at: { weight: 500, stretch: 88 } },
    { family: 'Archivo', local: 'Arial', weight: [600, 800], stretch: [62, 95], at: { weight: 800, stretch: 80 } },
    { family: 'Archivo', local: 'Arial', weight: [300, 599], stretch: [96, 125], at: { weight: 500, stretch: 100 } },
    { family: 'Archivo', local: 'Arial', weight: [600, 800], stretch: [96, 125], at: { weight: 700, stretch: 110 } },
    { family: 'IBM Plex Sans', local: 'Arial', weight: [400, 549], at: { weight: 400 } },
    { family: 'IBM Plex Sans', local: 'Arial', weight: [550, 600], at: { weight: 600 } },
    { family: 'JetBrains Mono', local: 'Courier New', weight: [400, 500], at: { weight: 400 } },
];

const FILES = {
    Archivo: 'resources/fonts/archivo-latin-critical.woff2',
    'IBM Plex Sans': 'resources/fonts/ibm-plex-sans-latin-critical.woff2',
    'JetBrains Mono': 'resources/fonts/jetbrains-mono-latin-critical.woff2',
};

if (import.meta.url === `file:///${process.argv[1]?.replace(/\\/g, '/').replace(/^\//, '')}` || process.argv[1]?.endsWith('measure-font-fallbacks.mjs')) {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    const page = await browser.newPage();
    await page.setContent('<!doctype html><body></body>');

    for (const [family, file] of Object.entries(FILES)) {
        const data = readFileSync(join(root, file)).toString('base64');
        await page.evaluate(async ({ family, data, variable }) => {
            const bytes = Uint8Array.from(atob(data), (c) => c.charCodeAt(0));
            const face = new FontFace(family, bytes, variable ? { weight: '300 800', stretch: '62% 125%' } : { weight: '300 800' });
            document.fonts.add(await face.load());
        }, { family, data, variable: family === 'Archivo' });
    }

    const results = [];
    for (const fallback of FALLBACKS) {
        const widths = await page.evaluate(({ fallback, sample }) => {
            const measure = (family, weight, stretch) => {
                const span = document.createElement('span');
                span.textContent = sample;
                span.style.cssText = `font: ${weight} 100px '${family}'; font-stretch: ${stretch}%; white-space: nowrap; position: absolute;`;
                document.body.appendChild(span);
                const width = span.getBoundingClientRect().width;
                span.remove();
                return width;
            };
            const { weight, stretch = 100 } = fallback.at;
            // Local fallbacks have no width axis and only regular/bold: compare against what the browser would draw.
            return { web: measure(fallback.family, weight, stretch), local: measure(fallback.local, weight >= 600 ? 700 : 400, 100) };
        }, { fallback, sample: SAMPLE });
        results.push({ ...fallback, sizeAdjust: Number((widths.web / widths.local).toFixed(4)) });
    }
    await browser.close();

    writeFileSync(join(root, 'scripts/font-fallbacks.json'), `${JSON.stringify(results, null, 4)}\n`);
    for (const r of results) console.log(`${r.family.padEnd(15)} ${String(r.weight).padEnd(8)} ${String(r.stretch ?? '').padEnd(7)} → ${r.local.padEnd(12)} size-adjust ${(r.sizeAdjust * 100).toFixed(2)}%`);
}
