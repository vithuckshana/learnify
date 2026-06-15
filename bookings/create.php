<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('student');

$tutor_id = isset($_GET['tutor_id']) ? (int) $_GET['tutor_id'] : 0;
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) $_SESSION['user_id'];
    $tutor_id = (int) ($_POST['tutor_id'] ?? 0);
    $date = $conn->real_escape_string($_POST['date'] ?? '');
    $time = $conn->real_escape_string($_POST['booking_time'] ?? '09:00');
    $notes = $conn->real_escape_string(trim($_POST['notes'] ?? ''));

    $sql = "INSERT INTO bookings (student_id, tutor_id, booking_date, booking_time, notes, status)
            VALUES ($student_id, $tutor_id, '$date', '$time', '$notes', 'pending')";

    if ($conn->query($sql)) {
        header('Location: ' . page_url('student/calendar.php'));
        exit;
    }
    $message = 'Could not create booking: ' . $conn->error;
    $message_type = 'error';
}

$tutor = null;
$timeSlots = [];
if ($tutor_id) {
    $res = $conn->query("SELECT t.*, u.name FROM tutors t JOIN users u ON t.id = u.id WHERE t.id = $tutor_id AND u.status = 'active'");
    $tutor = $res ? $res->fetch_assoc() : null;
    $avail = $conn->query("SELECT * FROM tutor_availability WHERE tutor_id = $tutor_id");
    while ($avail && $a = $avail->fetch_assoc()) {
        $start = strtotime($a['start_time']);
        $end = strtotime($a['end_time']);
        for ($t = $start; $t < $end; $t += 3600) {
            $timeSlots[] = date('H:i', $t);
        }
    }
    $timeSlots = array_unique($timeSlots);
    sort($timeSlots);
    if (!$timeSlots) {
        for ($h = 9; $h <= 17; $h++) {
            $timeSlots[] = sprintf('%02d:00', $h);
        }
    }
}

$page_title = 'Book Tutor';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Reservation</p>
    <h1 class="page-title">Book a <em>Session</em></h1>
    <hr class="hairline">
</header>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$tutor): ?>
    <div class="card"><p class="page-subtitle"><a href="<?php echo page_url('tutor/list.php'); ?>">Select a tutor</a> first.</p></div>
<?php else: ?>
    <div class="card booking-card">
        <div class="tutor-card-top mb-2">
            <img src="<?php echo upload_url($tutor['profile_image'] ?? null); ?>" class="tutor-avatar" alt="">
            <div>
                <h2 class="tutor-name"><?php echo htmlspecialchars($tutor['name']); ?></h2>
                <p class="stat-label"><?php echo htmlspecialchars($tutor['subject']); ?> · $<?php echo number_format((float)$tutor['hourly_rate'], 2); ?>/hr</p>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
            <div class="form-group">
                <label>Date</label>
                <input class="form-control" type="date" name="date" id="booking-date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Time</label>
                <div class="time-slots" id="time-slots">
                    <?php foreach ($timeSlots as $slot): ?>
                        <label class="time-slot"><input type="radio" name="booking_time" value="<?php echo $slot; ?>" <?php echo $slot === '09:00' ? 'checked' : ''; ?>><span><?php echo $slot; ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Topics to cover..."></textarea>
            </div>
            <div class="form-actions" style="flex-direction:row;gap:1rem">
                <button type="submit" class="btn">Confirm Booking</button>
                <a class="btn btn-ghost btn-sm" href="<?php echo page_url('tutor/view.php?id=' . $tutor_id); ?>">View Profile</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
