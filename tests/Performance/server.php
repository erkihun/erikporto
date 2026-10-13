<?php

declare(strict_types=1);
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

/*
 * Router for PHP's built-in server used by Lighthouse CI (scripts/perf-server.mjs).
 * It approximates the production nginx setup closely enough for budgets:
 * gzip through zlib.output_compression, immutable caching for /build, and the
 * app always uses the production build (a running Vite dev server is ignored).
 *
 *   php -d zlib.output_compression=1 -S 127.0.0.1:8000 -t public tests/Performance/server.php
 */

$root = dirname(__DIR__, 2);
$public = $root.'/public';
$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$file = realpath($public.$uri);
$publicReal = (string) realpath($public);

if ($uri !== '/' && $file !== false && is_file($file) && str_starts_with($file, $publicReal) && basename($file) !== 'hot') {
    $types = [
        'js' => 'text/javascript; charset=utf-8', 'css' => 'text/css; charset=utf-8', 'svg' => 'image/svg+xml',
        'woff2' => 'font/woff2', 'ico' => 'image/x-icon', 'png' => 'image/png', 'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'json' => 'application/json', 'txt' => 'text/plain; charset=utf-8',
        'xml' => 'application/xml',
    ];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if (in_array($extension, ['woff2', 'ico', 'png', 'jpg', 'jpeg', 'webp'], true)) {
        ini_set('zlib.output_compression', '0'); // already compressed
    }

    header('Content-Type: '.($types[$extension] ?? 'application/octet-stream'));
    header(str_starts_with($uri, '/build/')
        ? 'Cache-Control: public, max-age=31536000, immutable'
        : 'Cache-Control: public, max-age=3600');
    readfile($file);

    return true;
}

// The HTTP/2 front in scripts/perf-server.mjs terminates TLS and forwards plain HTTP.
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

define('LARAVEL_START', microtime(true));

require $root.'/vendor/autoload.php';

$app = require_once $root.'/bootstrap/app.php';
$app->booted(static fn () => Vite::useHotFile($root.'/storage/framework/lighthouse-no-hot'));
$app->handleRequest(Request::capture());
