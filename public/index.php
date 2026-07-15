<?php
require_once __DIR__ . '/lib/bootstrap.php';

$posts = db()->query("SELECT * FROM posts WHERE status = 'published'
                      ORDER BY published_at DESC, id DESC")->fetchAll();

$featured = isset($posts[0]) ? $posts[0] : null;
$recent = array();
foreach ($posts as $p) {
    if (!$featured || $p['id'] !== $featured['id']) {
        $recent[] = $p;
    }
    if (count($recent) >= 3) break;
}
$devotional = null;
foreach ($posts as $p) {
    if ($p['category'] === 'Devotional') { $devotional = $p; break; }
}

$subscribed = isset($_GET['subscribed']);
$sub_error  = isset($_GET['sub_error']) ? $_GET['sub_error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_title')) ?> | Essays &amp; Devotionals</title>
<meta name="description" content="<?= e(setting('meta_description')) ?>">
<link rel="canonical" href="<?= e(site_url('/')) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(setting('site_title')) ?>">
<meta property="og:title" content="<?= e(setting('site_title')) ?>">
<meta property="og:description" content="<?= e(setting('meta_description')) ?>">
<meta property="og:url" content="<?= e(site_url('/')) ?>">
<meta name="twitter:card" content="summary">
<link rel="alternate" type="application/rss+xml" title="<?= e(setting('site_title')) ?>" href="/feed.php">
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
      <a href="#devotionals">Devotionals</a>
      <?php if (setting('substack_url') !== ''): ?>
      <a href="<?= e(setting('substack_url')) ?>" target="_blank" rel="noopener">Essays &#8599;</a>
      <?php endif; ?>
      <a href="#about">About</a>
      <a href="#archive">Archive</a>
      <a class="desk" href="/desk/">Writing Desk</a>
    </nav>
  </div>
</header>

<main>
  <section class="hero">
    <div class="wrap hero-grid">
      <div>
        <div class="eyebrow"><?= e(setting('hero_eyebrow')) ?></div>
        <h1><?= nl2br(e(setting('hero_title'))) ?></h1>
        <p><?= e(setting('hero_intro')) ?></p>
      </div>
      <aside class="hero-aside">&ldquo;<?= e(setting('hero_quote')) ?>&rdquo;</aside>
    </div>
  </section>

  <?php if ($featured): ?>
  <section class="section" id="latest">
    <div class="wrap">
      <div class="section-heading">
        <div>
          <div class="eyebrow">From the journal</div>
          <h2>Latest writing</h2>
        </div>
      </div>
      <div class="featured">
        <div class="featured-copy">
          <div class="post-meta"><?= e($featured['category']) ?> &middot; <?= e(format_date($featured['published_at'])) ?></div>
          <h2><?= e($featured['title']) ?></h2>
          <p class="excerpt"><?= e($featured['excerpt']) ?></p>
          <a class="read-more" href="/post.php?post=<?= e($featured['slug']) ?>">Read the <?= e(strtolower($featured['category'])) ?> &rarr;</a>
        </div>
        <div class="featured-art" aria-hidden="true"></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($recent): ?>
  <section class="section">
    <div class="wrap">
      <div class="section-heading">
        <div>
          <div class="eyebrow">Read slowly</div>
          <h2>Recent writing</h2>
        </div>
        <a class="read-more" href="#archive">Browse the archive &rarr;</a>
      </div>
      <div class="post-grid">
        <?php foreach ($recent as $p): ?>
        <article class="post-card">
          <div class="post-meta"><?= e($p['category']) ?> &middot; <?= e(format_date($p['published_at'])) ?></div>
          <h3><a href="/post.php?post=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h3>
          <p><?= e($p['excerpt']) ?></p>
          <a class="read-more" href="/post.php?post=<?= e($p['slug']) ?>">Read more &rarr;</a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($devotional): ?>
  <section class="devotional" id="devotionals">
    <div class="wrap devotional-grid">
      <div>
        <div class="eyebrow">A short devotional</div>
        <p>For a pause in the middle of your day.</p>
      </div>
      <div>
        <blockquote>&ldquo;<?= e($devotional['excerpt']) ?>&rdquo;</blockquote>
        <cite>&mdash; <a href="/post.php?post=<?= e($devotional['slug']) ?>"><?= e($devotional['title']) ?></a></cite>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="section" id="about">
    <div class="wrap about">
      <div>
        <div class="eyebrow"><?= e(setting('about_eyebrow')) ?></div>
        <h2><?= e(setting('about_heading')) ?></h2>
      </div>
      <div class="about-copy">
        <?= render_body(setting('about_body')) ?>
      </div>
    </div>
  </section>

  <?php if (setting('substack_url') !== ''): ?>
  <section class="section" id="substack">
    <div class="wrap about">
      <div>
        <div class="eyebrow">Beyond the devotional</div>
        <h2><?= e(setting('substack_heading')) ?></h2>
      </div>
      <div class="about-copy">
        <p><?= e(setting('substack_text')) ?></p>
        <a class="read-more" href="<?= e(setting('substack_url')) ?>" target="_blank" rel="noopener">Read the essays &#8599;</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="section" id="archive">
    <div class="wrap">
      <div class="section-heading">
        <div>
          <div class="eyebrow">The archive</div>
          <h2>All writing</h2>
        </div>
        <p><?= count($posts) ?> <?= count($posts) === 1 ? 'piece' : 'pieces' ?></p>
      </div>
      <ul class="archive-list">
        <?php foreach ($posts as $p): ?>
        <li>
          <small><?= e(format_date($p['published_at'])) ?></small>
          <a href="/post.php?post=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
          <span class="post-meta"><?= e($p['category']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="newsletter" id="subscribe">
    <div class="wrap newsletter-inner">
      <h2><?= e(setting('newsletter_heading')) ?></h2>
      <?php if ($subscribed): ?>
        <p class="form-note"><strong>Thank you &mdash; you're on the list.</strong></p>
      <?php else: ?>
        <form class="email-form" method="post" action="/subscribe.php">
          <?= csrf_field() ?>
          <input type="email" name="email" required placeholder="your@email.com" aria-label="Email address">
          <button type="submit">Subscribe &rarr;</button>
        </form>
      <?php endif; ?>
      <?php if ($sub_error): ?>
        <p class="form-note error"><?= e($sub_error) ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>&copy; <?= e(setting('site_title')) ?> <span><?= date('Y') ?></span></div>
    <div><?= e(setting('footer_note')) ?></div>
  </div>
</footer>
</body>
</html>
