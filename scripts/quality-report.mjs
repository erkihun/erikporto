/**
 * The CI numbers for the colophon: how many tests passed and the median Lighthouse scores.
 *
 *   node scripts/quality-report.mjs --junit storage/logs/junit.xml --lighthouse .lighthouseci --out quality.json
 *
 * CI publishes the file as the `quality.json` asset of the `quality` release; deploy/deploy.sh
 * downloads it to storage/app/quality.json, where App\Support\BuildMeta reads it. Missing
 * inputs are left out rather than guessed.
 */
import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const args = Object.fromEntries(
    process.argv.slice(2).reduce((pairs, arg, i, all) => (arg.startsWith('--') ? [...pairs, [arg.slice(2), all[i + 1]]] : pairs), []),
);

const report = { measured_at: new Date().toISOString(), commit: process.env.GITHUB_SHA ?? null };

if (args.junit && existsSync(args.junit)) {
    const xml = readFileSync(args.junit, 'utf8');
    // The root <testsuite> (or <testsuites>) carries the totals.
    const totals = xml.match(/<testsuites?\b[^>]*\btests="(\d+)"[^>]*>/);
    const attr = (name) => Number(totals?.[0].match(new RegExp(`\\b${name}="(\\d+)"`))?.[1] ?? 0);
    if (totals) {
        const failed = attr('failures') + attr('errors');
        report.tests = { total: attr('tests'), passed: attr('tests') - failed - attr('skipped'), skipped: attr('skipped'), failed };
    }
}

if (args.lighthouse && existsSync(join(args.lighthouse, 'manifest.json'))) {
    // lhci's manifest marks one representative (median) run per URL.
    const runs = JSON.parse(readFileSync(join(args.lighthouse, 'manifest.json'), 'utf8')).filter((run) => run.isRepresentativeRun);
    const pages = runs.map((run) => ({ url: new URL(run.url).pathname, ...run.summary }));
    const min = (key) => Math.round(Math.min(...pages.map((page) => page[key])) * 100);
    if (pages.length > 0) {
        report.lighthouse = {
            pages: pages.length,
            // The lowest median across pages: the score every page reaches.
            performance: min('performance'),
            accessibility: min('accessibility'),
            best_practices: min('best-practices'),
            seo: min('seo'),
        };
    }
} else if (args.lighthouse && existsSync(args.lighthouse)) {
    console.warn(`No manifest.json in ${args.lighthouse} (found: ${readdirSync(args.lighthouse).join(', ')})`);
}

writeFileSync(args.out ?? 'quality.json', JSON.stringify(report, null, 2) + '\n');
console.log(JSON.stringify(report));
