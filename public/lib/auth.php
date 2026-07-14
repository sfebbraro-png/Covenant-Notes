<?php

function is_logged_in() {
    return !empty($_SESSION['writer']);
}

function require_login() {
    if (!is_logged_in()) redirect('login.php');
}

function password_is_set() {
    return setting('admin_password_hash') !== '';
}

function set_admin_password($password) {
    save_setting('admin_password_hash', password_hash($password, PASSWORD_DEFAULT));
}

function attempt_login($password) {
    // Small fixed delay blunts brute-force attempts on a shared host.
    usleep(400000);
    if (password_verify($password, setting('admin_password_hash'))) {
        session_regenerate_id(true);
        $_SESSION['writer'] = true;
        return true;
    }
    return false;
}
