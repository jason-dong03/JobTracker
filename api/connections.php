<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db      = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$user_id = (int)$_SESSION['user_id'];

if ($method === 'GET') {
    // List connections
    $stmt = $db->prepare(
        "SELECT u.user_id, u.first_name, u.last_name, u.email,
                p.biography, p.profile_picture, s.school_name, t.major, t.degree_type
         FROM user_connections uc
         JOIN users u ON uc.connected_user_id = u.user_id
         LEFT JOIN profiles p ON u.user_id = p.user_id
         LEFT JOIN terms t ON u.user_id = t.user_id
         LEFT JOIN schools s ON t.school_id = s.school_id
         WHERE uc.user_id = ?
         GROUP BY u.user_id"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    $d = get_input();
    $email = $d['email'] ?? '';
    
    if (!$email) json_response(['error' => 'Email required'], 400);
    
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if (!$row) json_response(['error' => 'User not found'], 404);
    
    $conn_id = $row['user_id'];
    if ($conn_id === $user_id) json_response(['error' => 'Cannot connect to yourself'], 400);
    
    // Bidirectional
    $stmt2 = $db->prepare("INSERT IGNORE INTO user_connections (user_id, connected_user_id) VALUES (?, ?)");
    $stmt2->bind_param('ii', $user_id, $conn_id);
    $stmt2->execute();
    
    $stmt3 = $db->prepare("INSERT IGNORE INTO user_connections (user_id, connected_user_id) VALUES (?, ?)");
    $stmt3->bind_param('ii', $conn_id, $user_id);
    $stmt3->execute();
    
    json_response(['success' => true]);
}
