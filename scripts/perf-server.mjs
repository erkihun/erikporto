/**
 * Starts a production-like copy of the site for Lighthouse CI (lighthouserc.cjs runs it).
 *
 * - Fresh SQLite database seeded with LighthouseSeeder (demo content + a note + a /now update),
 *   or the seeder named in PERF_SEEDER.
 * - Config, route and view caches written to an isolated directory (APP_*_CACHE, relative to the
 *   project because Laravel treats only paths starting with / or \ as absolute), so the
 *   developer's bootstrap/cache and .env are never touched.
 * - PHP's built-in server with opcache (when available), gzip and the router in
 *   tests/Performance/server.php, plus the Inertia SSR server from bootstrap/ssr.
 * - In front of PHP, an HTTP/2 + TLS proxy (Node's http2, throwaway self-signed certificate from
 *   `openssl`), because production nginx serves HTTP/2 and Lighthouse's network simulation models
 *   HTTP/1.1 (up to six cold TCP connections) very differently from one multiplexed connection.
 *
 * Requires `npm run build` first and `openssl` on the PATH. Prints "Lighthouse server ready".
 */
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { request as httpRequest } from 'node:http';
import { createSecureServer } from 'node:http2';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const port = Number(process.env.LIGHTHOUSE_PORT ?? 8090);
const backendPort = port + 1;
const php = process.env.PHP_BINARY ?? 'php';
const work = join(root, 'storage/framework/testing/lighthouse');

mkdirSync(work, { recursive: true });
const database = join(work, 'database.sqlite');
writeFileSync(database, '');

const env = {
    ...process.env,
    APP_ENV: 'lighthouse',
    APP_DEBUG: 'false',
    APP_URL: `https://127.0.0.1:${port}`,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    DB_URL: '',
    CACHE_STORE: 'database',
    SESSION_DRIVER: 'database',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    LOG_CHANNEL: 'stderr',
    LOG_LEVEL: 'error',
    INERTIA_SSR_ENABLED: 'true',
    TELEGRAM_BOT_TOKEN: '',
    TELEGRAM_CHANNEL_ID: '',
    APP_CONFIG_CACHE: 'storage/framework/testing/lighthouse/config.php',
    APP_ROUTES_CACHE: 'storage/framework/testing/lighthouse/routes.php',
    APP_EVENTS_CACHE: 'storage/framework/testing/lighthouse/events.php',
    APP_PACKAGES_CACHE: 'storage/framework/testing/lighthouse/packages.php',
    APP_SERVICES_CACHE: 'storage/framework/testing/lighthouse/services.php',
};

const fail = (message) => {
    console.error(message);
    process.exit(1);
};

const artisan = (...args) => {
    const result = spawnSync(php, ['artisan', ...args], { cwd: root, env, stdio: ['ignore', 'ignore', 'inherit'] });
    if (result.status !== 0) fail(`php artisan ${args.join(' ')} failed`);
};

// Clear first: a stale cached config would ignore the environment above.
artisan('config:clear');
artisan('route:clear');
// PERF_SEEDER picks another dataset, e.g. ProfileOnlySeeder for the empty states.
artisan('migrate:fresh', '--seed', `--seeder=Database\\Seeders\\${process.env.PERF_SEEDER ?? 'LighthouseSeeder'}`, '--force');
artisan('config:cache');
artisan('route:cache');
artisan('view:cache');

// Throwaway certificate for the HTTP/2 front (Chrome runs with --ignore-certificate-errors).
const keyFile = join(work, 'key.pem');
const certFile = join(work, 'cert.pem');
if (!existsSync(keyFile) || !existsSync(certFile)) {
    // Our own minimal config: a stale OPENSSL_CONF on the machine must not break this.
    const config = join(work, 'openssl.cnf');
    writeFileSync(config, '[req]\ndistinguished_name = dn\nprompt = no\nx509_extensions = ext\n[dn]\nCN = 127.0.0.1\n[ext]\nsubjectAltName = IP:127.0.0.1\n');
    const openssl = spawnSync('openssl', [
        'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '30',
        '-config', config, '-keyout', keyFile, '-out', certFile,
    ], { stdio: ['ignore', 'ignore', 'inherit'], env: { ...process.env, OPENSSL_CONF: config } });
    if (openssl.status !== 0) fail('openssl is required to create the test certificate for the HTTP/2 front.');
}

const hasOpcache = (flags) =>
    spawnSync(php, [...flags, '-r', "echo extension_loaded('Zend OPcache') ? 'yes' : 'no';"], { encoding: 'utf8' }).stdout.trim() === 'yes';
const opcache = hasOpcache([]) ? [] : hasOpcache(['-d', 'zend_extension=opcache']) ? ['-d', 'zend_extension=opcache'] : null;
const phpFlags = [
    ...(opcache ?? []),
    ...(opcache ? ['-d', 'opcache.enable=1', '-d', 'opcache.enable_cli=1', '-d', 'opcache.validate_timestamps=0'] : []),
    '-d', 'zlib.output_compression=1',
];
if (!opcache) console.warn('OPcache unavailable: response times will be slower than production.');

const children = [
    spawn(process.execPath, ['bootstrap/ssr/ssr.js'], { cwd: root, env, stdio: ['ignore', 'ignore', 'inherit'] }),
    spawn(php, [...phpFlags, '-S', `127.0.0.1:${backendPort}`, '-t', 'public', 'tests/Performance/server.php'], { cwd: root, env, stdio: ['ignore', 'ignore', 'ignore'] }),
];

// HTTP/2 front: forwards every request to PHP and marks it as HTTPS (see tests/Performance/server.php).
const HOP_BY_HOP = ['connection', 'keep-alive', 'proxy-connection', 'transfer-encoding', 'upgrade'];
const front = createSecureServer({ key: readFileSync(keyFile), cert: readFileSync(certFile), allowHTTP1: true }, (req, res) => {
    const headers = Object.fromEntries(Object.entries(req.headers).filter(([name]) => !name.startsWith(':') && !HOP_BY_HOP.includes(name)));
    headers.host = `127.0.0.1:${port}`;
    headers['x-forwarded-proto'] = 'https';

    const upstream = httpRequest({ host: '127.0.0.1', port: backendPort, method: req.method, path: req.url, headers }, (response) => {
        const out = Object.fromEntries(Object.entries(response.headers).filter(([name]) => !HOP_BY_HOP.includes(name)));
        res.writeHead(response.statusCode ?? 502, out);
        response.pipe(res);
    });
    upstream.on('error', () => {
        res.writeHead(502);
        res.end();
    });
    req.pipe(upstream);
});
front.listen(port, '127.0.0.1');

const stop = () => {
    front.close();
    for (const child of children) child.kill();
    process.exit(0);
};
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
for (const child of children) child.on('exit', (code) => code && code !== 0 && (console.error('A server exited early.'), stop()));

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const backend = (path) => fetch(`http://127.0.0.1:${backendPort}${path}`, { headers: { 'x-forwarded-proto': 'https', host: `127.0.0.1:${port}` } });
for (let attempt = 0; attempt < 60; attempt++) {
    try {
        const [ssr, app] = await Promise.all([fetch('http://127.0.0.1:13714/health'), backend('/up')]);
        if (ssr.ok && app.ok) {
            // Warm the page caches like a live site.
            for (const path of ['/', '/work/euisis', '/notes/storing-ethiopian-dates', '/now']) await backend(path);
            console.log(`Lighthouse server ready on https://127.0.0.1:${port} (HTTP/2)`);
            break;
        }
    } catch {
        // not up yet
    }
    await wait(500);
}
