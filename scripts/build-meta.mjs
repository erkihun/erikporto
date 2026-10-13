/**
 * Facts about this build for the colophon (`npm run build` runs it last):
 * commit, build time, installed versions of the stack and the first-view byte counts
 * measured from the build itself (same numbers as `npm run budgets`).
 *
 * Writes public/build/build-meta.json, read by App\Support\BuildMeta. CI-only numbers
 * (test count, Lighthouse scores) come separately from scripts/quality-report.mjs.
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { measureBuild } from './check-budgets.mjs';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const json = (file) => JSON.parse(readFileSync(join(root, file), 'utf8'));

const composer = json('composer.lock');
const npm = json('package-lock.json');
const composerVersion = (name) => [...composer.packages, ...(composer['packages-dev'] ?? [])].find((p) => p.name === name)?.version?.replace(/^v/, '') ?? null;
const npmVersion = (name) => npm.packages?.[`node_modules/${name}`]?.version ?? null;

const git = (...args) => {
    try {
        return execFileSync('git', args, { cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim() || null;
    } catch {
        return null;
    }
};

const { js, css, fonts, fontsAmharic } = measureBuild();

const meta = {
    built_at: new Date().toISOString(),
    commit: process.env.GITHUB_SHA ?? git('rev-parse', 'HEAD'),
    committed_at: git('log', '-1', '--format=%cI'),
    stack: {
        laravel: composerVersion('laravel/framework'),
        filament: composerVersion('filament/filament'),
        inertia: npmVersion('@inertiajs/react'),
        preact: npmVersion('preact'),
        tailwindcss: npmVersion('tailwindcss'),
        vite: npmVersion('vite'),
    },
    // Bytes on first view of the home page (gzip for JS/CSS, woff2 as served for fonts).
    weight: { js: js.Home ?? null, css, fonts, fonts_amharic: fontsAmharic },
};

writeFileSync(join(root, 'public/build/build-meta.json'), JSON.stringify(meta, null, 2) + '\n');
console.log(`build-meta.json: ${meta.commit?.slice(0, 7) ?? 'no commit'}, JS ${(meta.weight.js / 1024).toFixed(1)} KB`);
