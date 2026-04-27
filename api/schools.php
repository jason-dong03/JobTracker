<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $res = $db->query("SELECT * FROM schools ORDER BY school_name ASC");
    json_response($res->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    $d = get_input();
    $name = trim($d['school_name'] ?? '');
    if (!$name) json_response(['error' => 'Name required'], 400);

    // See if exists
    $stmt = $db->prepare("SELECT school_id FROM schools WHERE school_name = ?");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if ($row) {
        json_response(['school_id' => $row['school_id']], 201);
    } else {
        $stmt2 = $db->prepare("INSERT INTO schools (school_name) VALUES (?)");
        $stmt2->bind_param('s', $name);
        $stmt2->execute();
        json_response(['school_id' => $db->insert_id], 201);
    }
}
