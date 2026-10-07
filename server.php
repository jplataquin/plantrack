<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @author   Taylor Otwell <taylor@laravel.com>
 */
$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($publicPath.$uri) && ! is_dir($publicPath.$uri)) {
    return false;
}

// Fallback for public storage assets (e.g., when Windows cannot resolve WSL symlinks or across dev environments)
if (str_starts_with($uri, '/storage/')) {
    $relativePath = substr($uri, strlen('/storage/'));
    $storageFile = __DIR__.'/storage/app/public/'.$relativePath;

    if (file_exists($storageFile) && ! is_dir($storageFile)) {
        $mime = mime_content_type($storageFile) ?: 'application/octet-stream';
        header('Content-Type: '.$mime);
        header('Content-Length: '.filesize($storageFile));
        header('Cache-Control: public, max-age=86400');
        readfile($storageFile);
        exit;
    }
}

require_once $publicPath.'/index.php';
