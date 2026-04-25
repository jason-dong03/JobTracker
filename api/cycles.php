<?php
require_once __DIR__ . '/../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db      = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user_id = (int)$_SESSION['user_id'];

if ($method === 'GET' && !$id) {
    if (!empty($_GET['mine'])) {
        $stmt = $db->prepare(
            "SELECT ac.cycle_id, ac.cycle_name, COUNT(s.application_id) AS app_count
             FROM application_cycles ac
             LEFT JOIN applications a ON ac.cycle_id = a.cycle_id
             LEFT JOIN submits s ON a.application_id = s.application_id AND s.user_id = ?
             WHERE ac.user_id = ? OR ac.user_id IS NULL
             GROUP BY ac.cycle_id, ac.cycle_name
             ORDER BY ac.cycle_id DESC"
        );
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT cycle_id, cycle_name FROM application_cycles WHERE user_id = ? OR user_id IS NULL ORDER BY cycle_id DESC");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'GET' && $id) {
    $stmt = $db->prepare("SELECT cycle_id, cycle_name FROM application_cycles WHERE cycle_id = ? AND (user_id = ? OR user_id IS NULL)");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(['error' => 'Not found'], 404);
    json_response($row);
}

if ($method === 'POST') {
    $d = get_input();
    $stmt = $db->prepare("INSERT INTO application_cycles (cycle_name, user_id) VALUES (?, ?)");
    $stmt->bind_param('si', $d['cycle_name'], $user_id);
    $stmt->execute();
    json_response(['cycle_id' => $db->insert_id], 201);
}

if ($method === 'PUT' && $id) {
    $d = get_input();
    $stmt = $db->prepare("UPDATE application_cycles SET cycle_name=? WHERE cycle_id=? AND user_id=?");
    $stmt->bind_param('sii', $d['cycle_name'], $id, $user_id);
    $stmt->execute();
    json_response(['updated' => $stmt->affected_rows]);
}

if ($method === 'DELETE' && $id) {
    $stmt = $db->prepare("DELETE FROM application_cycles WHERE cycle_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    json_response(['deleted' => $stmt->affected_rows]);
}
