<?php
require_once __DIR__ . '/inc.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$post = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute(array($id));
    $post = $stmt->fetch();
    if (!$post) { $id = 0; }
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete']) && $post) {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute(array($post['id']));
        flash('"' . $post['title'] . '" was deleted.');
        redirect('index.php');
    }

    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $category = trim(isset($_POST['category']) ? $_POST['category'] : '');
    $allowed = categories();
    if (!in_array($category, $allowed, true)) {
        // Keep whatever the post already had rather than silently recategorising it.
        $category = $post ? $post['category'] : 'Devotional';
    }
    $scripture = trim(isset($_POST['scripture']) ? $_POST['scripture'] : '');
    $seo_title = trim(isset($_POST['seo_title']) ? $_POST['seo_title'] : '');
    $date = trim(isset($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d'));
    $excerpt = trim(isset($_POST['excerpt']) ? $_POST['excerpt'] : '');
    $body = trim(isset($_POST['body']) ? $_POST['body'] : '');
    if (isset($_POST['publish'])) {
        $status = 'published';
    } elseif (isset($_POST['save_draft'])) {
        $status = 'draft';
    } else {
        $status = $post && $post['status'] === 'published' ? 'published' : 'draft';
    }

    if ($title === '') {
        $error = 'A title is required.';
    } else {
        $slug = $post ? $post['slug'] : slugify($title);
        if (!$post) {
            $base = $slug === '' ? strtolower($category) : $slug;
            $slug = $base;
            $n = 1;
            while (true) {
                $stmt = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ?');
                $stmt->execute(array($slug));
                if (!$stmt->fetchColumn()) break;
                $slug = $base . '-' . (++$n);
            }
        }
        if ($post) {
            db()->prepare("UPDATE posts SET title=?, category=?, scripture=?, seo_title=?, excerpt=?, body=?, status=?, published_at=?, updated_at=datetime('now') WHERE id=?")
                ->execute(array($title, $category, $scripture, $seo_title, $excerpt, $body, $status, $date, $post['id']));
            $id = (int)$post['id'];
        } else {
            db()->prepare("INSERT INTO posts (slug,title,category,scripture,seo_title,excerpt,body,status,published_at) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute(array($slug, $title, $category, $scripture, $seo_title, $excerpt, $body, $status, $date));
            $id = (int)db()->lastInsertId();
        }
        $noun = strtolower($category) === 'essay' ? 'essay' : 'devotional';
        flash($status === 'published' ? 'Saved. This ' . $noun . ' is live.' : 'Saved as a draft.');
        redirect('index.php?id=' . $id);
    }
}

$posts = db()->query("SELECT * FROM posts ORDER BY status = 'draft' DESC, published_at DESC, id DESC")->fetchAll();
$active = $post ?: array(
    'id' => 0, 'title' => '', 'category' => 'Devotional', 'scripture' => '', 'seo_title' => '', 'excerpt' => '', 'body' => '',
    'status' => 'draft', 'published_at' => date('Y-m-d'), 'slug' => ''
);
$active_noun = strtolower($active['category']) === 'essay' ? 'essay' : 'devotional';
$flash_message = '';
if (!empty($_SESSION['flash'])) { $flash_message = $_SESSION['flash']; unset($_SESSION['flash']); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Writing | Writing Desk</title>
<link rel="stylesheet" href="<?= e(asset_path('/assets/site.css')) ?>">
</head>
<body class="local-desk-body">
<main class="local-admin-shell">
  <aside class="local-admin-sidebar">
    <a class="local-admin-brand" href="/">
      <span class="local-admin-mark" aria-hidden="true">C</span>
      <span><strong><?= e(setting('site_title')) ?></strong><small>Writing desk</small></span>
    </a>
    <nav>
      <a class="active" href="index.php"><b aria-hidden="true">&#9998;</b><span>Writing</span></a>
      <a href="sections.php"><b aria-hidden="true">&#9635;</b><span>Site sections</span></a>
    </nav>
    <div class="local-admin-bottom"><a href="/" target="_blank">View live site &#8599;</a><a href="logout.php">Sign out</a></div>
  </aside>

  <section class="local-post-library">
    <header class="local-library-heading">
      <div><p class="local-eyebrow">Your library</p><h1>Writing</h1></div>
      <a class="local-square-button" href="index.php" aria-label="Start something new">+</a>
    </header>
    <div class="local-post-list">
      <?php foreach ($posts as $item): ?>
      <a href="index.php?id=<?= (int)$item['id'] ?>" class="<?= (int)$active['id'] === (int)$item['id'] ? 'active' : '' ?>">
        <span class="local-status-dot <?= e($item['status']) ?>"></span>
        <span><strong><?= e($item['title']) ?></strong><small><?= e($item['category']) ?><?php if ($item['scripture'] !== ''): ?> &middot; <?= e($item['scripture']) ?><?php endif; ?> &middot; <?= e($item['published_at']) ?></small></span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="local-editor-panel">
    <form method="post" id="devotional-form">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$active['id'] ?>">
      <header class="local-editor-topbar">
        <div><span class="local-status-badge <?= e($active['status']) ?>"><?= e($active['status']) ?></span><?php if ($flash_message): ?><span class="local-save-notice"><?= e($flash_message) ?></span><?php endif; ?><?php if ($error): ?><span class="local-save-notice error"><?= e($error) ?></span><?php endif; ?></div>
        <div>
          <?php if ($active['id']): ?><button class="local-delete-button" type="submit" name="delete" value="1" onclick="return confirm('Delete this <?= e($active_noun) ?> permanently?')">Delete</button><?php endif; ?>
          <button class="local-draft-button" type="submit" name="save_draft" value="1"><?= $active['status'] === 'published' ? 'Move to draft' : 'Save draft' ?></button>
          <button class="local-save-button" type="submit" name="publish" value="1"><?= $active['status'] === 'published' ? 'Update published ' . e($active_noun) : 'Publish ' . e($active_noun) ?></button>
        </div>
      </header>
      <div class="local-editor-fields">
        <label class="local-title-field">Title<input name="title" required value="<?= e($active['title']) ?>" placeholder="Give this piece a title"></label>
        <div class="local-field-row">
          <label>Type<select name="category">
            <?php foreach (categories() as $name): ?>
            <option value="<?= e($name) ?>" <?= $name === $active['category'] ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
          </select></label>
          <label>Scripture<input name="scripture" value="<?= e($active['scripture']) ?>" placeholder="Romans 8:1"></label>
          <label>Publish date<input name="published_at" type="date" value="<?= e($active['published_at']) ?>"></label>
          <label>Read time<span class="local-read-time"><b id="read-time"><?= reading_time_minutes($active['body']) ?></b> min <small>(<span id="word-count"><?= reading_word_count($active['body']) ?></span> words)</small></span></label>
        </div>
        <label>Short introduction<textarea name="excerpt" rows="3" placeholder="A one-sentence introduction for archive cards."><?= e($active['excerpt']) ?></textarea></label>
        <label>Search engine title <span class="local-field-note">Optional &middot; not shown on the site</span>
          <input name="seo_title" maxlength="70" value="<?= e($active['seo_title']) ?>" placeholder="<?= e($active['title'] !== '' ? $active['title'] : 'Defaults to the title above') ?>">
        </label>
        <p class="local-field-hint">This is the headline Google shows in search results and the text in the browser tab. Readers never see it on the page. Leave it blank to use the title above. Aim for about 60 characters so it is not cut off.</p>
        <div class="local-editor-label"><?= e($active['category']) ?></div>
        <div class="local-rich-editor">
          <div class="local-format-toolbar" role="toolbar" aria-label="Text formatting">
            <button type="button" data-wrap="**" title="Bold"><strong>B</strong></button>
            <button type="button" data-wrap="*" title="Italic"><em>I</em></button>
            <button type="button" data-wrap="++" title="Underline"><u>U</u></button><span></span>
            <button type="button" data-block="## " title="Heading">&#182;</button>
            <button type="button" data-block="&gt; " title="Block quote">&#10077;</button>
            <button type="button" data-block="- " title="Bulleted list">&bull; List</button>
            <button type="button" data-block="1. " title="Numbered list">1. List</button><span></span>
            <button type="button" data-link="1" title="Link">Link</button>
          </div>
          <textarea id="body" name="body" placeholder="Begin writing…"><?= e($active['body']) ?></textarea>
        </div>
      </div>
    </form>
  </section>
</main>
<script>
(function(){
  var ta=document.getElementById('body'),bar=document.querySelector('.local-format-toolbar');
  if(!ta||!bar)return;
  function estimate(){var plain=ta.value.replace(/\[([^\]]+)\]\([^)]+\)/g,'$1').replace(/(^|\n)\s*(?:-\s+|\d+\.\s+)/g,'$1').replace(/[*_+`#>]+/g,' ').replace(/<[^>]*>/g,' ').trim();var words=plain?plain.split(/\s+/).filter(Boolean).length:0;document.getElementById('word-count').textContent=words;document.getElementById('read-time').textContent=words?Math.max(1,Math.ceil(words/200)):0;}
  function select(s,e){ta.focus();ta.selectionStart=s;ta.selectionEnd=e;}
  ta.addEventListener('input',estimate);
  bar.addEventListener('click',function(ev){var btn=ev.target.closest('button');if(!btn)return;var s=ta.selectionStart,e=ta.selectionEnd,v=ta.value;if(btn.dataset.wrap){var m=btn.dataset.wrap,chosen=v.slice(s,e)||'text';ta.value=v.slice(0,s)+m+chosen+m+v.slice(e);select(s+m.length,s+m.length+chosen.length);}else if(btn.dataset.block){var start=v.lastIndexOf('\n',s-1)+1,p=btn.dataset.block;ta.value=v.slice(0,start)+p+v.slice(start);select(s+p.length,e+p.length);}else if(btn.dataset.link){var chosen2=v.slice(s,e)||'link text',url=window.prompt('Address to link to (https://…)','https://');if(!url)return;var md='['+chosen2+']('+url+')';ta.value=v.slice(0,s)+md+v.slice(e);select(s+1,s+1+chosen2.length);}estimate();});estimate();
})();
</script>
</body>
</html>
