<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('tutor');

$userId = (int) $_SESSION['user_id'];
$days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $dow = (int) ($_POST['day_of_week'] ?? 0);
    $start = $conn->real_escape_string($_POST['start_time'] ?? '09:00');
    $end = $conn->real_escape_string($_POST['end_time'] ?? '17:00');
    $conn->query("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES ($userId, $dow, '$start', '$end')");
    header('Location: ' . page_url('tutor/calendar.php'));
    exit;
}
if (isset($_GET['delete'])) {
    $del = (int) $_GET['delete'];
    $conn->query("DELETE FROM tutor_availability WHERE id = $del AND tutor_id = $userId");
    header('Location: ' . page_url('tutor/calendar.php'));
    exit;
}

$slots = $conn->query("SELECT * FROM tutor_availability WHERE tutor_id = $userId ORDER BY day_of_week, start_time");
$bookings = $conn->query("SELECT b.*, u.name AS student_name FROM bookings b JOIN users u ON u.id = b.student_id WHERE b.tutor_id = $userId ORDER BY b.booking_date, b.booking_time");

$events = [];
while ($bookings && $b = $bookings->fetch_assoc()) {
    $time = $b['booking_time'] ? substr($b['booking_time'], 0, 5) : '09:00';
    $events[] = [
        'date' => $b['booking_date'],
        'time' => $time,
        'title' => $b['student_name'] . ' (' . $b['status'] . ')',
        'type' => 'booking',
    ];
}

$page_title = 'Calendar';
$extra_js = ['assets/js/calendar.js'];
$calendar_events = json_encode($events);
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Schedule</p>
    <h1 class="page-title">Your <em>Calendar</em></h1>
    <hr class="hairline">
</header>

<div class="grid-2">
    <div class="card">
        <h2 class="card-title">Weekly Availability</h2>
        <form method="POST" class="mb-2">
            <input type="hidden" name="add_slot" value="1">
            <div class="form-group">
                <label>Day</label>
                <select class="form-control" name="day_of_week">
                    <?php foreach ($days as $i => $d): ?><option value="<?php echo $i; ?>"><?php echo $d; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label>From</label><input class="form-control" type="time" name="start_time" value="09:00" required></div>
                <div class="form-group"><label>To</label><input class="form-control" type="time" name="end_time" value="17:00" required></div>
            </div>
            <button class="btn btn-sm" type="submit">Add Slot</button>
        </form>
        <ul class="avail-list">
            <?php if ($slots && $slots->num_rows): while ($s = $slots->fetch_assoc()): ?>
                <li class="flex-between">
                    <span><?php echo $days[(int)$s['day_of_week']]; ?>: <?php echo substr($s['start_time'],0,5); ?>–<?php echo substr($s['end_time'],0,5); ?></span>
                    <a href="?delete=<?php echo (int)$s['id']; ?>" class="btn btn-sm btn-danger">Remove</a>
                </li>
            <?php endwhile; else: ?>
                <li>No availability set.</li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="card">
        <div id="calendar" class="calendar-widget" data-events='<?php echo htmlspecialchars($calendar_events, ENT_QUOTES); ?>'></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
