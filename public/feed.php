<?php
require_once __DIR__ . '/lib/bootstrap.php';

$posts = db()->query("SELECT * FROM posts WHERE status = 'published'
                      ORDER BY published_at DESC, id DESC LIMIT 20")->fetchAll();

$base = site_url();

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
  <title><?= e(setting('site_title')) ?></title>
  <link><?= e($base) ?>/</link>
  <description><?= e(setting('meta_description')) ?></description>
  <language>en-us</language>
<?php foreach ($posts as $p): ?>
  <item>
    <title><?= e($p['title']) ?></title>
    <link><?= e($base) ?>/post.php?post=<?= e($p['slug']) ?></link>
    <guid isPermaLink="true"><?= e($base) ?>/post.php?post=<?= e($p['slug']) ?></guid>
    <pubDate><?= date(DATE_RSS, strtotime($p['published_at'])) ?></pubDate>
    <category><?= e($p['category']) ?></category>
    <description><?= e($p['excerpt']) ?></description>
  </item>
<?php endforeach; ?>
</channel>
</rss>
