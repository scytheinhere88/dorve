<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['lang'] ?? 'id';

    if (in_array($lang, ['id', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? '/index.php';
header("Location: " . $redirect);
exit();
