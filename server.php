<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server.
// Using is_file() ensures physical directories (such as /receipts or /accounts)
// are routed to Laravel's index.php rather than returning a PHP CLI 404.
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
