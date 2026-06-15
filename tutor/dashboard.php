<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('tutor');

$tutor_id = (int) $_SESSION['user_id'];

$sql = "SELECT b.id, u.name AS student_name, b.booking_date, b.booking_time, b.status, b.notes
    FROM bookings b JOIN users u ON b.student_id = u.id
    WHERE b.tutor_id = $tutor_id ORDER BY b.booking_date DESC, b.booking_time DESC";
$result = $conn->query($sql);

$page_title = 'Tutor Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Tutor Portal</p>
    <h1 class="page-title">Welcome, <em><?php echo htmlspecialchars($_SESSION['name']); ?></em></h1>
    <p class="page-subtitle">Manage bookings, profile, and availability.</p>
    <hr class="hairline">
</header>

<div class="quick-actions mb-3">
    <a href="<?php echo page_url('tutor/profile.php'); ?>" class="quick-action-card"><span class="stat-label">Edit</span><strong>My Profile</strong></a>
    <a href="<?php echo page_url('tutor/calendar.php'); ?>" class="quick-action-card"><span class="stat-label">Schedule</span><strong>Calendar</strong></a>
    <a href="<?php echo page_url('tutor/view.php?id=' . $tutor_id); ?>" class="quick-action-card"><span class="stat-label">Public</span><strong>View Profile</strong></a>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Student</th><th>Date</th><th>Time</th><th>Notes</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            <?php if ($result && $result->num_rows): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php $badge = $row['status'] === 'accepted' ? 'badge-accepted' : ($row['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending'); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td><?php echo $row['booking_time'] ? substr($row['booking_time'], 0, 5) : '—'; ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($row['notes'] ?? '', 0, 40, '…')); ?></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <div class="action-links">
                                    <a class="btn btn-sm btn-success" href="update_booking.php?id=<?php echo (int)$row['id']; ?>&status=accepted">Accept</a>
                                    <a class="btn btn-sm btn-danger" href="update_booking.php?id=<?php echo (int)$row['id']; ?>&status=rejected">Reject</a>
                                </div>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="6">No booking requests yet. Complete your <a href="<?php echo page_url('tutor/profile.php'); ?>">profile</a> and <a href="<?php echo page_url('tutor/calendar.php'); ?>">availability</a>.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
