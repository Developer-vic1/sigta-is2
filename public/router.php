<?php
// Router de desarrollo para `php -S`; en Laragon configure DocumentRoot=public/.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) { return false; }
require __DIR__ . '/index.php';
