<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ' . page_url('auth/login.php'));
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$status = $conn->real_escape_string($_GET['status'] ?? '');
$tutor_id = (int) $_SESSION['user_id'];

if ($id && in_array($status, ['accepted', 'rejected'], true)) {
    $conn->query("UPDATE bookings SET status = '$status' WHERE id = $id AND tutor_id = $tutor_id");
}

header('Location: ' . page_url('tutor/dashboard.php'));
exit;
