<?php
require_once __DIR__ . '/../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user_id = (int)$_SESSION['user_id'];

if ($method === 'GET' && !$id) {
    if (!empty($_GET['mine'])) {
        $stmt = $db->prepare(
            "SELECT ci.city_id, ci.city_name, ci.state_name, COUNT(a.application_id) AS app_count
             FROM cities ci
             JOIN applications a ON ci.city_id = a.city_id
             JOIN submits s ON a.application_id = s.application_id
             WHERE s.user_id = ?
             GROUP BY ci.city_id, ci.city_name, ci.state_name
             ORDER BY ci.city_name ASC"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT city_id, city_name, state_name FROM cities ORDER BY city_name ASC");
        $stmt->execute();
    }
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    $d = get_input();
    $state = $d['state_name'] ?? '';
    $stmt = $db->prepare("INSERT INTO cities (city_name, state_name) VALUES (?,?)");
    $stmt->bind_param('ss', $d['city_name'], $state);
    $stmt->execute();
    json_response(['city_id' => $db->insert_id], 201);
}

if ($method === 'DELETE' && $id) {
    $stmt = $db->prepare("DELETE FROM cities WHERE city_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['deleted' => $stmt->affected_rows]);
}
