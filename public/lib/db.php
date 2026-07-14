<?php
/**
 * SQLite connection + first-run schema/seed.
 * The database lives in public/data/blog.sqlite (protected by .htaccess).
 */

function db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . '/blog.sqlite';
    $fresh = !file_exists($path);

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($fresh) db_init($pdo);
    return $pdo;
}

function db_init($pdo) {
    $pdo->exec("
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'Essay',
            excerpt TEXT NOT NULL DEFAULT '',
            body TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'draft',
            published_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            views INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        );
        CREATE TABLE subscribers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            synced INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );
    ");

    $pdo->exec("INSERT INTO categories (name) VALUES ('Essay'), ('Devotional')");

    $settings = array(
        'site_title'         => 'The Covenant Blog',
        'site_url'           => 'https://covenantblog.us',
        'author_name'        => 'Steve Febbraro',
        'brand_main'         => 'The Covenant',
        'brand_accent'       => 'Blog',
        'brand_tagline'      => '“Scripture is the school of the Holy Spirit” — John Calvin',
        'meta_description'   => 'Thoughtful Christian writing on Scripture, culture, prayer, and the quiet work of becoming a people shaped by Christ.',
        'hero_eyebrow'       => 'Essays for ordinary faithfulness',
        'hero_title'         => "Rooted in grace.\nAttentive to life.",
        'hero_intro'         => 'Thoughtful Christian writing on Scripture, culture, prayer, and the quiet work of becoming a people shaped by Christ.',
        'hero_quote'         => 'The ordinary means of grace are never ordinary when God is pleased to meet us there.',
        'about_eyebrow'      => 'About the writer',
        'about_heading'      => 'For the church, and for the road.',
        'about_body'         => "I write for Christians who want to bring their whole lives under the kind and searching light of Scripture. Here you'll find essays that linger over ideas and short devotionals for ordinary mornings.\n\nMy theological home is in the Reformed tradition: convinced that grace is deeper than our striving, that the local church matters, and that the Word of God is sufficient for the life we have actually been given.",
        'substack_url'       => 'https://stevefebbraro.substack.com',
        'substack_heading'   => 'Essays live on Substack.',
        'substack_text'      => 'The devotionals here are short by design. For essays and longer writing, join me on Substack.',
        'newsletter_heading' => 'Receive new writing in your inbox.',
        'footer_note'        => 'Made for careful reading.',
        'newsletter_api_key' => '',
        'admin_password_hash'=> '',
    );
    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) $stmt->execute(array($k, $v));

    $posts = array(
        array('the-patience-of-ordinary-grace', 'The Patience of Ordinary Grace', 'Essay', '2026-07-11',
            'God often does His deepest work by the means we are most tempted to overlook.',
            "There is a kind of faithfulness that makes very little noise. It opens the Bible again. It comes to worship again. It prays again, even when the prayer feels small.\n\nThe Reformed tradition has given us a helpful phrase for this: the ordinary means of grace. The Word read and preached, the sacraments, and prayer are not dramatic techniques. They are the ordinary pathways by which the Spirit keeps drawing us to Christ.\n\nWe do not need to manufacture a spiritual life. We need to keep returning to the places where God has promised to meet His people."),
        array('learning-to-receive-the-day', 'Learning to Receive the Day', 'Devotional', '2026-07-08',
            'A morning meditation on limits, gratitude, and the gift of today.',
            "This day arrives as a gift before it becomes a task. Its hours are not ours to master; they are ours to receive.\n\nBefore the lists and the noise, remember: the God who made you is not anxious about your unfinished work. He is near, and He is faithful."),
        array('what-we-mean-by-hope', 'What We Mean by Hope', 'Essay', '2026-07-02',
            'Christian hope is not optimism with religious vocabulary. It has a name and a history.',
            "Hope is not a polished way to say that things will work out. Christian hope rests on something sturdier: Christ has died, Christ is risen, and Christ will come again.\n\nThat does not make sorrow less sorrowful. It does mean sorrow does not have the final word."),
        array('a-prayer-before-the-conversation', 'A Prayer Before the Conversation', 'Devotional', '2026-06-28',
            'When words feel important, ask first for a listening heart.',
            "Lord, make me slow to speak and quick to listen. Keep me from needing to win. Give me words that are true, gentle, and fitted for the person in front of me. Amen."),
    );
    $stmt = $pdo->prepare("INSERT INTO posts (slug, title, category, published_at, excerpt, body, status)
                           VALUES (?, ?, ?, ?, ?, ?, 'published')");
    foreach ($posts as $p) $stmt->execute($p);
}
