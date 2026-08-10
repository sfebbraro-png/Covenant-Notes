<?php
require_once __DIR__ . '/lib/bootstrap.php';

$posts = db()->query("SELECT * FROM posts WHERE status = 'published'
                      ORDER BY published_at DESC, id DESC")->fetchAll();
$current = null;
foreach ($posts as $candidate) {
    if ($candidate['category'] === 'Devotional') { $current = $candidate; break; }
}
if (!$current && isset($posts[0])) $current = $posts[0];
$archives = $posts;
$devotional_count = 0;
$essay_count = 0;
foreach ($archives as $entry) {
    if (strtolower($entry['category']) === 'essay') $essay_count++; else $devotional_count++;
}
$archive_counts = array();
if ($devotional_count) $archive_counts[] = $devotional_count . ' ' . ($devotional_count === 1 ? 'devotional' : 'devotionals');
if ($essay_count) $archive_counts[] = $essay_count . ' ' . ($essay_count === 1 ? 'essay' : 'essays');
$archive_summary = $archive_counts ? implode(' · ', $archive_counts) . ' in the archive' : 'The archive is filling up.';
$current_url = $current ? site_url('/post.php?post=' . rawurlencode($current['slug'])) : '';
$current_facebook_url = $current_url !== '' ? 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($current_url) : '';

$subscribed = isset($_GET['subscribed']);
$sub_error = isset($_GET['sub_error']) ? $_GET['sub_error'] : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php $home_seo_title = trim(setting('home_seo_title')); ?>
<title><?= $home_seo_title !== '' ? e($home_seo_title) : e(setting('site_title')) . ' | Essays &amp; Devotionals' ?></title>
<meta name="description" content="<?= e(setting('meta_description')) ?>">
<link rel="canonical" href="<?= e(site_url('/')) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(setting('site_title')) ?>">
<meta property="og:title" content="<?= e(setting('site_title')) ?>">
<meta property="og:description" content="<?= e(setting('meta_description')) ?>">
<meta property="og:url" content="<?= e(site_url('/')) ?>">
<meta property="og:image" content="<?= e(site_url(asset_path('/assets/og.png'))) ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1730">
<meta property="og:image:height" content="909">
<meta property="og:image:alt" content="<?= e(setting('site_title')) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e(setting('site_title')) ?>">
<meta name="twitter:description" content="<?= e(setting('meta_description')) ?>">
<meta name="twitter:image" content="<?= e(site_url(asset_path('/assets/og.png'))) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= e(setting('site_title')) ?>" href="/feed.php">
<link rel="stylesheet" href="<?= e(asset_path('/assets/site.css')) ?>">
</head>
<body class="approved-design">
<main id="top">
  <header class="approved-header">
    <nav class="approved-nav shell" aria-label="Main navigation">
      <a class="approved-brand" href="/" aria-label="<?= e(setting('site_title')) ?> home">
        <span class="approved-brand-mark" aria-hidden="true">C</span>
        <span>
          <strong><?= e(setting('site_title')) ?></strong>
          <small>Devotions &amp; essays by <?= e(setting('author_name', 'Steve Febbraro')) ?></small>
        </span>
      </a>
      <div class="approved-nav-links">
        <a href="#devotional">Devotions / Essays</a>
        <a href="#archive">Archive</a>
        <a href="#about">About</a>
        <a href="#newsletter">Newsletter</a>
      </div>
      <a class="approved-admin-link" href="/desk/" aria-label="Open the private writing desk"><span>Writing desk</span></a>
    </nav>
  </header>

  <section class="approved-hero">
    <div class="approved-hero-rule" aria-hidden="true"></div>
    <div class="shell approved-hero-inner">
      <p class="approved-eyebrow">Scripture &middot; Reflection &middot; Reformed Faith</p>
      <blockquote>&ldquo;The Bible is the school<br class="approved-desktop-break"> of the Holy Spirit.&rdquo;</blockquote>
      <p class="approved-quote-byline">&mdash; John Calvin</p>
      <a class="approved-hero-link" href="#devotional">Read today&rsquo;s devotional <span aria-hidden="true">&darr;</span></a>
    </div>
  </section>

  <?php if ($current): ?>
  <section class="approved-current shell" id="devotional">
    <aside class="approved-section-intro">
      <span class="approved-section-number">01</span>
      <p class="approved-eyebrow">Today&rsquo;s devotional</p>
      <p>A quiet moment in the Word for the middle of an ordinary day.</p>
      <a class="approved-home-link" href="#top">Back to homepage <span aria-hidden="true">&uarr;</span></a>
    </aside>
    <article class="approved-devotional-card">
      <div class="approved-devotional-meta">
        <time datetime="<?= e($current['published_at']) ?>"><?= e(format_date($current['published_at'])) ?></time>
        <span><?= reading_time_minutes($current['body']) ?> min read</span>
      </div>
      <p class="approved-scripture-ref"><?= e($current['scripture'] !== '' ? $current['scripture'] : $current['category']) ?></p>
      <h1><?= e($current['title']) ?></h1>
      <p class="approved-devotional-lede"><?= e($current['excerpt']) ?></p>
      <div class="approved-devotional-body"><?= render_body($current['body']) ?></div>
      <div class="approved-devotional-share">
        <a class="approved-text-link" href="<?= e($current_url) ?>">Open shareable page <span aria-hidden="true">&rarr;</span></a>
        <a class="approved-text-link" href="<?= e($current_facebook_url) ?>" target="_blank" rel="noopener">Share on Facebook <span aria-hidden="true">&#8599;</span></a>
      </div>
    </article>
  </section>
  <?php endif; ?>

  <section class="approved-archive" id="archive">
    <div class="shell">
      <div class="approved-section-heading">
        <div>
          <span class="approved-section-number">02</span>
          <p class="approved-eyebrow">From the archive</p>
          <a class="approved-home-link" href="#top">Back to homepage <span aria-hidden="true">&uarr;</span></a>
        </div>
        <div class="approved-archive-heading-copy">
          <h2>Return to the Word,<br>again and again.</h2>
          <p class="approved-archive-count"><?= e($archive_summary) ?></p>
        </div>
      </div>
      <?php if ($archives): ?>
      <div class="approved-archive-list">
        <?php foreach ($archives as $index => $post): ?>
        <article class="approved-archive-row">
          <div class="approved-archive-index" aria-hidden="true"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></div>
          <div class="approved-archive-copy">
            <p class="approved-scripture-ref"><?= e($post['scripture'] !== '' ? $post['scripture'] : $post['category']) ?></p>
            <h3><a href="/post.php?post=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
            <p><?= e($post['excerpt']) ?></p>
          </div>
          <div class="approved-archive-meta">
            <time datetime="<?= e($post['published_at']) ?>"><?= e(format_date($post['published_at'])) ?></time>
            <span><?= reading_time_minutes($post['body']) ?> min read</span>
          </div>
          <span class="approved-archive-arrow" aria-hidden="true">&rarr;</span>
        </article>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="approved-empty">More devotionals and essays will appear here as they are published.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="approved-about shell" id="about">
    <div class="approved-about-portrait" aria-hidden="true"><span>C</span><small>Coram Deo</small></div>
    <div class="approved-about-copy">
      <span class="approved-section-number">03</span>
      <p class="approved-eyebrow"><?= e(setting('about_eyebrow', 'About the writer')) ?></p>
      <h2><?= e(setting('about_heading')) ?></h2>
      <?= render_body(setting('about_body')) ?>
      <p class="approved-signature"><?= e(setting('author_name', 'Steve')) ?></p>
      <a class="approved-home-link" href="#top">Back to homepage <span aria-hidden="true">&uarr;</span></a>
    </div>
  </section>

  <?php if (setting('substack_url') !== ''): ?>
  <section class="approved-substack">
    <div class="shell approved-substack-inner">
      <div>
        <p class="approved-eyebrow">Essays &amp; longer reads</p>
        <h2><?= e(setting('substack_heading', 'Essays, here or on Substack.')) ?></h2>
        <a class="approved-home-link approved-light" href="#top">Back to homepage <span aria-hidden="true">&uarr;</span></a>
      </div>
      <div class="approved-substack-copy">
        <p><?= e(setting('substack_text')) ?></p>
        <a class="approved-light-button" href="<?= e(setting('substack_url')) ?>" target="_blank" rel="noopener">Visit Steve&rsquo;s Substack <span aria-hidden="true">&#8599;</span></a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="approved-newsletter" id="newsletter">
    <div class="approved-newsletter-mark" aria-hidden="true">&#10022;</div>
    <div class="shell approved-newsletter-inner">
      <p class="approved-eyebrow">A devotional in your inbox</p>
      <h2><?= e(setting('newsletter_heading')) ?></h2>
      <p>Receive thoughtful encouragement rooted in Scripture.</p>
      <?php if ($subscribed): ?>
        <p class="approved-form-message approved-success"><strong>Thank you &mdash; you&rsquo;re on the list.</strong></p>
      <?php else: ?>
        <form class="approved-newsletter-form" method="post" action="/subscribe.php">
          <?= csrf_field() ?>
          <input type="email" name="email" required placeholder="Your email address" aria-label="Email address">
          <button type="submit">Join the newsletter</button>
        </form>
      <?php endif; ?>
      <?php if ($sub_error): ?><p class="approved-form-message approved-error"><?= e($sub_error) ?></p><?php endif; ?>
      <small>No noise. Just thoughtful encouragement rooted in Scripture. Unsubscribe anytime.</small>
      <a class="approved-home-link approved-centered" href="#top">Back to homepage <span aria-hidden="true">&uarr;</span></a>
    </div>
  </section>

  <footer class="approved-footer">
    <div class="shell approved-footer-inner">
      <div class="approved-brand approved-footer-brand">
        <span class="approved-brand-mark" aria-hidden="true">C</span>
        <span><strong><?= e(setting('site_title')) ?></strong><small>Devotions &amp; essays by <?= e(setting('author_name', 'Steve Febbraro')) ?></small></span>
      </div>
      <p>&copy; <?= date('Y') ?> <?= e(setting('author_name', 'Steve Febbraro')) ?>. Soli Deo Gloria.</p>
      <div><a href="#about">About</a><a href="<?= e(setting('substack_url')) ?>" target="_blank" rel="noopener">Substack</a></div>
    </div>
  </footer>
</main>
</body>
</html>
