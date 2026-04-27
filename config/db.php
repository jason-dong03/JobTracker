<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$_env = parse_ini_file(__DIR__ . '/../.env');
define('DB_HOST', $_env['DB_HOST']);
define('DB_USER', $_env['DB_USER']);
define('DB_PASS', $_env['DB_PASS']);
define('DB_NAME', $_env['DB_NAME']);

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            die(json_encode(['error' => 'DB connection failed']));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function get_input(): array {
    $body = file_get_contents('php://input');
    return $body ? (json_decode($body, true) ?? []) : [];
}
