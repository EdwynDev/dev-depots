<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['chat_history'] = [];
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false]);
exit;
?>