<?php
require_once __DIR__ . '/../lib/bootstrap.php';

function desk_header($active, $title) {
    $tabs = array(
        'index'       => array('index.php', 'Posts'),
        'edit'        => array('edit.php', 'New post'),
        'sections'    => array('sections.php', 'Site sections'),
        'subscribers' => array('subscribers.php', 'Subscribers'),
        'settings'    => array('settings.php', 'Settings'),
    );
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> | Writing Desk</title>
<link rel="stylesheet" href="/assets/site.css">
</head>
<body class="desk-body">
<header class="site-header">
  <div class="wrap nav">
    <a class="brand" href="/"><?= e(setting('brand_main')) ?> <span><?= e(setting('brand_accent')) ?></span></a>
    <nav class="nav-links">
      <a href="/">View site</a>
      <a class="desk" href="logout.php">Sign out</a>
    </nav>
  </div>
</header>
<main class="desk-main">
  <div class="eyebrow">The writing desk</div>
  <h1 class="desk-title"><?= e($title) ?></h1>
  <nav class="desk-nav">
    <?php foreach ($tabs as $key => $tab): ?>
      <a href="<?= e($tab[0]) ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= e($tab[1]) ?></a>
    <?php endforeach; ?>
  </nav>
    <?php
}

function desk_footer() {
    echo '</main></body></html>';
}

function flash($msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return; }
    if (!empty($_SESSION['flash'])) {
        echo '<div class="notice">' . e($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}
