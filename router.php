<?php
// Front controller for PHP's built-in server, which is what Railway runs.
// The built-in server does not honor .htaccess, so everything the Apache
// config used to handle has to live here instead.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';

// One canonical hostname. www redirects to the apex so search engines do not
// split ranking credit between two addresses serving identical pages.
if (strpos($host, 'www.') === 0) {
    $apex = substr($host, 4);
    header('Location: https://' . $apex . $_SERVER['REQUEST_URI'], true, 301);
    return true;
}

// The SQLite volume is mounted beneath the document root, and lib/ holds the
// application internals. Neither should ever be reachable over HTTP.
if (preg_match('#^/(?:data|lib)(?:/|$)#', $path) || preg_match('#/(?:\.|%2e)#i', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found";
    return true;
}

// Search engines expect /sitemap.xml. The .htaccess rewrite that used to
// provide it never runs here, so map it explicitly.
if ($path === '/sitemap.xml') {
    require __DIR__ . '/public/sitemap.php';
    return true;
}

// robots.txt is generated rather than static so the sitemap address always
// tracks the configured site_url instead of going stale on a domain change.
if ($path === '/robots.txt') {
    require_once __DIR__ . '/public/lib/bootstrap.php';
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\n";
    echo "Disallow: /desk/\n";
    echo "Allow: /\n\n";
    echo 'Sitemap: ' . site_url('/sitemap.xml') . "\n";
    return true;
}

return false;
