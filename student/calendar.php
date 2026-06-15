<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('student');

$userId = (int) $_SESSION['user_id'];
$bookings = $conn->query("SELECT b.*, u.name AS tutor_name, t.subject FROM bookings b
    JOIN users u ON u.id = b.tutor_id JOIN tutors t ON t.id = b.tutor_id
    WHERE b.student_id = $userId ORDER BY b.booking_date DESC, b.booking_time DESC");

$rows = [];
$events = [];
if ($bookings) {
    while ($b = $bookings->fetch_assoc()) {
        $rows[] = $b;
        $time = $b['booking_time'] ? substr($b['booking_time'], 0, 5) : '09:00';
        $events[] = [
            'date' => $b['booking_date'],
            'time' => $time,
            'title' => $b['tutor_name'] . ' — ' . $b['subject'] . ' (' . $b['status'] . ')',
            'type' => 'booking',
        ];
    }
}

$page_title = 'My Calendar';
$extra_js = ['assets/js/calendar.js'];
$calendar_events = json_encode($events);
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Schedule</p>
    <h1 class="page-title">My <em>Calendar</em></h1>
    <p class="page-subtitle">All your upcoming and past sessions.</p>
    <hr class="hairline">
</header>

<div class="card">
    <div id="calendar" class="calendar-widget" data-events='<?php echo htmlspecialchars($calendar_events, ENT_QUOTES); ?>'></div>
</div>

<div class="table-wrap mt-3">
    <table class="data-table">
        <thead><tr><th>Date</th><th>Time</th><th>Tutor</th><th>Subject</th><th>Status</th></tr></thead>
        <tbody>
            <?php if ($rows): foreach ($rows as $b):
                $badge = $b['status'] === 'accepted' ? 'badge-accepted' : ($b['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending');
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                    <td><?php echo $b['booking_time'] ? substr($b['booking_time'], 0, 5) : '—'; ?></td>
                    <td><?php echo htmlspecialchars($b['tutor_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['subject']); ?></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                </tr>
            <?php endforeach; else: ?>
                <tr class="empty-row"><td colspan="5">No sessions yet. <a href="<?php echo page_url('student/suggestions.php'); ?>">Find a tutor</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
