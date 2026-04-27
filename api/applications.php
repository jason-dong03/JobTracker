<?php
require_once __DIR__ . '/../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user_id = (int)$_SESSION['user_id'];

if ($method === 'GET' && !$id) {
    $where  = ['s.user_id = ?'];
    $params = [$user_id];
    $types  = 'i';

    if (!empty($_GET['cycle_id'])) { $where[] = 'a.cycle_id = ?';  $params[] = (int)$_GET['cycle_id']; $types .= 'i'; }
    if (!empty($_GET['status']))   { $where[] = 'a.status = ?';    $params[] = $_GET['status']; $types .= 's'; }
    if (!empty($_GET['company_id'])){ $where[] = 'a.company_id = ?'; $params[] = (int)$_GET['company_id']; $types .= 'i'; }

    $allowed = ['created_at','status','role_title','company_name'];
    $sort = in_array($_GET['sort'] ?? '', $allowed) ? $_GET['sort'] : 'created_at';
    $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $col = $sort === 'company_name' ? 'co.company_name' : 'a.' . $sort;

    $sql = "SELECT a.application_id, a.role_title, a.status, a.created_at,
                   co.company_name, ac.cycle_name,
                   ci.city_name, ci.state_name,
                   CONCAT(ci.city_name, IF(ci.state_name != '', CONCAT(', ', ci.state_name), '')) AS location
            FROM applications a
            JOIN submits s ON a.application_id = s.application_id
            JOIN companies co ON a.company_id = co.company_id
            JOIN application_cycles ac ON a.cycle_id = ac.cycle_id
            JOIN cities ci ON a.city_id = ci.city_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $col $dir";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'GET' && $id) {
    $stmt = $db->prepare(
        "SELECT a.*, co.company_name, ac.cycle_name, ci.city_name, ci.state_name
         FROM applications a
         JOIN submits s ON a.application_id = s.application_id
         JOIN companies co ON a.company_id = co.company_id
         JOIN application_cycles ac ON a.cycle_id = ac.cycle_id
         JOIN cities ci ON a.city_id = ci.city_id
         WHERE a.application_id = ? AND s.user_id = ?"
    );
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(['error' => 'Not found'], 404);
    $stmt2 = $db->prepare("SELECT doc_id FROM application_documents WHERE application_id = ?");
    $stmt2->bind_param('i', $id);
    $stmt2->execute();
    $docs = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $row['document_ids'] = array_column($docs, 'doc_id');

    json_response($row);
}

if ($method === 'POST') {
    $d = get_input();
    $db->begin_transaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO applications (role_title, status, company_id, city_id, cycle_id) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param('ssiii', $d['role_title'], $d['status'], $d['company_id'], $d['city_id'], $d['cycle_id']);
        $stmt->execute();
        $app_id = $db->insert_id;

        $stmt2 = $db->prepare("INSERT INTO submits (user_id, application_id) VALUES (?,?)");
        $stmt2->bind_param('ii', $user_id, $app_id);
        $stmt2->execute();

        if (isset($d['document_ids']) && is_array($d['document_ids'])) {
            $stmt3 = $db->prepare("INSERT INTO application_documents (application_id, doc_id) VALUES (?, ?)");
            foreach ($d['document_ids'] as $doc_id) {
                $stmt3->bind_param('ii', $app_id, $doc_id);
                $stmt3->execute();
            }
        }

        $db->commit();
        json_response(['application_id' => $app_id], 201);
    } catch (Exception $e) {
        $db->rollback();
        json_response(['error' => $e->getMessage()], 500);
    }
}

if ($method === 'PUT' && $id) {
    $d = get_input();

    // Verify ownership before touching anything
    $check = $db->prepare("SELECT 1 FROM submits WHERE application_id = ? AND user_id = ?");
    $check->bind_param('ii', $id, $user_id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) json_response(['error' => 'Not found'], 404);

    $db->begin_transaction();
    try {
        // Update non-status fields
        $stmt = $db->prepare(
            "UPDATE applications SET role_title=?, company_id=?, city_id=?, cycle_id=?
             WHERE application_id=?"
        );
        $stmt->bind_param('siiii', $d['role_title'], $d['company_id'], $d['city_id'], $d['cycle_id'], $id);
        $stmt->execute();

        // Use stored procedure for status (enforces CHECK constraint via procedure)
        $stmt2 = $db->prepare("CALL update_application_status(?, ?)");
        $stmt2->bind_param('is', $id, $d['status']);
        $stmt2->execute();

        $stmt_del = $db->prepare("DELETE FROM application_documents WHERE application_id = ?");
        $stmt_del->bind_param('i', $id);
        $stmt_del->execute();

        if (isset($d['document_ids']) && is_array($d['document_ids'])) {
            $stmt_add = $db->prepare("INSERT INTO application_documents (application_id, doc_id) VALUES (?, ?)");
            foreach ($d['document_ids'] as $doc_id) {
                $stmt_add->bind_param('ii', $id, (int)$doc_id);
                $stmt_add->execute();
            }
        }

        $db->commit();
        json_response(['updated' => true]);
    } catch (Exception $e) {
        $db->rollback();
        json_response(['error' => $e->getMessage()], 500);
    }
}

if ($method === 'DELETE' && $id) {
    // JOIN with submits ensures only the owner can delete their application
    $stmt = $db->prepare(
        "DELETE a FROM applications a
         JOIN submits s ON a.application_id = s.application_id
         WHERE a.application_id = ? AND s.user_id = ?"
    );
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) json_response(['error' => 'Not found'], 404);
    json_response(['deleted' => $stmt->affected_rows]);
}
