<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('tutor');

$userId = (int) $_SESSION['user_id'];
$status = $_SESSION['status'] ?? 'pending';

$res = $conn->query("SELECT status FROM users WHERE id = $userId");
if ($res && $row = $res->fetch_assoc()) {
    $status = $row['status'];
    $_SESSION['status'] = $status;
}

if ($status === 'active') {
    header('Location: ' . page_url('tutor/dashboard.php'));
    exit;
}

$tutor = get_tutor_row($conn, $userId);
$page_title = 'Application Pending';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Tutor Application</p>
    <h1 class="page-title">Awaiting <em>Approval</em></h1>
    <hr class="hairline">
</header>

<div class="card">
    <div class="alert alert-info">
        Your account <strong>is registered</strong> in our system. An admin must approve your application before you can access the tutor dashboard.
    </div>

    <p class="page-subtitle"><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($status)); ?></p>
    <p class="page-subtitle"><strong>Account ID:</strong> #<?php echo $userId; ?></p>

    <?php if ($tutor): ?>
        <p class="page-subtitle mt-2"><strong>Subject:</strong> <?php echo htmlspecialchars($tutor['subject']); ?></p>
        <p class="page-subtitle"><strong>Profile saved:</strong> Yes — your tutor details are in the database.</p>
    <?php else: ?>
        <div class="alert alert-error mt-2">
            Your tutor profile row is missing. <a href="<?php echo page_url('setup/repair.php'); ?>">Run repair</a> or contact support.
        </div>
    <?php endif; ?>

    <p class="mt-3 page-subtitle">Once approved, refresh this page or sign in again to access your dashboard.</p>
    <div class="action-links mt-2">
        <a href="<?php echo page_url('tutor/pending.php'); ?>" class="btn btn-sm">Refresh Status</a>
        <a href="<?php echo page_url('auth/logout.php'); ?>" class="btn btn-sm btn-ghost">Logout</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
