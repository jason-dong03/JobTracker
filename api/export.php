<?php
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); die('Unauthorized'); }

$db = get_db();
$format  = $_GET['format'] ?? 'json';
$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT a.application_id, a.role_title, a.status, a.created_at,
               co.company_name, ac.cycle_name,
               ci.city_name, ci.state_name
        FROM applications a
        JOIN submits s ON a.application_id = s.application_id
        JOIN companies co ON a.company_id = co.company_id
        JOIN application_cycles ac ON a.cycle_id = ac.cycle_id
        JOIN cities ci ON a.city_id = ci.city_id
        WHERE s.user_id = ?";

$types = 'i';
$params = [$user_id];

if (!empty($_GET['cycle_id'])) {
    $sql .= " AND a.cycle_id = ?";
    $types .= 'i';
    $params[] = (int)$_GET['cycle_id'];
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Disposition: attachment; filename="applications_export.json"');
json_response($result);
