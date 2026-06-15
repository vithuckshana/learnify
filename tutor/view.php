<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$res = $conn->query("SELECT t.*, u.name AS tutor_name, u.email FROM tutors t JOIN users u ON t.id = u.id WHERE t.id = $id AND u.role = 'tutor' AND u.status = 'active'");
if (!$res || !$res->num_rows) {
    header('Location: ' . page_url('tutor/list.php'));
    exit;
}
$tutor = $res->fetch_assoc();

$cats = $conn->query("SELECT c.name FROM tutor_categories tc JOIN categories c ON c.id = tc.category_id WHERE tc.tutor_id = $id");
$catNames = [];
while ($cats && $r = $cats->fetch_assoc()) {
    $catNames[] = $r['name'];
}

$courses = $conn->query("SELECT * FROM tutor_courses WHERE tutor_id = $id AND is_active = 1");
$avail = $conn->query("SELECT * FROM tutor_availability WHERE tutor_id = $id ORDER BY day_of_week, start_time");
$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

$page_title = $tutor['tutor_name'];
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header profile-header">
    <img src="<?php echo upload_url($tutor['profile_image']); ?>" alt="" class="profile-hero-img profile-hero-img-lg">
    <div>
        <p class="page-eyebrow"><?php echo htmlspecialchars($tutor['subject']); ?></p>
        <h1 class="page-title"><?php echo htmlspecialchars($tutor['tutor_name']); ?></h1>
        <p class="page-subtitle"><?php echo htmlspecialchars(implode(' · ', $catNames)); ?></p>
        <p class="tutor-meta mt-1">$<?php echo number_format((float)$tutor['hourly_rate'], 2); ?>/hr · <?php echo (int)$tutor['experience_years']; ?> yrs experience</p>
        <div class="action-links mt-2">
            <a href="<?php echo page_url('bookings/create.php?tutor_id=' . $id); ?>" class="btn">Book Session</a>
            <?php if ($tutor['portfolio_url']): ?><a href="<?php echo htmlspecialchars($tutor['portfolio_url']); ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">Portfolio</a><?php endif; ?>
        </div>
    </div>
</header>

<div class="grid-2">
    <div class="card">
        <h2 class="card-title">About</h2>
        <p class="page-subtitle"><?php echo nl2br(htmlspecialchars($tutor['bio'] ?: 'No bio provided.')); ?></p>
        <h3 class="card-title mt-2">Qualifications</h3>
        <p class="page-subtitle"><?php echo nl2br(htmlspecialchars($tutor['qualifications'] ?: '—')); ?></p>
        <h3 class="card-title mt-2">Contact</h3>
        <p class="page-subtitle">
            <?php if ($tutor['phone']): ?>Phone: <?php echo htmlspecialchars($tutor['phone']); ?><br><?php endif; ?>
            Email: <?php echo htmlspecialchars($tutor['contact_email'] ?: $tutor['email']); ?>
        </p>
    </div>
    <div class="card">
        <h2 class="card-title">Courses & Classes</h2>
        <?php if ($courses && $courses->num_rows): ?>
            <?php while ($c = $courses->fetch_assoc()): ?>
                <article class="course-item mb-2">
                    <?php if ($c['image_path']): ?><img src="<?php echo upload_url($c['image_path']); ?>" class="course-thumb" alt=""><?php endif; ?>
                    <div>
                        <strong><?php echo htmlspecialchars($c['title']); ?></strong>
                        <p class="page-subtitle" style="font-size:0.85rem"><?php echo htmlspecialchars($c['description']); ?></p>
                        <?php if ($c['join_link']): ?><a href="<?php echo htmlspecialchars($c['join_link']); ?>" target="_blank" rel="noopener">Join →</a><?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="page-subtitle"><?php echo nl2br(htmlspecialchars($tutor['class_details'] ?: 'No courses listed yet.')); ?></p>
        <?php endif; ?>

        <h3 class="card-title mt-3">Availability</h3>
        <?php if ($avail && $avail->num_rows): ?>
            <ul class="avail-list">
                <?php while ($a = $avail->fetch_assoc()): ?>
                    <li><?php echo $days[(int)$a['day_of_week']]; ?>: <?php echo substr($a['start_time'], 0, 5); ?> – <?php echo substr($a['end_time'], 0, 5); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="page-subtitle">Contact tutor for schedule.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
