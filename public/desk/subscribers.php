<?php
require_once __DIR__ . '/inc.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_check();
    db()->prepare('DELETE FROM subscribers WHERE id = ?')->execute(array((int)$_POST['delete_id']));
    flash('Subscriber removed.');
    redirect('subscribers.php');
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('email', 'subscribed_on'));
    foreach (db()->query('SELECT email, created_at FROM subscribers ORDER BY created_at') as $row) {
        fputcsv($out, array($row['email'], $row['created_at']));
    }
    fclose($out);
    exit;
}

$subs = db()->query('SELECT * FROM subscribers ORDER BY created_at DESC')->fetchAll();
$has_key = setting('newsletter_api_key') !== '';

desk_header('subscribers', 'Subscribers');
flash();
?>
<div class="desk-card">
  <?php if (!$has_key): ?>
  <div class="notice" style="margin:0 0 1.4rem">Subscribers are being stored here on the site. Add your
    newsletter API key under <a href="settings.php">Settings</a> and new subscribers will also be sent
    to your email service automatically.</div>
  <?php endif; ?>
  <div class="btn-row" style="margin:0 0 1.4rem">
    <a class="btn ghost" href="subscribers.php?export=1">Download CSV</a>
    <span class="post-meta"><?= count($subs) ?> subscriber<?= count($subs) === 1 ? '' : 's' ?></span>
  </div>
  <?php if (!$subs): ?>
    <p class="empty">No subscribers yet. The signup form is in the footer of every page.</p>
  <?php else: ?>
  <table class="desk-table">
    <tr><th>Email</th><th>Subscribed</th><th>Sent to email service</th><th></th></tr>
    <?php foreach ($subs as $s): ?>
    <tr>
      <td style="font-family:var(--sans);font-size:.95rem"><?= e($s['email']) ?></td>
      <td><small><?= e(format_date(substr($s['created_at'], 0, 10))) ?></small></td>
      <td><?= $s['synced'] ? 'Yes' : 'Stored locally' ?></td>
      <td>
        <form method="post" onsubmit="return confirm('Remove this subscriber?')" style="margin:0">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_id" value="<?= (int)$s['id'] ?>">
          <button class="btn danger" style="padding:.3rem .7rem;font-size:.75rem" type="submit">Remove</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php desk_footer(); ?>
