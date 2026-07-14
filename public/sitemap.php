<?php
require_once __DIR__ . '/lib/bootstrap.php';

$posts = db()->query("SELECT slug, published_at, updated_at FROM posts WHERE status = 'published'
                      ORDER BY published_at DESC, id DESC")->fetchAll();

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= e(site_url('/')) ?></loc>
    <?php if ($posts): ?><lastmod><?= e(substr($posts[0]['updated_at'], 0, 10)) ?></lastmod><?php endif; ?>
  </url>
<?php foreach ($posts as $p): ?>
  <url>
    <loc><?= e(site_url('/post.php?post=' . rawurlencode($p['slug']))) ?></loc>
    <lastmod><?= e(substr($p['updated_at'], 0, 10)) ?></lastmod>
  </url>
<?php endforeach; ?>
</urlset>
