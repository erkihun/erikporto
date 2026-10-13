/**
 * Builds the self-hosted font files and resources/css/fonts.css.
 *
 * Sources are the fontsource variable fonts (devDependencies, OFL). Each family is
 * split by unicode-range so a first visit downloads only what it renders:
 *   - latin-critical: ASCII plus the punctuation the UI uses (loaded on first view)
 *   - latin-rest / latin-ext: accented letters and the rest, fetched only when used
 *   - Ethiopic: a "core" file with the glyphs the English page shows (footer date,
 *     brand mock) and the rest of the block for Amharic content.
 * Fallback faces get metric overrides so swapping to the web font shifts nothing.
 *
 * Run `npm run fonts` after changing a character set; the output is committed.
 */
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import archivoMetrics from '@capsizecss/metrics/archivo';
import ibmPlexSansMetrics from '@capsizecss/metrics/ibmPlexSans';
import jetBrainsMonoMetrics from '@capsizecss/metrics/jetBrainsMono';
import subsetFont from 'subset-font';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const fontsDir = join(root, 'resources/fonts');
const cssFile = join(root, 'resources/css/fonts.css');
const fontsource = (pkg) => join(root, 'node_modules/@fontsource-variable', pkg);

/** ASCII symbols that copy rarely uses; they live in the on-demand "rest" file. */
const RARE_ASCII = '$*<=>[\\]^_`{|}~';

/**
 * ASCII (minus the rare symbols) plus the typographic characters used in the UI and likely in copy.
 * Control characters are included too: they have no glyphs, but Markdown soft line breaks leave a
 * U+000A in paragraph text and the browser matches it against unicode-range, which would otherwise
 * download a whole extra file just for a newline.
 */
const CRITICAL_LATIN = [
    [0x00, 0x1f], [0x7f, 0x9f],
    ...Array.from({ length: 0x7e - 0x20 + 1 }, (_, i) => 0x20 + i).filter((cp) => !RARE_ASCII.includes(String.fromCodePoint(cp))),
    0xa0, 0xa9, 0xb7, 0xd7, 0x2013, 0x2014, 0x2018, 0x2019, 0x201c, 0x201d, 0x2022, 0x2026,
    [0x2190, 0x2193], 0x2197, 0x2713, 0x29c9,
];

/** Ethiopic glyphs on the English page: month names and ዓ.ም. in the footer, the brand mock, the "አማ" language chip, plus ፡ ።. */
const ETHIOPIC_CORE_TEXT = 'ሀለሐመስከረምጥቅትኅዳርታሣሥየካቲጋቢሚያዝግንቦሰኔሌነሴጳጉሜዓአማ፡።';

/**
 * What Amharic copy needs, preloaded on /am only: every order (ä u i a e ə o + the -wa form) of the
 * consonants Amharic uses, the labialised velars (ቈ ኈ ኰ ጐ rows) and the punctuation ፡–፨. Ge'ez-only
 * and extended letters and Ethiopic numerals stay in the "rest" file, loaded only if a page uses them.
 */
const AMHARIC_ROWS = [
    0x1200, 0x1208, 0x1210, 0x1218, 0x1220, 0x1228, 0x1230, 0x1238, 0x1240, 0x1260, 0x1268, 0x1270, 0x1278, 0x1280, 0x1290, 0x1298,
    0x12a0, 0x12a8, 0x12b8, 0x12c8, 0x12d0, 0x12d8, 0x12e0, 0x12e8, 0x12f0, 0x12f8, 0x1300, 0x1308, 0x1320, 0x1328, 0x1330, 0x1338,
    0x1340, 0x1348, 0x1350,
];
const AMHARIC = [
    ...AMHARIC_ROWS.map((row) => [row, row + 7]),
    ...[0x1248, 0x1288, 0x12b0, 0x1310].map((row) => [row, row + 5]),
    [0x1361, 0x1368],
];

/** Digits and punctuation in the footer's Ethiopian date, drawn with Noto Sans Ethiopic's Latin. */
const ETHIOPIC_DATE_LATIN = ' ,.0123456789';

const FAMILIES = [
    {
        family: 'Archivo',
        slug: 'archivo',
        pkg: 'archivo',
        css: 'wdth.css',
        // wght starts at 300 like the previously loaded Google font, so weight 200 keeps rendering as 300.
        axes: { wght: { min: 300, max: 800 }, wdth: { min: 62, max: 125 } },
        descriptors: { 'font-weight': '300 800', 'font-stretch': '62% 125%' },
        preload: true,
    },
    {
        family: 'IBM Plex Sans',
        slug: 'ibm-plex-sans',
        pkg: 'ibm-plex-sans',
        css: 'index.css',
        axes: { wght: { min: 400, max: 600 } },
        descriptors: { 'font-weight': '400 600' },
    },
    {
        family: 'JetBrains Mono',
        slug: 'jetbrains-mono',
        pkg: 'jetbrains-mono',
        css: 'index.css',
        axes: { wght: { min: 400, max: 500 } },
        descriptors: { 'font-weight': '400 500' },
        // Code blocks in notes use every ASCII symbol, so the mono face keeps all of ASCII on
        // first view. Programming ligatures are left out (plain code, and they would triple the file).
        criticalAscii: 'all',
        noLayoutClosure: true,
    },
    {
        family: 'Noto Sans Ethiopic',
        slug: 'noto-sans-ethiopic',
        pkg: 'noto-sans-ethiopic',
        css: 'index.css',
        axes: { wght: { min: 400, max: 800 } },
        descriptors: { 'font-weight': '400 800' },
        ethiopic: true,
    },
];

// ---------------------------------------------------------------------------

const expand = (spec) => spec.flatMap((s) => (Array.isArray(s) ? Array.from({ length: s[1] - s[0] + 1 }, (_, i) => s[0] + i) : [s]));

const parseRange = (range) =>
    range.split(',').flatMap((part) => {
        const [a, b] = part.trim().replace(/^U\+/i, '').split('-');
        const start = parseInt(a, 16);
        const end = b ? parseInt(b, 16) : start;
        return Array.from({ length: end - start + 1 }, (_, i) => start + i);
    });

/** Compacts code points into a CSS unicode-range value. */
const toRange = (codePoints) => {
    const sorted = [...new Set(codePoints)].sort((a, b) => a - b);
    const parts = [];
    for (let i = 0; i < sorted.length; i++) {
        let j = i;
        while (j + 1 < sorted.length && sorted[j + 1] === sorted[j] + 1) j++;
        const hex = (n) => n.toString(16).toUpperCase().padStart(4, '0');
        parts.push(i === j ? `U+${hex(sorted[i])}` : `U+${hex(sorted[i])}-${hex(sorted[j])}`);
        i = j;
    }
    return parts.join(', ');
};

/** Reads fontsource's CSS to map each subset (latin, latin-ext, ethiopic, …) to its file and unicode-range. */
async function subsets(pkg, cssName) {
    const css = await readFile(join(fontsource(pkg), cssName), 'utf8');
    const map = {};
    for (const block of css.split('@font-face').slice(1)) {
        const file = block.match(/url\(\.\/files\/([^)]+\.woff2)\)/)?.[1];
        const range = block.match(/unicode-range:\s*([^;]+);/)?.[1];
        // Strip the family prefix first: "noto-sans-ethiopic-latin-…" names the latin subset.
        const subset = file?.slice(pkg.length + 1).match(/^(latin-ext|latin|ethiopic)-/)?.[1];
        if (file && range && subset && !map[subset]) map[subset] = { file: join(fontsource(pkg), 'files', file), codePoints: parseRange(range) };
    }
    return map;
}

async function build(font, name, source, codePoints) {
    const text = String.fromCodePoint(...codePoints);
    const output = await subsetFont(await readFile(source), text, {
        targetFormat: 'woff2',
        variationAxes: font.axes,
        noLayoutClosure: font.noLayoutClosure ?? false,
    });
    const file = `${font.slug}-${name}.woff2`;
    await writeFile(join(fontsDir, file), output);
    console.log(`${file.padEnd(40)} ${(output.length / 1024).toFixed(1).padStart(6)} KB  (${codePoints.length} code points)`);
    return { file, range: toRange(codePoints) };
}

const face = (font, { file, range }) => {
    const descriptors = Object.entries(font.descriptors).map(([k, v]) => `    ${k}: ${v};\n`).join('');
    return `@font-face {\n    font-family: '${font.family}';\n    src: url('../fonts/${file}') format('woff2');\n${descriptors}    font-style: normal;\n    font-display: swap;\n    unicode-range: ${range};\n}\n`;
};

/**
 * Local fallback faces per weight/stretch range (measured by scripts/measure-font-fallbacks.mjs into
 * scripts/font-fallbacks.json): size-adjust makes the local font run as wide as the web font, and the
 * ascent/descent/line-gap overrides give it the web font's line box, so the swap moves nothing.
 */
const METRICS = { Archivo: archivoMetrics, 'IBM Plex Sans': ibmPlexSansMetrics, 'JetBrains Mono': jetBrainsMonoMetrics };
const LOCALS = {
    Arial: { regular: ['Arial', 'ArialMT', 'Liberation Sans'], bold: ['Arial Bold', 'Arial-BoldMT', 'Liberation Sans Bold'] },
    'Courier New': { regular: ['Courier New', 'CourierNewPSMT', 'Liberation Mono'], bold: ['Courier New Bold', 'CourierNewPS-BoldMT', 'Liberation Mono Bold'] },
};
const fallbackFace = ({ family, local: localFamily, weight, stretch, sizeAdjust }) => {
    const metrics = METRICS[family];
    const em = metrics.unitsPerEm * sizeAdjust;
    const local = LOCALS[localFamily][weight[0] >= 600 ? 'bold' : 'regular'];
    const name = `${family} Fallback`;
    const pct = (n) => `${(n * 100).toFixed(2)}%`;
    const range = `    font-weight: ${weight[0]} ${weight[1]};\n${stretch ? `    font-stretch: ${stretch[0]}% ${stretch[1]}%;\n` : ''}`;
    return `@font-face {\n    font-family: '${name}';\n    src: ${local.map((l) => `local('${l}')`).join(', ')};\n${range}    size-adjust: ${pct(sizeAdjust)};\n    ascent-override: ${pct(metrics.ascent / em)};\n    descent-override: ${pct(Math.abs(metrics.descent) / em)};\n    line-gap-override: ${pct(metrics.lineGap / em)};\n}\n`;
};

await mkdir(fontsDir, { recursive: true });
const critical = expand(CRITICAL_LATIN);
const faces = [];
const fallbacks = [];
const preloads = [];

for (const font of FAMILIES) {
    const map = await subsets(font.pkg, font.css);

    if (font.ethiopic) {
        const core = [...ETHIOPIC_CORE_TEXT].map((c) => c.codePointAt(0));
        const amharic = expand(AMHARIC).filter((cp) => map.ethiopic.codePoints.includes(cp) && !core.includes(cp));
        const rest = map.ethiopic.codePoints.filter((cp) => !core.includes(cp) && !amharic.includes(cp));
        faces.push(face(font, await build(font, 'ethiopic-core', map.ethiopic.file, core)));
        faces.push(face(font, await build(font, 'ethiopic-amharic', map.ethiopic.file, amharic)));
        faces.push(face(font, await build(font, 'ethiopic-rest', map.ethiopic.file, rest)));
        faces.push(face(font, await build(font, 'date-latin', map.latin.file, [...ETHIOPIC_DATE_LATIN].map((c) => c.codePointAt(0)))));
        continue;
    }

    const fullAscii = font.criticalAscii === 'all' ? Array.from({ length: 0x7e - 0x20 + 1 }, (_, i) => 0x20 + i) : [];
    const inLatin = [...new Set([...critical, ...fullAscii])]
        .filter((cp) => map.latin.codePoints.includes(cp) || (cp >= 0x2190 && cp <= 0x29ff))
        .sort((a, b) => a - b);
    const rest = map.latin.codePoints.filter((cp) => !inLatin.includes(cp));
    const criticalFace = await build(font, 'latin-critical', map.latin.file, inLatin);
    faces.push(face(font, criticalFace));
    faces.push(face(font, await build(font, 'latin-rest', map.latin.file, rest)));
    faces.push(face(font, await build(font, 'latin-ext', map['latin-ext'].file, map['latin-ext'].codePoints.filter((cp) => !inLatin.includes(cp)))));
    if (font.preload) preloads.push(`resources/fonts/${criticalFace.file}`);
}

for (const entry of JSON.parse(await readFile(join(root, 'scripts/font-fallbacks.json'), 'utf8'))) fallbacks.push(fallbackFace(entry));

const header = `/*\n * Generated by scripts/fonts.mjs. Do not edit by hand: change the script and run \`npm run fonts\`.\n * First-view files (latin-critical, ethiopic-core, date-latin; ethiopic-amharic on /am) are preloaded by App\\Support\\Fonts.\n */\n`;
await writeFile(cssFile, `${header}\n${faces.join('\n')}\n/* Metric-matched local fallbacks: the swap to the web font causes no layout shift. */\n${fallbacks.join('\n')}`);
console.log(`\nwrote ${cssFile.replace(root + '\\', '').replace(root + '/', '')}`);
