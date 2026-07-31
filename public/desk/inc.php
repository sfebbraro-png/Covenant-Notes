<?php
require_once __DIR__ . '/../lib/bootstrap.php';

function desk_header($active, $title) {
    $tabs = array(
        'index'       => array('index.php', 'Writing', '&#9998;'),
        'sections'    => array('sections.php', 'Site sections', '&#9635;'),
    );
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> | Writing Desk</title>
<link rel="stylesheet" href="<?= e(asset_path('/assets/site.css')) ?>">
</head>
<body class="local-desk-body">
<div class="local-settings-shell">
  <aside class="local-admin-sidebar">
    <a class="local-admin-brand" href="/">
      <span class="local-admin-mark" aria-hidden="true">C</span>
      <span><strong><?= e(setting('site_title')) ?></strong><small>Writing desk</small></span>
    </a>
    <nav aria-label="Writing desk navigation">
    <?php foreach ($tabs as $key => $tab): ?>
      <a href="<?= e($tab[0]) ?>" class="<?= $key === $active ? 'active' : '' ?>"><b aria-hidden="true"><?= $tab[2] ?></b><span><?= e($tab[1]) ?></span></a>
    <?php endforeach; ?>
    </nav>
    <div class="local-admin-bottom"><a href="/" target="_blank">View live site &#8599;</a><a href="logout.php">Sign out</a></div>
  </aside>
  <main class="local-settings-panel">
    <header class="local-settings-heading"><div><p class="local-eyebrow">Make it your own</p><h1><?= e($title) ?></h1></div><a class="local-square-button" href="index.php" aria-label="Writing">&#9998;</a></header>
    <?php
}

function desk_footer() {
    echo '</main></div></body></html>';
}

function flash($msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return; }
    if (!empty($_SESSION['flash'])) {
        echo '<div class="notice">' . e($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}
