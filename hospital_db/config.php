<?php
if(session_status()===PHP_SESSION_NONE) session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hospital_db';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#A32D2D;background:#FCEBEB;border-radius:8px;margin:40px;">
        <strong>Database connection failed:</strong> ' . $conn->connect_error . '
    </div>');
}

function sanitize($conn, $val) {
    return $conn->real_escape_string(trim($val));
}
function redirect($url) {
    header("Location: $url");
    exit;
}
function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

// Cek domain email admin
function isAdminEmail($email){
    return str_ends_with($email, '@sehatbersama.hospital.com');
}