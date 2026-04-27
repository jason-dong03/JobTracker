<?php
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$upload_dir = __DIR__ . '/../storage/documents/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Serve file for preview/download
if ($method === 'GET' && $id) {
    $stmt = $db->prepare(
        "SELECT d.file_name, d.disk_name FROM documents d
         JOIN uploads u ON d.doc_id = u.doc_id
         WHERE u.user_id = ? AND d.doc_id = ?"
    );
    $stmt->bind_param('ii', $user_id, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !$row['disk_name']) {
        http_response_code(404);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'File not found']));
    }

    $filepath = $upload_dir . $row['disk_name'];
    if (!file_exists($filepath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'File not found on disk']));
    }

    $mime = mime_content_type($filepath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);

    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . $row['file_name'] . '"');
    } else {
        header('Content-Disposition: inline; filename="' . $row['file_name'] . '"');
    }

    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

// List all documents with linked applications
if ($method === 'GET') {
    header('Content-Type: application/json');
    $stmt = $db->prepare(
        "SELECT d.doc_id, d.file_name, d.disk_name, u.uploaded_at,
                GROUP_CONCAT(DISTINCT CONCAT(a.role_title, ' @ ', co.company_name) SEPARATOR ', ') AS linked_apps
         FROM documents d
         JOIN uploads u ON d.doc_id = u.doc_id
         LEFT JOIN application_documents ad ON d.doc_id = ad.doc_id
         LEFT JOIN applications a ON ad.application_id = a.application_id
         LEFT JOIN companies co ON a.company_id = co.company_id
         WHERE u.user_id = ?
         GROUP BY d.doc_id
         ORDER BY u.uploaded_at DESC"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    header('Content-Type: application/json');
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'No file uploaded or upload error'], 400);
    }

    $file = $_FILES['document'];
    $filename = basename($file['name']);

    // Create safe filename
    $safe_name = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $filename);
    $disk_name = $user_id . '_' . time() . '_' . $safe_name;
    $dest = $upload_dir . $disk_name;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $stmt1 = $db->prepare("INSERT INTO documents (file_name, disk_name) VALUES (?, ?)");
        $stmt1->bind_param('ss', $filename, $disk_name);
        $stmt1->execute();
        $doc_id = $db->insert_id;

        $stmt2 = $db->prepare("INSERT INTO uploads (user_id, doc_id) VALUES (?, ?)");
        $stmt2->bind_param('ii', $user_id, $doc_id);
        $stmt2->execute();

        json_response(['doc_id' => $doc_id, 'file_name' => $filename], 201);
    } else {
        json_response(['error' => 'Failed to save file on server'], 500);
    }
}

if ($method === 'DELETE' && $id) {
    header('Content-Type: application/json');
    $stmt = $db->prepare("SELECT d.file_name, d.disk_name FROM uploads u JOIN documents d ON u.doc_id = d.doc_id WHERE u.user_id = ? AND u.doc_id = ?");
    $stmt->bind_param('ii', $user_id, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) json_response(['error' => 'Not found or not yours'], 404);

    // Delete physical file if disk_name exists
    if ($row['disk_name']) {
        $filepath = $upload_dir . $row['disk_name'];
        if (file_exists($filepath)) unlink($filepath);
    }

    $del = $db->prepare("DELETE FROM documents WHERE doc_id = ?");
    $del->bind_param('i', $id);
    $del->execute();

    json_response(['deleted' => true]);
}
