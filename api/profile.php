<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }

$db = get_db();
$method  = $_SERVER['REQUEST_METHOD'];
$user_id = (int)$_SESSION['user_id'];

if ($method === 'GET') {
    $stmt = $db->prepare(
        "SELECT u.user_id, u.email, u.first_name, u.last_name, u.created_at,
                p.biography, p.profile_picture,
                t.term_id, t.start_date, t.end_date, t.degree_type, t.major,
                s.school_id, s.school_name
         FROM users u
         LEFT JOIN profiles p ON u.user_id = p.user_id
         LEFT JOIN terms t ON u.user_id = t.user_id
         LEFT JOIN schools s ON t.school_id = s.school_id
         WHERE u.user_id = ?
         ORDER BY t.term_id DESC LIMIT 1"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(['error' => 'Not found'], 404);
    json_response($row);
}

if ($method === 'POST') {
    $d = $_POST;
    $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=? WHERE user_id=?");
    $stmt->bind_param('ssi', $d['first_name'], $d['last_name'], $user_id);
    $stmt->execute();

    $bio = $d['biography'] ?? '';
    $stmt2 = $db->prepare(
        "INSERT INTO profiles (user_id, biography) VALUES (?,?)
         ON DUPLICATE KEY UPDATE biography=VALUES(biography)"
    );
    $stmt2->bind_param('is', $user_id, $bio);
    $stmt2->execute();
    $school_id = !empty($d['school_id']) ? (int)$d['school_id'] : null;
    if ($school_id) {
        $stmt_t = $db->prepare("SELECT term_id FROM terms WHERE user_id = ? ORDER BY term_id DESC LIMIT 1");
        $stmt_t->bind_param('i', $user_id);
        $stmt_t->execute();
        $t_row = $stmt_t->get_result()->fetch_assoc();
        
        $sd = $d['start_date'] ?: '2000-01-01';
        $ed = $d['end_date'] ?: '2000-01-01';
        $dt = $d['degree_type'] ?? '';
        $mj = $d['major'] ?? '';

        if ($t_row) {
            $stmt_upd = $db->prepare("UPDATE terms SET school_id=?, start_date=?, end_date=?, degree_type=?, major=? WHERE term_id=?");
            $stmt_upd->bind_param('issssi', $school_id, $sd, $ed, $dt, $mj, $t_row['term_id']);
            $stmt_upd->execute();
        } else {
            $stmt_ins2 = $db->prepare("INSERT INTO terms (user_id, school_id, start_date, end_date, degree_type, major) VALUES (?,?,?,?,?,?)");
            $stmt_ins2->bind_param('iissss', $user_id, $school_id, $sd, $ed, $dt, $mj);
            $stmt_ins2->execute();
        }
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../storage/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file = $_FILES['profile_picture'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_name = $user_id . '_' . time() . '.' . $ext;
        $dest = $upload_dir . $safe_name;
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt_pic = $db->prepare("UPDATE profiles SET profile_picture = ? WHERE user_id = ?");
            $stmt_pic->bind_param('si', $safe_name, $user_id);
            $stmt_pic->execute();
        }
    }

    json_response(['updated' => true]);
}
