<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('admin');

$pending = $conn->query("SELECT u.*, t.subject, t.qualifications, t.experience_years
    FROM users u JOIN tutors t ON u.id = t.id
    WHERE u.role = 'tutor' AND u.status = 'pending' ORDER BY u.created_at DESC");

$stats = [
    'students' => (int) $conn->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'],
    'tutors'   => (int) $conn->query("SELECT COUNT(*) c FROM users WHERE role='tutor' AND status='active'")->fetch_assoc()['c'],
    'pending'  => (int) $conn->query("SELECT COUNT(*) c FROM users WHERE role='tutor' AND status='pending'")->fetch_assoc()['c'],
    'bookings' => (int) $conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'],
];

$page_title = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Control Center</p>
    <h1 class="page-title">Admin <em>Dashboard</em></h1>
    <hr class="hairline">
</header>

<div class="card-grid stats-grid mb-3">
    <div class="card stat-card"><p class="stat-label">Students</p><p class="stat-value"><?php echo $stats['students']; ?></p></div>
    <div class="card stat-card"><p class="stat-label">Active Tutors</p><p class="stat-value"><?php echo $stats['tutors']; ?></p></div>
    <div class="card stat-card"><p class="stat-label">Pending</p><p class="stat-value"><?php echo $stats['pending']; ?></p></div>
    <div class="card stat-card"><p class="stat-label">Bookings</p><p class="stat-value"><?php echo $stats['bookings']; ?></p></div>
</div>

<div class="flex-between mb-2">
    <h2 class="section-title">Pending Tutor Applications</h2>
    <a href="<?php echo page_url('admin/categories.php'); ?>" class="btn btn-sm">Manage Categories</a>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Subject</th><th>Experience</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php if ($pending && $pending->num_rows): ?>
                <?php while ($row = $pending->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td><?php echo (int)$row['experience_years']; ?> yrs</td>
                        <td class="action-links">
                            <a href="<?php echo page_url('admin/review.php?id=' . (int)$row['id']); ?>" class="btn btn-sm">Review</a>
                            <a href="<?php echo page_url('admin/actions.php?action=approve&id=' . (int)$row['id']); ?>" class="btn btn-sm btn-success">Approve</a>
                            <a href="<?php echo page_url('admin/actions.php?action=reject&id=' . (int)$row['id']); ?>" class="btn btn-sm btn-danger">Reject</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="5">No pending applications.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
