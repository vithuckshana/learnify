<?php
require_once __DIR__ . '/../config/db.php';

$sql = "SELECT 
            bookings.id,
            users.name AS student_name,
            tutors.subject,
            bookings.booking_date,
            bookings.status
        FROM bookings
        JOIN users ON bookings.student_id = users.id
        JOIN tutors ON bookings.tutor_id = tutors.id
        ORDER BY bookings.id DESC";

$result = $conn->query($sql);

$page_title = 'All Bookings';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Admin View</p>
    <h1 class="page-title">All <em>Bookings</em></h1>
    <hr class="hairline">
</header>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td>
                            <?php
                            $status = $row['status'];
                            $badge = $status === 'accepted' ? 'badge-accepted' : ($status === 'rejected' ? 'badge-rejected' : 'badge-pending');
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="5">No bookings in the system.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
