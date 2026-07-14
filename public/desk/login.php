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
<link rel="stylesheet" href="/assets/site.css">
</head>
<body class="desk-body">
<div class="login-wrap">
  <a class="brand" href="/" style="font-size:1.8rem"><?= e(setting('brand_main')) ?> <span><?= e(setting('brand_accent')) ?></span></a>
  <div class="desk-card">
    <?php if ($first_run): ?>
      <h2>Welcome to your writing desk</h2>
      <p>Before your first sign-in, choose the password you will use to manage the site.</p>
    <?php else: ?>
      <h2>Sign in</h2>
    <?php endif; ?>
    <?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" style="margin-top:1.4rem">
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
      <button class="btn" type="submit"><?= $first_run ? 'Create password & enter' : 'Enter the desk' ?></button>
    </form>
  </div>
</div>
</body>
</html>
