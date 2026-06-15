<?php

$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
$projectRoot = str_replace('\\', '/', dirname(__DIR__));

$basePath = str_replace($docRoot, '', $projectRoot);
$basePath = $basePath === '' ? '' : '/' . trim($basePath, '/');

define('BASE_PATH', $basePath);

function asset_url(string $path): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function page_url(string $path): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}
