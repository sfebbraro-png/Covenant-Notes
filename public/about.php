<?php
require_once __DIR__ . '/lib/bootstrap.php';

$author_name = setting('author_name', 'Steve Febbraro');
$about_url = site_url('/about');
$about_description = 'About ' . $author_name . ', a Reformed Christian writer publishing essays and devotionals on Scripture, church life, and Christian faith.';
$substack_url = setting('substack_url');
?>
<!doctype html>
<html lang="en">
<head>
<?php require __DIR__ . '/lib/google-analytics.php'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>About <?= e($author_name) ?> | <?= e(setting('site_title')) ?></title>
<meta name="description" content="<?= e($about_description) ?>">
<link rel="canonical" href="<?= e($about_url) ?>">
<meta property="og:type" content="profile">
<meta property="og:site_name" content="<?= e(setting('site_title')) ?>">
<meta property="og:title" content="About <?= e($author_name) ?>">
<meta property="og:description" content="<?= e($about_description) ?>">
<meta property="og:url" content="<?= e($about_url) ?>">
<meta property="og:image" content="<?= e(site_url(asset_path('/assets/og.png'))) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="About <?= e($author_name) ?>">
<meta name="twitter:description" content="<?= e($about_description) ?>">
<meta name="twitter:image" content="<?= e(site_url(asset_path('/assets/og.png'))) ?>">
<script type="application/ld+json"><?= json_encode(array(
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    '@id' => $about_url . '#person',
    'name' => $author_name,
    'url' => $about_url,
    'description' => $about_description,
    'sameAs' => array_values(array_filter(array($substack_url))),
), JSON_UNESCAPED_SLASHES) ?></script>
<link rel="stylesheet" href="<?= e(asset_path('/assets/site.css')) ?>">
</head>
<body class="approved-design approved-reading-page">
<header class="approved-header approved-reading-header">
  <nav class="approved-nav shell" aria-label="Main navigation">
    <a class="approved-brand" href="/" aria-label="<?= e(setting('site_title')) ?> home">
      <span class="approved-brand-mark" aria-hidden="true">C</span>
      <span><strong><?= e(setting('site_title')) ?></strong><small>Devotions &amp; essays by <?= e($author_name) ?></small></span>
    </a>
    <div class="approved-nav-links">
      <a href="/#devotional">Devotions / Essays</a><a href="/#archive">Archive</a><a href="/about" aria-current="page">About</a><a href="/#newsletter">Newsletter</a>
    </div>
    <a class="approved-admin-link" href="/desk/" aria-label="Open the private writing desk"><span>Writing desk</span></a>
  </nav>
</header>

<main>
  <article class="approved-author-page shell-narrow">
    <p class="approved-eyebrow">About the writer</p>
    <h1><?= e($author_name) ?></h1>
    <p class="approved-author-lede">Reformed Christian essays and devotionals for readers who want to bring ordinary life under the searching light of Scripture.</p>
    <figure style="margin:36px 0 6px">
      <img src="<?= e(asset_path('/assets/steve-febbraro.jpg')) ?>" alt="Portrait of <?= e($author_name) ?>" width="600" height="600" style="display:block;width:min(260px,72vw);height:auto;border-radius:18px;box-shadow:0 18px 45px rgba(16,37,64,.16)">
    </figure>
    <div class="approved-reading-rule"><span>&#10022;</span></div>
    <div class="approved-author-body"><?= render_body(setting('about_body')) ?></div>
    <?php if ($substack_url !== ''): ?>
    <p><a class="approved-text-link" href="<?= e($substack_url) ?>" target="_blank" rel="noopener">Read Steve&rsquo;s Substack <span aria-hidden="true">&#8599;</span></a></p>
    <?php endif; ?>
    <p><a class="approved-text-link" href="/">&larr; Back to the homepage</a></p>
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
</body>
</html>
