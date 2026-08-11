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

// Posts live at /the-slug. The old query-string form is kept working with a
// 301 so existing links, shares, and anything Google already indexed survive.
if ($path === '/post.php') {
    parse_str(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '', $query);
    if (!empty($query['post'])) {
        require_once __DIR__ . '/public/lib/bootstrap.php';
        $target = post_path($query['post']);
        unset($query['post']);
        if ($query) $target .= '?' . http_build_query($query);
        header('Location: ' . $target, true, 301);
        return true;
    }
}

// /the-slug/ and /the-slug should not be two addresses for one post.
if (preg_match('#^/([a-z0-9][a-z0-9-]*)/$#', $path, $m) && !is_dir(__DIR__ . '/public/' . $m[1])) {
    header('Location: /' . $m[1], true, 301);
    return true;
}

// A single path segment with no dot is a candidate post slug, but only once we
// know it is not a real file or directory such as /desk or /assets.
if (preg_match('#^/([a-z0-9][a-z0-9-]*)$#', $path, $m) && !file_exists(__DIR__ . '/public' . $path)) {
    $_GET['post'] = $m[1];
    $_REQUEST['post'] = $m[1];
    require __DIR__ . '/public/post.php';
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
