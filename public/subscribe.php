<?php
require_once __DIR__ . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/#subscribe');
csrf_check();

$return = '/';
if (isset($_POST['return']) && strpos($_POST['return'], '/post.php?post=') === 0) {
    $return = $_POST['return'];
}
$sep = strpos($return, '?') === false ? '?' : '&';

$email = trim(isset($_POST['email']) ? $_POST['email'] : '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect($return . $sep . 'sub_error=' . rawurlencode('Please enter a valid email address.') . '#subscribe');
}

try {
    db()->prepare('INSERT INTO subscribers (email) VALUES (?)')->execute(array($email));
} catch (PDOException $e) {
    // Duplicate email: treat as success, the reader is already subscribed.
}

// Forward to the newsletter service when an API key is configured.
$api_key = setting('newsletter_api_key');
if ($api_key !== '') {
    $ch = curl_init('https://api.buttondown.email/v1/subscribers');
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(array('email_address' => $email)),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Token ' . $api_key,
            'Content-Type: application/json',
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ));
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        db()->prepare('UPDATE subscribers SET synced = 1 WHERE email = ?')->execute(array($email));
    }
}

redirect($return . $sep . 'subscribed=1#subscribe');
