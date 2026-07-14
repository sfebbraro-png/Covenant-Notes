<?php
require_once __DIR__ . '/inc.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_check();
    $stmt = db()->prepare('SELECT title FROM posts WHERE id = ?');
    $stmt->execute(array((int)$_POST['delete_id']));
    $title = $stmt->fetchColumn();
    if ($title !== false) {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute(array((int)$_POST['delete_id']));
        flash('"' . $title . '" was deleted.');
    }
    redirect('index.php');
}

$posts = db()->query("SELECT * FROM posts ORDER BY status = 'draft' DESC, published_at DESC, id DESC")->fetchAll();
$total_views = 0;
$published = 0;
foreach ($posts as $p) {
    $total_views += (int)$p['views'];
    if ($p['status'] === 'published') $published++;
}
$subscribers = (int)db()->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();

desk_header('index', 'Posts');
flash();
?>
<div class="stat-row">
  <div class="stat"><b><?= $published ?></b><span>Published pieces</span></div>
  <div class="stat"><b><?= $total_views ?></b><span>Total post views</span></div>
  <div class="stat"><b><?= $subscribers ?></b><span>Subscribers</span></div>
</div>

<div class="desk-card">
  <div class="btn-row" style="margin:0 0 1.4rem">
    <a class="btn" href="edit.php">Write a new post</a>
  </div>
  <?php if (!$posts): ?>
    <p class="empty">Nothing here yet. Write your first post.</p>
  <?php else: ?>
  <table class="desk-table">
    <tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th>Views</th><th>Actions</th></tr>
    <?php foreach ($posts as $p): ?>
    <tr>
      <td><a href="edit.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></td>
      <td><span class="post-meta"><?= e($p['category']) ?></span></td>
      <td><span class="badge <?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
      <td><small><?= e(format_date($p['published_at'])) ?></small></td>
      <td><?= (int)$p['views'] ?></td>
      <td class="row-actions">
        <a href="edit.php?id=<?= (int)$p['id'] ?>">edit</a>
        <a href="/post.php?post=<?= e($p['slug']) ?>">view</a>
        <form method="post" onsubmit="return confirm('Delete &quot;<?= e($p['title']) ?>&quot; permanently?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>">
          <button type="submit">delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php desk_footer(); ?>
