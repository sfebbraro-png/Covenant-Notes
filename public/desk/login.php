<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if (is_logged_in()) redirect('index.php');

$first_run = !password_is_set();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($first_run) {
        $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
        if (strlen($password) < 10) {
            $error = 'Choose a password of at least 10 characters.';
        } elseif ($password !== $confirm) {
            $error = 'The two passwords do not match.';
        } else {
            set_admin_password($password);
            $_SESSION['writer'] = true;
            session_regenerate_id(true);
            redirect('index.php');
        }
    } else {
        if (attempt_login($password)) {
            redirect('index.php');
        }
        $error = 'That password is not correct.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Writing Desk | <?= e(setting('site_title')) ?></title>
<link rel="stylesheet" href="<?= e(asset_path('/assets/site.css')) ?>">
</head>
<body class="desk-body approved-desk-login">
<main class="approved-login-page">
  <section class="approved-login-panel">
    <a class="approved-brand" href="/"><span class="approved-brand-mark" aria-hidden="true">C</span><span><strong><?= e(setting('site_title')) ?></strong><small>Private writing desk</small></span></a>
    <div class="approved-login-copy">
      <p class="approved-eyebrow">Authorized access only</p>
    <?php if ($first_run): ?>
      <h1>Welcome to your<br>writing desk.</h1>
      <p>Before your first sign-in, choose the password you will use to manage the site.</p>
    <?php else: ?>
      <h1>Welcome back,<br>Steve.</h1>
      <p>Enter your password to write devotionals or update the site.</p>
    <?php endif; ?>
    </div>
    <?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="approved-login-form">
      <?= csrf_field() ?>
      <div class="field">
        <label for="password"><?= $first_run ? 'Choose a password' : 'Password' ?></label>
        <input type="password" id="password" name="password" required autofocus>
      </div>
      <?php if ($first_run): ?>
      <div class="field">
        <label for="confirm">Repeat the password</label>
        <input type="password" id="confirm" name="confirm" required>
      </div>
      <?php endif; ?>
      <button type="submit"><?= $first_run ? 'Create password & enter' : 'Enter writing desk →' ?></button>
    </form>
    <a class="approved-login-back" href="/">&larr; Back to the blog</a>
  </section>
  <section class="approved-login-verse"><blockquote>&ldquo;Your word is a lamp to my feet and a light to my path.&rdquo;</blockquote><p>Psalm 119:105</p></section>
</main>
</body>
</html>
