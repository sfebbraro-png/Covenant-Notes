<?php
require_once __DIR__ . '/inc.php';
require_login();

$fields = array(
    'Brand & title' => array(
        'site_title'   => array('text', 'Site title', 'Used in the browser tab, footer, and feed.'),
        'brand_main'   => array('text', 'Brand — main part', 'The dark part of the logo text.'),
        'brand_accent' => array('text', 'Brand — accent part', 'The terracotta part of the logo text.'),
        'brand_tagline' => array('text', 'Tagline under the brand', 'Shown in italics beneath the site name. Leave blank to hide.'),
        'meta_description' => array('text', 'Search description', 'Shown by search engines under your site name.'),
        'site_url'     => array('text', 'Site address', 'The permanent https address search engines should index. Only change if the domain changes.'),
        'author_name'  => array('text', 'Author name', 'Used in the machine-readable article info search engines read.'),
    ),
    'Hero (top of the front page)' => array(
        'hero_eyebrow' => array('text', 'Small eyebrow line', ''),
        'hero_title'   => array('textarea-small', 'Big headline', 'Use a line break where the headline should wrap.'),
        'hero_intro'   => array('textarea-small', 'Introduction paragraph', ''),
        'hero_quote'   => array('textarea-small', 'Side quote', 'Shown beside the headline with quotation marks added.'),
    ),
    'About section' => array(
        'about_eyebrow' => array('text', 'Eyebrow line', ''),
        'about_heading' => array('text', 'Heading', ''),
        'about_body'    => array('textarea', 'Body', 'Separate paragraphs with a blank line.'),
    ),
    'Substack (essays & longer writing)' => array(
        'substack_url'     => array('text', 'Substack URL', 'Leave blank to hide every Substack link on the site.'),
        'substack_heading' => array('text', 'Section heading', ''),
        'substack_text'    => array('textarea-small', 'Section text', ''),
    ),
    'Newsletter & footer' => array(
        'newsletter_heading' => array('text', 'Newsletter heading', ''),
        'footer_note'        => array('text', 'Footer note', ''),
    ),
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $group) {
        foreach ($group as $key => $def) {
            if (isset($_POST[$key])) {
                save_setting($key, trim(str_replace("\r\n", "\n", $_POST[$key])));
            }
        }
    }
    flash('Sections saved.');
    redirect('sections.php');
}

desk_header('sections', 'Site sections');
flash();
?>
<form method="post">
<?= csrf_field() ?>
<?php foreach ($fields as $heading => $group): ?>
<div class="desk-card">
  <h2><?= e($heading) ?></h2>
  <?php foreach ($group as $key => $def): list($type, $label, $hint) = $def; ?>
  <div class="field">
    <label for="<?= e($key) ?>"><?= e($label) ?></label>
    <?php if ($type === 'text'): ?>
      <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key)) ?>">
    <?php elseif ($type === 'textarea-small'): ?>
      <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" style="min-height:90px"><?= e(setting($key)) ?></textarea>
    <?php else: ?>
      <textarea id="<?= e($key) ?>" name="<?= e($key) ?>"><?= e(setting($key)) ?></textarea>
    <?php endif; ?>
    <?php if ($hint): ?><div class="hint"><?= e($hint) ?></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
<div class="btn-row">
  <button class="btn" type="submit">Save all sections</button>
  <a class="btn ghost" href="/">View the site</a>
</div>
</form>
<?php desk_footer(); ?>
