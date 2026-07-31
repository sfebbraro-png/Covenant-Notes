<?php
require_once __DIR__ . '/lib/bootstrap.php';

$slug = isset($_GET['post']) ? trim($_GET['post']) : '';
$stmt = db()->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
$stmt->execute(array($slug));
$post = $stmt->fetch();
$is_preview = $post && $post['status'] !== 'published';
if ($is_preview && !is_logged_in()) $post = null;

if (!$post) http_response_code(404);
$title = $post ? $post['title'] : 'Not found';
$post_url = $post ? site_url('/post.php?post=' . rawurlencode($post['slug'])) : site_url('/');
$facebook_share_url = $post ? 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($post_url) : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | <?= e(setting('site_title')) ?></title>
<?php if ($post): ?>
<meta name="description" content="<?= e($post['excerpt']) ?>">
<link rel="canonical" href="<?= e($post_url) ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="<?= e(setting('site_title')) ?>">
<meta property="og:title" content="<?= e($post['title']) ?>">
<meta property="og:description" content="<?= e($post['excerpt']) ?>">
<meta property="og:url" content="<?= e($post_url) ?>">
<meta property="og:image" content="<?= e(site_url(asset_path('/assets/og.png'))) ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1730">
<meta property="og:image:height" content="909">
<meta property="og:image:alt" content="<?= e($post['title']) ?> — <?= e(setting('site_title')) ?>">
<meta property="article:published_time" content="<?= e($post['published_at']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($post['title']) ?>">
<meta name="twitter:description" content="<?= e($post['excerpt']) ?>">
<meta name="twitter:image" content="<?= e(site_url(asset_path('/assets/og.png'))) ?>">
<script type="application/ld+json"><?= json_encode(array(
    '@context' => 'https://schema.org', '@type' => 'Article',
    'headline' => $post['title'], 'description' => $post['excerpt'],
    'datePublished' => $post['published_at'], 'dateModified' => $post['updated_at'],
    'url' => $post_url, 'author' => array('@type' => 'Person', 'name' => setting('author_name')),
    'publisher' => array('@type' => 'Organization', 'name' => setting('site_title'))
), JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset_path('/assets/site.css')) ?>">
</head>
<body class="approved-design approved-reading-page">
<header class="approved-header approved-reading-header">
  <nav class="approved-nav shell" aria-label="Main navigation">
    <a class="approved-brand" href="/" aria-label="<?= e(setting('site_title')) ?> home">
      <span class="approved-brand-mark" aria-hidden="true">C</span>
      <span><strong><?= e(setting('site_title')) ?></strong><small>Devotions &amp; essays by <?= e(setting('author_name', 'Steve Febbraro')) ?></small></span>
    </a>
    <div class="approved-nav-links">
      <a href="/#devotional">Devotions / Essays</a><a href="/#archive">Archive</a><a href="/#about">About</a><a href="/#newsletter">Newsletter</a>
    </div>
    <a class="approved-admin-link" href="/desk/" aria-label="Open the private writing desk"><span>Writing desk</span></a>
  </nav>
</header>

<?php if (!$post): ?>
<main class="shell-narrow approved-not-found">
  <p class="approved-eyebrow">Not found</p>
  <h1>That page has wandered off.</h1>
  <p><a class="approved-text-link" href="/">Return to the homepage &rarr;</a></p>
</main>
<?php else: ?>
<main>
  <article class="shell-narrow approved-reading-article">
    <p class="approved-eyebrow"><?= e(format_date($post['published_at'])) ?> &middot; <?= reading_time_minutes($post['body']) ?> min read<?php if ($is_preview): ?> &middot; Draft preview<?php endif; ?></p>
    <p class="approved-scripture-ref"><?= e($post['scripture'] !== '' ? $post['scripture'] : $post['category']) ?></p>
    <h1><?= e($post['title']) ?></h1>
    <p class="approved-reading-excerpt"><?= e($post['excerpt']) ?></p>
    <div class="approved-reading-rule"><span>&#10022;</span></div>
    <div class="approved-reading-body"><?= render_body($post['body']) ?></div>
    <div class="approved-reading-footer">
      <div class="approved-reading-actions">
        <a class="approved-text-link" href="/">&larr; Back to the homepage</a>
        <a class="approved-text-link" href="<?= e($facebook_share_url) ?>" target="_blank" rel="noopener">Share on Facebook <span aria-hidden="true">&#8599;</span></a>
      </div>
      <p>Soli Deo Gloria.</p>
    </div>
  </article>

  <section class="approved-newsletter approved-reading-newsletter" id="newsletter">
    <div class="shell approved-newsletter-inner">
      <p class="approved-eyebrow">A devotional in your inbox</p>
      <h2><?= e(setting('newsletter_heading')) ?></h2>
      <form class="approved-newsletter-form" method="post" action="/subscribe.php">
        <?= csrf_field() ?><input type="email" name="email" required placeholder="Your email address" aria-label="Email address"><button type="submit">Join the newsletter</button>
      </form>
    </div>
  </section>
</main>
<?php endif; ?>
</body>
</html>
