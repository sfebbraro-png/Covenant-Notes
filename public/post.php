<?php
require_once __DIR__ . '/lib/bootstrap.php';

$slug = isset($_GET['post']) ? $_GET['post'] : '';
$stmt = db()->prepare('SELECT * FROM posts WHERE slug = ?');
$stmt->execute(array($slug));
$post = $stmt->fetch();

$is_preview = $post && $post['status'] !== 'published';
if (!$post || ($is_preview && !is_logged_in())) {
    http_response_code(404);
    $title = 'Not found';
} else {
    $title = $post['title'];
    if (!$is_preview && !is_logged_in()) {
        db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute(array($post['id']));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(setting('site_title')) ?></title>
<?php if ($post && !$is_preview): $post_url = site_url('/post.php?post=' . rawurlencode($post['slug'])); ?>
<meta name="description" content="<?= e($post['excerpt']) ?>">
<link rel="canonical" href="<?= e($post_url) ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="<?= e(setting('site_title')) ?>">
<meta property="og:title" content="<?= e($post['title']) ?>">
<meta property="og:description" content="<?= e($post['excerpt']) ?>">
<meta property="og:url" content="<?= e($post_url) ?>">
<meta property="article:published_time" content="<?= e($post['published_at']) ?>">
<meta name="twitter:card" content="summary">
<script type="application/ld+json"><?= json_encode(array(
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $post['title'],
    'description' => $post['excerpt'],
    'datePublished' => $post['published_at'],
    'dateModified' => substr($post['updated_at'], 0, 10),
    'mainEntityOfPage' => $post_url,
    'author' => array('@type' => 'Person', 'name' => setting('author_name')),
    'publisher' => array('@type' => 'Organization', 'name' => setting('site_title')),
), JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<link rel="stylesheet" href="/assets/site.css">
</head>
<body>
<header class="site-header">
  <div class="wrap nav">
    <div class="brand-block">
      <a class="brand" href="/"><?= e(setting('brand_main')) ?> <span><?= e(setting('brand_accent')) ?></span></a>
      <?php if (setting('brand_tagline') !== ''): ?>
      <div class="tagline"><?= e(setting('brand_tagline')) ?></div>
      <?php endif; ?>
    </div>
    <nav class="nav-links">
      <a href="/#devotionals">Devotionals</a>
      <?php if (setting('substack_url') !== ''): ?>
      <a href="<?= e(setting('substack_url')) ?>" target="_blank" rel="noopener">Essays &#8599;</a>
      <?php endif; ?>
      <a href="/#about">About</a>
      <a href="/#archive">Archive</a>
      <a class="desk" href="/desk/">Writing Desk</a>
    </nav>
  </div>
</header>

<main>
<?php if (!$post || ($is_preview && !is_logged_in())): ?>
  <div class="wrap page-hero">
    <div class="eyebrow">Not found</div>
    <h1 style="font-size:clamp(2.4rem,5vw,4rem)">That page has wandered off.</h1>
    <p><a href="/">Return to the front page &rarr;</a></p>
  </div>
<?php else: ?>
  <div class="wrap page-hero">
    <div class="post-meta">
      <?= e($post['category']) ?> &middot; <?= e(format_date($post['published_at'])) ?>
      <?php if ($is_preview): ?> &middot; <span class="draft-flag">Draft preview</span><?php endif; ?>
    </div>
    <h1 style="font-size:clamp(2.6rem,6vw,5rem)"><?= e($post['title']) ?></h1>
    <p class="excerpt"><?= e($post['excerpt']) ?></p>
  </div>
  <article class="prose">
    <?= render_body($post['body']) ?>
    <hr>
    <p><a href="/#archive">&larr; Back to all writing</a></p>
    <?php if (setting('substack_url') !== ''): ?>
    <p><a href="<?= e(setting('substack_url')) ?>" target="_blank" rel="noopener">Essays and longer writing live on Substack &#8599;</a></p>
    <?php endif; ?>
  </article>

  <section class="newsletter" id="subscribe">
    <div class="wrap newsletter-inner">
      <h2><?= e(setting('newsletter_heading')) ?></h2>
      <?php if (isset($_GET['subscribed'])): ?>
        <p class="form-note"><strong>Thank you &mdash; you're on the list.</strong></p>
      <?php else: ?>
        <form class="email-form" method="post" action="/subscribe.php">
          <?= csrf_field() ?>
          <input type="hidden" name="return" value="/post.php?post=<?= e($post['slug']) ?>">
          <input type="email" name="email" required placeholder="your@email.com" aria-label="Email address">
          <button type="submit">Subscribe &rarr;</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>&copy; <?= e(setting('site_title')) ?> <span><?= date('Y') ?></span></div>
    <div><?= e(setting('footer_note')) ?></div>
  </div>
</footer>
</body>
</html>
