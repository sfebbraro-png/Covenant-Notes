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
    db_migrate($pdo);
    return $pdo;
}

/**
 * Small, idempotent data corrections for existing persistent databases.
 * These intentionally target only known legacy defaults and preserve anything
 * the writer has customized in the desk.
 */
function db_migrate($pdo) {
    $columns = array();
    foreach ($pdo->query('PRAGMA table_info(posts)') as $column) $columns[] = $column['name'];
    if (!in_array('scripture', $columns, true)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN scripture TEXT NOT NULL DEFAULT ''");
    }
    // Optional search-engine title. Never rendered on the page; falls back to
    // the post title when left empty.
    if (!in_array('seo_title', $columns, true)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN seo_title TEXT NOT NULL DEFAULT ''");
    }

    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'site_url' AND value IN (?, ?, ?)");
    $stmt->execute(array(
        'https://thecovenantblog.org',
        'https://covenantblog.us',
        'https://www.covenantblog.us',
        'http://covenantblog.us',
    ));

    // Essays are now published on the blog itself, so the Substack panel offers a
    // choice rather than sending readers away. Only rewrites the untouched defaults.
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'substack_heading' AND value = ?");
    $stmt->execute(array(
        'Essays, here or on Substack.',
        'Essays live on Substack.',
    ));

    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'substack_text' AND value = ?");
    $stmt->execute(array(
        'Essays are published right here on the blog, and they go out on Substack as well. Read them wherever you like it best.',
        'The devotionals here are short by design. For essays and longer writing, join me on Substack.',
    ));

    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'brand_tagline' AND value = ?");
    $stmt->execute(array(
        '“The Bible is the school of the Holy Spirit.” — John Calvin',
        '“Scripture is the school of the Holy Spirit” — John Calvin',
    ));

    db_migrate_once($pdo, 'seo_titles_2026_08_10', 'db_apply_seo_titles');
    db_migrate_once($pdo, 'seo_audit_2026_08_27', 'db_apply_seo_audit');
    db_migrate_once($pdo, 'seo_audit_2026_08_30', 'db_apply_seo_audit_2026_08_30');
    db_migrate_once($pdo, 'seo_metadata_2026_09_06', 'db_apply_seo_metadata');
    db_migrate_once($pdo, 'seo_titles_2026_09_06b', 'db_apply_seo_titles_2026_09_06b');
}

/**
 * Runs a named migration a single time, recording it in settings so the work
 * does not repeat on every request the way the corrections above do.
 */
function db_migrate_once($pdo, $name, $callback) {
    $key = 'migration_' . $name;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE key = ?');
    $stmt->execute(array($key));
    if ($stmt->fetchColumn()) return;

    call_user_func($callback, $pdo);

    $stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $stmt->execute(array($key, date('c')));
}

/**
 * Rewrites the search-engine titles. The originals were being truncated by
 * Google, and three of them misattributed Scripture: Matthew 28:19 reads "all
 * nations" not "all men", "do not be anxious about anything" is Philippians 4:6
 * rather than Matthew 6:25, and the milk of Hebrews 5 is verse 12, not 11.
 */
function db_apply_seo_titles($pdo) {
    $titles = array(
        'turn-off-the-music'    => 'Is Worship Music Necessary in Church?',
        'nobody-plows-alone'    => 'Matthew 11:28-30: What the Yoke Means',
        'a-church-in-disgrace'  => 'What Happens After the Altar Call?',
        'what-are-you-thinking' => 'Jeremiah 29:11 When Life Makes No Sense',
        'knives-and-forks'      => 'Hebrews 5:12-14: From Milk to Meat',
        'hard-verse-to-follow'  => 'Why Matthew 6:25 Is Hard to Believe',
    );
    $stmt = $pdo->prepare('UPDATE posts SET seo_title = ? WHERE slug = ?');
    foreach ($titles as $slug => $seo_title) {
        $stmt->execute(array($seo_title, $slug));
    }

    // Only fills the front page title if it has not been set by hand.
    $current = $pdo->query("SELECT value FROM settings WHERE key = 'home_seo_title'")->fetchColumn();
    if ($current === false || trim((string)$current) === '') {
        $stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
        $stmt->execute(array('home_seo_title', 'The Covenant Blog — Reformed Devotionals & Essays'));
    }
}

/**
<<<<<<< HEAD
 * On-page SEO audit: visible titles, search-engine titles, excerpts, and the
 * homepage meta description. Updates by slug only; leaves slugs, body, and
 * other columns untouched.
 */
function db_apply_seo_audit($pdo) {
    $titles = array(
        'the-armor-of-god' => 'The Armor of God Is Not a Cliche',
        'stop-and-think'   => 'Count It Joy When Trouble Comes',
    );
    $stmt = $pdo->prepare('UPDATE posts SET title = ? WHERE slug = ?');
    foreach ($titles as $slug => $title) {
        $stmt->execute(array($title, $slug));
    }

    $seo_titles = array(
        'there-is-no-switzerland-in-this-war'                      => 'Virginia 2026 Abortion Amendments',
        'contentment'                                              => 'Hebrews 13:5 and True Contentment',
        'how-strange-are-you'                                      => '1 Peter: Christians as Elect Exiles',
        'the-megachurch'                                           => 'Is a Megachurch a Better Church?',
        'the-god-who-works-through-ordinary-things-a-study-of-ruth'=> 'God’s Providence in the Book of Ruth',
        'stop-and-think'                                           => 'James 1:2: Count It All Joy',
        'the-armor-of-god'                                         => 'Ephesians 6: The Armor of God',
        'nobody-plows-alone'                                       => 'Matthew 11:28: My Yoke Is Easy',
        'what-are-you-thinking'                                    => 'Jeremiah 29:11: Life Makes No Sense',
    );
    $stmt = $pdo->prepare('UPDATE posts SET seo_title = ? WHERE slug = ?');
    foreach ($seo_titles as $slug => $seo_title) {
        $stmt->execute(array($seo_title, $slug));
    }

    $excerpts = array(
        'there-is-no-switzerland-in-this-war' => 'A Christian case against Virginia’s 2026 abortion and marriage amendments, with voting dates and Scripture. Vote no on both questions.',
        'contentment'                         => 'Hebrews 13:5 commands contentment, not complacency. Chasing more cannot satisfy. True rest is found in the God who will never leave you.',
        'how-strange-are-you'                 => '1 Peter 1 calls Christians elect exiles. Holy conduct should look strange to the culture, even when nobody admires or understands it.',
        'stop-and-think'                      => 'James 1:2 calls Christians to count trials as joy. God uses testing to produce steadfast faith, even when the hardship itself feels painful.',
        'turn-off-the-music'                  => 'Would worship still be worship without music? This essay asks whether congregational singing serves the Word, or has become the main attraction.',
    );
    $stmt = $pdo->prepare('UPDATE posts SET excerpt = ? WHERE slug = ?');
    foreach ($excerpts as $slug => $excerpt) {
        $stmt->execute(array($excerpt, $slug));
    }

    $stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $stmt->execute(array(
        'meta_description',
        'Reformed essays and devotionals on Scripture, church, and ordinary faithfulness, written for Christians who want their whole lives under the Word.',
    ));
}

/**
 * SEO metadata for a new post. Updates by slug only; leaves title, body, and
 * other columns untouched.
 */
function db_apply_seo_audit_2026_08_30($pdo) {
    $seo_titles = array(
        'did-god-really-tell-you-that' => 'Does God Still Speak Today?',
    );
    $stmt = $pdo->prepare('UPDATE posts SET seo_title = ? WHERE slug = ?');
    foreach ($seo_titles as $slug => $seo_title) {
        $stmt->execute(array($seo_title, $slug));
    }

    $excerpts = array(
        'did-god-really-tell-you-that' => 'Strong impressions are not the same as a word from God. Test your thoughts against Scripture, seek wisdom, and obey what He has already made clear.',
    );
    $stmt = $pdo->prepare('UPDATE posts SET excerpt = ? WHERE slug = ?');
    foreach ($excerpts as $slug => $excerpt) {
        $stmt->execute(array($excerpt, $slug));
    }
}

/**
 * Keeps search snippets concise while preserving the article's visible title
 * and excerpt on the page and in social previews.
 */
function db_apply_seo_metadata($pdo) {
    $titles = array(
        'christians-are-supposed-to-be-fat' => 'Christians Are Supposed to Be Fat | Spoon-Fed Faith',
        'i-dont-know' => "I Don't Know | Faith in the Hospital Room",
        'you-cant-know-yourself-until-you-know-god' => 'Know God, Know Yourself | Christian Wisdom',
    );
    $excerpts = array(
        'i-dont-know' => 'When suffering leaves us asking why God allows it, the faithful answer may be “I don’t know”—and a reminder of God’s presence and mercy.',
        'a-church-in-disgrace' => 'A church should measure ministry by faithful discipleship, not altar-call numbers. Jesus commands churches to teach and care for new believers.',
        'what-are-you-thinking' => 'Jeremiah 29:11 does not promise an easy life. It points to God’s sovereign purposes and comfort when suffering leaves you asking what he is doing.',
    );

    $stmt = $pdo->prepare('UPDATE posts SET seo_title = ? WHERE slug = ?');
    foreach ($titles as $slug => $seo_title) $stmt->execute(array($seo_title, $slug));

    $stmt = $pdo->prepare('UPDATE posts SET excerpt = ? WHERE slug = ?');
    foreach ($excerpts as $slug => $excerpt) $stmt->execute(array($excerpt, $slug));
}

function db_apply_seo_titles_2026_09_06b($pdo) {
    $titles = array(
        'christians-are-supposed-to-be-fat' => 'Christians and Spoon-Fed Faith',
        'i-dont-know' => "I Don't Know: Faith in Suffering",
        'you-cant-know-yourself-until-you-know-god' => 'Know God, Know Yourself',
    );
    $stmt = $pdo->prepare('UPDATE posts SET seo_title = ? WHERE slug = ?');
    foreach ($titles as $slug => $seo_title) $stmt->execute(array($seo_title, $slug));
}

function db_init($pdo) {
    $pdo->exec("
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'Essay',
            scripture TEXT NOT NULL DEFAULT '',
            seo_title TEXT NOT NULL DEFAULT '',
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
        'site_url'           => 'https://thecovenantblog.org',
        'author_name'        => 'Steve Febbraro',
        'brand_main'         => 'The Covenant',
        'brand_accent'       => 'Blog',
        'brand_tagline'      => '“The Bible is the school of the Holy Spirit.” — John Calvin',
        'home_seo_title'     => '',
        'meta_description'   => 'Thoughtful Christian writing on Scripture, culture, prayer, and the quiet work of becoming a people shaped by Christ.',
        'hero_eyebrow'       => 'Essays for ordinary faithfulness',
        'hero_title'         => "Rooted in grace.\nAttentive to life.",
        'hero_intro'         => 'Thoughtful Christian writing on Scripture, culture, prayer, and the quiet work of becoming a people shaped by Christ.',
        'hero_quote'         => 'The ordinary means of grace are never ordinary when God is pleased to meet us there.',
        'about_eyebrow'      => 'About the writer',
        'about_heading'      => 'For the church, and for the road.',
        'about_body'         => "I write for Christians who want to bring their whole lives under the kind and searching light of Scripture. Here you'll find essays that linger over ideas and short devotionals for ordinary mornings.\n\nMy theological home is in the Reformed tradition: convinced that grace is deeper than our striving, that the local church matters, and that the Word of God is sufficient for the life we have actually been given.",
        'substack_url'       => 'https://stevefebbraro.substack.com',
        'substack_heading'   => 'Essays, here or on Substack.',
        'substack_text'      => 'Essays are published right here on the blog, and they go out on Substack as well. Read them wherever you like it best.',
        'newsletter_heading' => 'Receive new writing in your inbox.',
        'footer_note'        => 'Made for careful reading.',
        'post_footer'        => '',
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
