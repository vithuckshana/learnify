<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('admin');

$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($id && in_array($action, ['approve', 'reject', 'suspend'], true)) {
    $status = $action === 'approve' ? 'active' : ($action === 'reject' ? 'rejected' : 'suspended');
    $conn->query("UPDATE users SET status = '$status' WHERE id = $id AND role = 'tutor'");
}

header('Location: ' . page_url('admin/dashboard.php'));
exit;
