<?php
require_once __DIR__ . '/../lib/bootstrap.php';
$_SESSION = array();
session_destroy();
redirect('/');
