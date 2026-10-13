import { defineConfig, devices } from '@playwright/test';

/**
 * Browser tests (`npm run test:browser`, the `a11y` CI job): axe accessibility checks and
 * keyboard flows against the production-like server from scripts/perf-server.mjs
 * (SSR, built assets, seeded demo content). Locally, CHROME_PATH can point at an
 * installed Chrome instead of `npx playwright install chromium`.
 */
const port = Number(process.env.LIGHTHOUSE_PORT ?? 8090);
const baseURL = `https://127.0.0.1:${port}`;

export default defineConfig({
    testDir: 'tests/Browser',
    // One server, one seeded database: run in order.
    workers: 1,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never', outputFolder: 'storage/logs/playwright' }]] : 'list',
    use: {
        baseURL,
        ignoreHTTPSErrors: true,
        trace: 'retain-on-failure',
        launchOptions: process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {},
    },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 900 } } },
        { name: 'mobile', use: { ...devices['Pixel 7'] } },
    ],
    webServer: {
        command: 'node scripts/perf-server.mjs',
        url: `${baseURL}/up`,
        ignoreHTTPSErrors: true,
        timeout: 180_000,
        reuseExistingServer: !process.env.CI,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
