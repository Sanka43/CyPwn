<?php

declare(strict_types=1);

$path = dirname(__DIR__) . '/assets/repo/packages.json';

if (!is_readable($path)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode(['error' => 'packages.json not found']);
    exit;
}

$mtime = (string) filemtime($path);
$etag = '"' . md5($path . $mtime) . '"';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('ETag: ' . $etag);

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}

$json = (string) file_get_contents($path);
$acceptsGzip = str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip');

if ($acceptsGzip && function_exists('gzencode')) {
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    echo gzencode($json, 6);
    exit;
}

echo $json;