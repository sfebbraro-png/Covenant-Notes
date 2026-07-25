<?php
require_once __DIR__ . '/inc.php';
require_login();
$target = 'index.php';
if (isset($_GET['id']) && (int)$_GET['id'] > 0) $target .= '?id=' . (int)$_GET['id'];
redirect($target);
