<?php

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = array();
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return isset($cache[$key]) ? $cache[$key] : $default;
}

function save_setting($key, $value) {
    $stmt = db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $stmt->execute(array($key, $value));
}

function site_url($path = '') {
    $base = rtrim(setting('site_url', 'https://thecovenantblog.org'), '/');
    return $base . $path;
}

function asset_path($path) {
    static $versions = array();
    $path = '/' . ltrim((string)$path, '/');
    if (!isset($versions[$path])) {
        $file = dirname(__DIR__) . $path;
        $versions[$path] = is_file($file) ? substr(hash_file('sha256', $file), 0, 12) : '1';
    }
    return $path . '?v=' . $versions[$path];
}

/**
 * Canonical location of a post: /the-slug, routed by router.php.
 * The older /post.php?post=the-slug form still works and 301s here.
 */
function post_path($slug) {
    return '/' . rawurlencode((string)$slug);
}

function post_url($slug) {
    return site_url(post_path($slug));
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[\'"’‘”“]/u', '', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function format_date($ymd) {
    if (!$ymd) return '';
    $ts = strtotime($ymd);
    return $ts ? date('F j, Y', $ts) : $ymd;
}

/**
 * Estimate reading length from the post body at 200 words per minute.
 * Markdown formatting and link destinations are excluded from the count.
 */
function reading_word_count($text) {
    $plain = (string)$text;
    $plain = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $plain);
    $plain = preg_replace('/(^|\n)\s*(?:-\s+|\d+\.\s+)/u', '$1', $plain);
    $plain = preg_replace('/[*_+`#>]+/u', ' ', $plain);
    $plain = strip_tags($plain);
    $words = preg_split('/\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
}

function reading_time_minutes($text, $words_per_minute = 200) {
    $word_count = reading_word_count($text);
    return $word_count === 0 ? 0 : max(1, (int)ceil($word_count / $words_per_minute));
}

/**
 * Minimal markdown-style rendering for post bodies:
 * blank-line paragraphs, ## headings, > blockquotes, --- rules,
 * **bold**, *italic*, [text](url).
 */
function render_body($text) {
    $blocks = preg_split("/\n\s*\n/", trim(str_replace("\r\n", "\n", (string)$text)));
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        if ($block === '---') { $html .= "<hr>\n"; continue; }
        $lines = explode("\n", $block);
        $is_unordered = count($lines) > 0;
        $is_ordered = count($lines) > 0;
        foreach ($lines as $line) {
            if (!preg_match('/^-\s+.+/', $line)) $is_unordered = false;
            if (!preg_match('/^\d+\.\s+.+/', $line)) $is_ordered = false;
        }
        if ($is_unordered || $is_ordered) {
            $tag = $is_unordered ? 'ul' : 'ol';
            $html .= '<' . $tag . ">\n";
            foreach ($lines as $line) {
                $item = preg_replace($is_unordered ? '/^-\s+/' : '/^\d+\.\s+/', '', $line);
                $html .= '<li>' . inline_format($item) . "</li>\n";
            }
            $html .= '</' . $tag . ">\n";
            continue;
        }
        if (strpos($block, '## ') === 0) {
            $html .= '<h2>' . inline_format(substr($block, 3)) . "</h2>\n";
        } elseif (strpos($block, '> ') === 0) {
            $lines = array();
            foreach (explode("\n", $block) as $line) {
                $lines[] = preg_replace('/^>\s?/', '', $line);
            }
            $html .= '<blockquote><p>' . inline_format(implode(' ', $lines)) . "</p></blockquote>\n";
        } else {
            $html .= '<p>' . nl2br(inline_format($block)) . "</p>\n";
        }
    }
    return $html;
}

function render_body_preview($text, $paragraph_limit = 2) {
    $blocks = preg_split("/\n\s*\n/", trim(str_replace("\r\n", "\n", (string)$text)));
    $selected = array();
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        $selected[] = $block;
        if (count($selected) >= $paragraph_limit) break;
    }
    return render_body(implode("\n\n", $selected));
}

function inline_format($text) {
    $text = e($text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    $text = preg_replace('/\+\+(.+?)\+\+/s', '<u>$1</u>', $text);
    $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', function ($m) {
        return '<a href="' . $m[2] . '">' . $m[1] . '</a>';
    }, $text);
    return $text;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check() {
    if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(400);
        exit('Invalid request token. Go back, reload the page, and try again.');
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function categories() {
    return db()->query('SELECT name FROM categories ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
}
