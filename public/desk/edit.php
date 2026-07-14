<?php
require_once __DIR__ . '/inc.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute(array($id));
    $post = $stmt->fetch();
    if (!$post) redirect('index.php');
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['delete']) && $post) {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute(array($post['id']));
        flash('"' . $post['title'] . '" was deleted.');
        redirect('index.php');
    }

    $title    = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $category = trim(isset($_POST['category']) ? $_POST['category'] : 'Essay');
    $new_cat  = trim(isset($_POST['new_category']) ? $_POST['new_category'] : '');
    $date     = trim(isset($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d'));
    $excerpt  = trim(isset($_POST['excerpt']) ? $_POST['excerpt'] : '');
    $body     = trim(isset($_POST['body']) ? $_POST['body'] : '');
    $status   = (isset($_POST['action']) && $_POST['action'] === 'publish') ? 'published' : 'draft';
    if ($post && isset($_POST['action']) && $_POST['action'] === 'save' && $post['status'] === 'published') {
        $status = 'published'; // saving an already-published post keeps it published
    }

    if ($new_cat !== '') {
        $category = $new_cat;
        try {
            db()->prepare('INSERT INTO categories (name) VALUES (?)')->execute(array($new_cat));
        } catch (PDOException $e) { /* already exists */ }
    }

    if ($title === '') {
        $error = 'A title is required.';
    } else {
        $slug = $post ? $post['slug'] : slugify($title);
        if (!$post) {
            $base = $slug === '' ? 'post' : $slug;
            $n = 1;
            while (true) {
                $stmt = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ?');
                $stmt->execute(array($slug));
                if (!$stmt->fetchColumn()) break;
                $slug = $base . '-' . (++$n);
            }
        }
        if ($post) {
            db()->prepare("UPDATE posts SET title=?, category=?, excerpt=?, body=?, status=?, published_at=?,
                           updated_at=datetime('now') WHERE id=?")
                ->execute(array($title, $category, $excerpt, $body, $status, $date, $post['id']));
            $id = $post['id'];
        } else {
            db()->prepare('INSERT INTO posts (slug, title, category, excerpt, body, status, published_at)
                           VALUES (?,?,?,?,?,?,?)')
                ->execute(array($slug, $title, $category, $excerpt, $body, $status, $date));
            $id = (int)db()->lastInsertId();
        }
        $was_published = $post && $post['status'] === 'published';
        flash($status === 'published' ? ($was_published ? 'Updated. The change is live.' : 'Published.') : 'Saved as draft.');
        redirect('edit.php?id=' . $id);
    }
}

$cats = categories();
$current_cat = $post ? $post['category'] : 'Essay';
if ($post && !in_array($post['category'], $cats)) $cats[] = $post['category'];

desk_header('edit', $post ? 'Edit post' : 'New post');
flash();
if ($error) echo '<div class="notice error">' . e($error) . '</div>';
?>
<div class="desk-card">
  <form method="post">
    <?= csrf_field() ?>
    <div class="field">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required value="<?= e($post ? $post['title'] : '') ?>">
    </div>
    <div class="field-row">
      <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach ($cats as $c): ?>
          <option value="<?= e($c) ?>" <?= $c === $current_cat ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="new_category">&hellip;or add a new category</label>
        <input type="text" id="new_category" name="new_category" placeholder="e.g. Meditation">
      </div>
      <div class="field">
        <label for="published_at">Date</label>
        <input type="date" id="published_at" name="published_at"
               value="<?= e($post ? $post['published_at'] : date('Y-m-d')) ?>">
      </div>
    </div>
    <div class="field">
      <label for="excerpt">Excerpt</label>
      <input type="text" id="excerpt" name="excerpt" value="<?= e($post ? $post['excerpt'] : '') ?>">
      <div class="hint">One sentence shown on the front page, in the archive, and in the feed.</div>
    </div>
    <div class="field">
      <label for="body">Body</label>
      <div class="editor-toolbar">
        <button type="button" data-wrap="**" title="Bold selected text"><strong>B</strong></button>
        <button type="button" data-wrap="*" title="Italicize selected text"><em>I</em></button>
        <button type="button" data-block="## " title="Turn the line into a heading">H</button>
        <button type="button" data-block="&gt; " title="Turn the line into a quote">&ldquo;&rdquo;</button>
        <button type="button" data-link="1" title="Turn selected text into a link">Link</button>
      </div>
      <textarea id="body" name="body"><?= e($post ? $post['body'] : '') ?></textarea>
      <div class="hint">Separate paragraphs with a blank line. You can also use ## Heading, &gt; blockquote,
        **bold**, *italic*, [link text](https://&hellip;), and --- for a rule.</div>
    </div>
    <div class="btn-row">
      <button class="btn" type="submit" name="action" value="publish"><?= $post && $post['status'] === 'published' ? 'Update' : 'Publish' ?></button>
      <button class="btn ghost" type="submit" name="action" value="save"><?= $post && $post['status'] === 'published' ? 'Save' : 'Save as draft' ?></button>
      <?php if ($post): ?>
        <a class="btn ghost" href="/post.php?post=<?= e($post['slug']) ?>">Preview</a>
        <button class="btn danger" type="submit" name="delete" value="1"
                onclick="return confirm('Delete this post permanently?')">Delete</button>
      <?php endif; ?>
    </div>
  </form>
</div>
<script>
(function () {
  var ta = document.getElementById('body');
  var bar = document.querySelector('.editor-toolbar');
  if (!ta || !bar) return;

  function setSelection(start, end) {
    ta.focus();
    ta.selectionStart = start;
    ta.selectionEnd = end;
  }

  bar.addEventListener('click', function (ev) {
    var btn = ev.target.closest('button');
    if (!btn) return;
    var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;

    if (btn.dataset.wrap) {
      var m = btn.dataset.wrap;
      var sel = v.slice(s, e) || 'text';
      // Toggle: if the selection (or the text around it) already has these
      // markers, remove them instead of stacking more asterisks.
      if (sel.length >= m.length * 2 && sel.slice(0, m.length) === m && sel.slice(-m.length) === m) {
        var inner = sel.slice(m.length, sel.length - m.length);
        ta.value = v.slice(0, s) + inner + v.slice(e);
        setSelection(s, s + inner.length);
      } else if (v.slice(s - m.length, s) === m && v.slice(e, e + m.length) === m) {
        ta.value = v.slice(0, s - m.length) + sel + v.slice(e + m.length);
        setSelection(s - m.length, s - m.length + sel.length);
      } else {
        ta.value = v.slice(0, s) + m + sel + m + v.slice(e);
        setSelection(s + m.length, s + m.length + sel.length);
      }
    } else if (btn.dataset.block) {
      var lineStart = v.lastIndexOf('\n', s - 1) + 1;
      var prefix = btn.dataset.block;
      if (v.slice(lineStart, lineStart + prefix.length) === prefix) {
        ta.value = v.slice(0, lineStart) + v.slice(lineStart + prefix.length);
        setSelection(Math.max(lineStart, s - prefix.length), Math.max(lineStart, e - prefix.length));
      } else {
        ta.value = v.slice(0, lineStart) + prefix + v.slice(lineStart);
        setSelection(s + prefix.length, e + prefix.length);
      }
    } else if (btn.dataset.link) {
      var sel2 = v.slice(s, e) || 'link text';
      var url = window.prompt('Address to link to (https://…)', 'https://');
      if (!url) return;
      var md = '[' + sel2 + '](' + url + ')';
      ta.value = v.slice(0, s) + md + v.slice(e);
      setSelection(s + 1, s + 1 + sel2.length);
    }
  });
})();
</script>
<?php desk_footer(); ?>
