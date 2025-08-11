<?php

if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}

$_SESSION['message_count'] = 0;
$_SESSION['last_reset_time'] = time();

echo json_encode(['success' => true]);
?>