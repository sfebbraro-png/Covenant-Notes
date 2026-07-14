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
    $base = rtrim(setting('site_url', 'https://covenantblog.us'), '/');
    return $base . $path;
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

function inline_format($text) {
    $text = e($text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
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
