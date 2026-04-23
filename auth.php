<?php
require_once 'config/db.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'login') {
    $db = get_db();
    $stmt = $db->prepare("SELECT user_id, password FROM users WHERE email = ?");
    $stmt->bind_param('s', $_POST['email']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        header('Location: index.php');
    } else {
        header('Location: login.php?error=credentials');
    }
    exit;
}

if ($action === 'register') {
    $db = get_db();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param('s', $_POST['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: login.php?mode=register&error=email');
        exit;
    }
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss', $_POST['email'], $hash, $_POST['first_name'], $_POST['last_name']);
    $stmt->execute();
    $_SESSION['user_id'] = $db->insert_id;
    header('Location: index.php');
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

header('Location: login.php');
exit;
