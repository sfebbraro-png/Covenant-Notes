<?php
require_once __DIR__ . '/inc.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['newsletter_api_key'])) {
        save_setting('newsletter_api_key', trim($_POST['newsletter_api_key']));
        flash('Newsletter settings saved.');
        redirect('settings.php');
    }

    if (isset($_POST['new_password'])) {
        $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new     = $_POST['new_password'];
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        if (!password_verify($current, setting('admin_password_hash'))) {
            $error = 'Your current password is not correct.';
        } elseif (strlen($new) < 10) {
            $error = 'The new password must be at least 10 characters.';
        } elseif ($new !== $confirm) {
            $error = 'The new passwords do not match.';
        } else {
            set_admin_password($new);
            flash('Password changed.');
            redirect('settings.php');
        }
    }
}

desk_header('settings', 'Settings');
flash();
if ($error) echo '<div class="notice error">' . e($error) . '</div>';
?>
<div class="desk-card">
  <h2>Newsletter service</h2>
  <p>New subscribers are always stored on this site. Add your Buttondown API key and each new
     subscriber will also be created in your Buttondown account automatically.</p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field">
      <label for="newsletter_api_key">API key</label>
      <input type="text" id="newsletter_api_key" name="newsletter_api_key"
             value="<?= e(setting('newsletter_api_key')) ?>" autocomplete="off">
      <div class="hint">Found in your newsletter account under Settings &rarr; API. Leave blank to
        only store subscribers here.</div>
    </div>
    <button class="btn" type="submit">Save</button>
  </form>
</div>

<div class="desk-card">
  <h2>Change password</h2>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" required>
    </div>
    <div class="field-row">
      <div class="field">
        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" required>
      </div>
      <div class="field">
        <label for="confirm_password">Repeat new password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>
      <div></div>
    </div>
    <button class="btn" type="submit">Change password</button>
  </form>
</div>
<?php desk_footer(); ?>
