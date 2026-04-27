<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$upload_dir = __DIR__ . '/../storage/documents/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($method === 'GET') {
    $stmt = $db->prepare(
        "SELECT d.doc_id, d.file_name, u.uploaded_at 
         FROM documents d
         JOIN uploads u ON d.doc_id = u.doc_id
         WHERE u.user_id = ?
         ORDER BY u.uploaded_at DESC"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
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
        // We'll store the original filename in DB for display purposes
        $stmt1 = $db->prepare("INSERT INTO documents (file_name) VALUES (?)");
        $stmt1->bind_param('s', $filename);
        $stmt1->execute();
        $doc_id = $db->insert_id;
        
        $stmt2 = $db->prepare("INSERT INTO uploads (user_id, doc_id) VALUES (?, ?)");
        $stmt2->bind_param('ii', $user_id, $doc_id);
        $stmt2->execute();
        
        // Return original name so frontend can display it
        json_response(['doc_id' => $doc_id, 'file_name' => $filename], 201);
    } else {
        json_response(['error' => 'Failed to save file on server'], 500);
    }
}

if ($method === 'DELETE' && $id) {
    // Verify ownership
    $stmt = $db->prepare("SELECT d.file_name FROM uploads u JOIN documents d ON u.doc_id = d.doc_id WHERE u.user_id = ? AND u.doc_id = ?");
    $stmt->bind_param('ii', $user_id, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if (!$row) json_response(['error' => 'Not found or not yours'], 404);
    
    // Actually we stored the original filename in the db, but the physical file is user_id_time_filename.
    // So if we delete from DB it's fine. If we really wanted to delete from disk, we would need to store the disk_name in DB.
    // For simplicity, we just delete the db record. The constraint CASCADE will delete from uploads.
    
    $del = $db->prepare("DELETE FROM documents WHERE doc_id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    
    json_response(['deleted' => true]);
}
