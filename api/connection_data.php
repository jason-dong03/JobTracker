<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$user_id = (int)$_SESSION['user_id'];
$conn_id = (int)($_GET['id'] ?? 0);

if (!$conn_id) { json_response(['error' => 'ID required'], 400); }


$check = $db->prepare("SELECT 1 FROM user_connections WHERE user_id = ? AND connected_user_id = ?");
$check->bind_param('ii', $user_id, $conn_id);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    json_response(['error' => 'Not connected'], 403);
}

// fetch profile
$p_stmt = $db->prepare(
    "SELECT u.user_id, u.first_name, u.last_name, u.email, u.created_at,
            p.biography, p.profile_picture, s.school_name, t.major, t.degree_type, t.start_date, t.end_date
     FROM users u
     LEFT JOIN profiles p ON u.user_id = p.user_id
     LEFT JOIN terms t ON u.user_id = t.user_id
     LEFT JOIN schools s ON t.school_id = s.school_id
     WHERE u.user_id = ?
     ORDER BY t.term_id DESC LIMIT 1"
);
$p_stmt->bind_param('i', $conn_id);
$p_stmt->execute();
$profile = $p_stmt->get_result()->fetch_assoc();

// fetch cycles
$c_stmt = $db->prepare("SELECT cycle_id, cycle_name FROM application_cycles WHERE user_id = ? OR user_id IS NULL ORDER BY cycle_id DESC");
$c_stmt->bind_param('i', $conn_id);
$c_stmt->execute();
$cycles = $c_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// fetch applications
$a_stmt = $db->prepare(
    "SELECT a.application_id, a.role_title, a.status, a.created_at, a.cycle_id,
            co.company_name, ci.city_name, ci.state_name
     FROM applications a
     JOIN submits s ON a.application_id = s.application_id
     JOIN companies co ON a.company_id = co.company_id
     JOIN cities ci ON a.city_id = ci.city_id
     WHERE s.user_id = ?
     ORDER BY a.created_at DESC"
);
$a_stmt->bind_param('i', $conn_id);
$a_stmt->execute();
$apps = $a_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

json_response([
    'profile' => $profile,
    'cycles' => $cycles,
    'applications' => $apps
]);
