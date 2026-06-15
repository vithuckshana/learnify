<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('student');

$userId = (int) $_SESSION['user_id'];
if (!is_questionnaire_complete($conn, $userId)) {
    header('Location: ' . page_url('student/questionnaire.php'));
    exit;
}

$sql = "SELECT b.id, t.subject, t.hourly_rate, b.booking_date, b.booking_time, b.status, u.name AS tutor_name
    FROM bookings b
    JOIN tutors t ON b.tutor_id = t.id
    JOIN users u ON u.id = t.id
    WHERE b.student_id = $userId
    ORDER BY b.booking_date DESC, b.booking_time DESC";
$result = $conn->query($sql);

$page_title = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Student Portal</p>
    <h1 class="page-title">Hello, <em><?php echo htmlspecialchars($_SESSION['name']); ?></em></h1>
    <p class="page-subtitle">Track bookings, view suggestions, manage your schedule.</p>
    <hr class="hairline">
</header>

<div class="quick-actions mb-3">
    <a href="<?php echo page_url('student/suggestions.php'); ?>" class="quick-action-card">
        <span class="stat-label">For You</span>
        <strong>Suggested Tutors</strong>
    </a>
    <a href="<?php echo page_url('tutor/list.php'); ?>" class="quick-action-card">
        <span class="stat-label">Browse</span>
        <strong>All Tutors</strong>
    </a>
    <a href="<?php echo page_url('student/calendar.php'); ?>" class="quick-action-card">
        <span class="stat-label">Schedule</span>
        <strong>My Calendar</strong>
    </a>
    <a href="<?php echo page_url('student/questionnaire.php'); ?>" class="quick-action-card">
        <span class="stat-label">Profile</span>
        <strong>Edit Preferences</strong>
    </a>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr><th>Tutor</th><th>Subject</th><th>Date</th><th>Time</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php $badge = $row['status'] === 'accepted' ? 'badge-accepted' : ($row['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending'); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td><?php echo $row['booking_time'] ? substr($row['booking_time'], 0, 5) : '—'; ?></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="5">No bookings yet. <a href="<?php echo page_url('student/suggestions.php'); ?>">Get matched</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
