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

// Compress page output for browsers that accept gzip. Static files are
// handled separately below and opt out of this buffer before they are served.
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
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

// The homepage and the feed are files the built-in server would serve by
// itself, which bypasses the gzip buffer above. Route them through here so
// they are compressed like every other page.
if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/public/index.php';
    return true;
}

if ($path === '/feed.php') {
    require __DIR__ . '/public/feed.php';
    return true;
}

if ($path === '/about') {
    require __DIR__ . '/public/about.php';
    return true;
}

// Archive-style addresses from other platforms (/category/..., /tag/...,
// /author/..., /page/...) have no meaning here. They used to fall through to
// the homepage with a 200, which search engines treat as a soft 404. Send
// them to the same not-found page as any other unknown address.
if (preg_match('#^/(?:category|tag|author|page)(?:/|$)#', $path)) {
    $_GET['post'] = '';
    $_REQUEST['post'] = '';
    require __DIR__ . '/public/post.php';
    return true;
}

// Posts live at /the-slug. The old query-string form is kept working with a
// 301 so existing links, shares, and anything Google already indexed survive.
if ($path === '/post.php') {
    parse_str(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '', $query);
    if (!empty($query['post'])) {
        require_once __DIR__ . '/public/lib/bootstrap.php';
        $legacy_stmt = db()->prepare("SELECT status FROM posts WHERE slug = ? LIMIT 1");
        $legacy_stmt->execute(array($query['post']));
        $legacy_post = $legacy_stmt->fetch();
        if (!$legacy_post || $legacy_post['status'] !== 'published') {
            http_response_code(410);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Gone";
            return true;
        }
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

// Static files (the stylesheet, images, the favicon) get a 30-day cache
// lifetime, and text formats get gzip. Templates build asset addresses with
// a content fingerprint (?v=...), so a changed file gets a fresh address
// and the long lifetime cannot serve a stale copy. Pages are never cached
// here; only these files are.
if (preg_match('#^/(?:assets/.+|favicon\.ico)$#', $path)) {
    $asset_file = __DIR__ . '/public' . $path;
    $asset_types = array(
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff2' => 'font/woff2',
    );
    $asset_ext = strtolower(pathinfo($asset_file, PATHINFO_EXTENSION));
    if (is_file($asset_file) && isset($asset_types[$asset_ext])) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $asset_data = file_get_contents($asset_file);
        header('Content-Type: ' . $asset_types[$asset_ext]);
        header('Cache-Control: public, max-age=2592000');
        header('Vary: Accept-Encoding');
        if (in_array($asset_ext, array('css', 'js', 'svg'), true)
            && isset($_SERVER['HTTP_ACCEPT_ENCODING'])
            && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $asset_data = gzencode($asset_data, 6);
            header('Content-Encoding: gzip');
        }
        header('Content-Length: ' . strlen($asset_data));
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            echo $asset_data;
        }
        return true;
    }
}

// Anything else that reaches here is a real file the built-in server should
// serve itself (desk pages and their assets). Drop the gzip buffer so the
// server's own response is untouched.
while (ob_get_level() > 0) {
    ob_end_clean();
}
return false;
