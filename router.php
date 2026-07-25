<?php
// The production SQLite volume is mounted beneath the document root. PHP's
// built-in server does not honor .htaccess, so block the data directory and
// dotfiles before allowing normal static/PHP routing.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#^/data(?:/|$)#', $path) || preg_match('#/(?:\.|%2e)#i', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found";
    return true;
}

return false;
