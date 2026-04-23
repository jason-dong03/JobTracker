<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db      = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$user_id = (int)$_SESSION['user_id'];

if ($method === 'GET') {
    $stmt = $db->prepare(
        "SELECT u.user_id, u.email, u.first_name, u.last_name, u.created_at,
                p.biography
         FROM users u
         LEFT JOIN profiles p ON u.user_id = p.user_id
         WHERE u.user_id = ?"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(['error' => 'Not found'], 404);
    json_response($row);
}

if ($method === 'PUT') {
    $d = get_input();
    $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=? WHERE user_id=?");
    $stmt->bind_param('ssi', $d['first_name'], $d['last_name'], $user_id);
    $stmt->execute();

    $bio = $d['biography'] ?? '';
    $stmt2 = $db->prepare(
        "INSERT INTO profiles (user_id, biography) VALUES (?,?)
         ON DUPLICATE KEY UPDATE biography=VALUES(biography)"
    );
    $stmt2->bind_param('is', $user_id, $bio);
    $stmt2->execute();

    json_response(['updated' => true]);
}
