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
    // mine=1 → only companies linked to user's applications
    if (!empty($_GET['mine'])) {
        $stmt = $db->prepare(
            "SELECT co.company_id, co.company_name, COUNT(a.application_id) AS app_count
             FROM companies co
             JOIN applications a ON co.company_id = a.company_id
             JOIN submits s ON a.application_id = s.application_id
             WHERE s.user_id = ?
             GROUP BY co.company_id, co.company_name
             ORDER BY co.company_name ASC"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT company_id, company_name FROM companies ORDER BY company_name ASC");
        $stmt->execute();
    }
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'GET' && $id) {
    $stmt = $db->prepare("SELECT company_id, company_name FROM companies WHERE company_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(['error' => 'Not found'], 404);
    json_response($row);
}

if ($method === 'POST') {
    $d = get_input();
    $stmt = $db->prepare("INSERT INTO companies (company_name) VALUES (?)");
    $stmt->bind_param('s', $d['company_name']);
    $stmt->execute();
    json_response(['company_id' => $db->insert_id], 201);
}

if ($method === 'PUT' && $id) {
    $d = get_input();
    $stmt = $db->prepare("UPDATE companies SET company_name=? WHERE company_id=?");
    $stmt->bind_param('si', $d['company_name'], $id);
    $stmt->execute();
    json_response(['updated' => $stmt->affected_rows]);
}

if ($method === 'DELETE' && $id) {
    $stmt = $db->prepare("DELETE FROM companies WHERE company_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['deleted' => $stmt->affected_rows]);
}
