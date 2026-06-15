<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('student');

$userId = (int) $_SESSION['user_id'];
$studentCats = get_student_category_ids($conn, $userId);
if (!is_questionnaire_complete($conn, $userId)) {
    header('Location: ' . page_url('student/questionnaire.php'));
    exit;
}

$tutors = get_suggested_tutors($conn, $userId);
$page_title = 'Suggested Tutors';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Matched For You</p>
    <h1 class="page-title">Suggested <em>Tutors</em></h1>
    <p class="page-subtitle">Based on your subjects, level, and learning goals.</p>
    <hr class="hairline">
</header>

<div class="flex-between mb-2">
    <a href="<?php echo page_url('student/questionnaire.php'); ?>" class="btn btn-sm btn-ghost">Edit Profile</a>
    <a href="<?php echo page_url('tutor/list.php'); ?>" class="btn btn-sm">Browse All</a>
</div>

<div class="card-grid">
    <?php if ($tutors): ?>
        <?php foreach ($tutors as $t): ?>
            <article class="card tutor-card" data-match="<?php echo (int) $t['match_score']; ?>">
                <div class="tutor-card-top">
                    <img src="<?php echo upload_url($t['profile_image'] ?? null); ?>" alt="" class="tutor-avatar">
                    <div>
                        <?php
                        $pct = min(98, max(52, (int) round(($t['match_score'] / max(1, count($studentCats) * 40 + 55)) * 100)));
                        ?>
                        <span class="match-badge"><?php echo $pct; ?>% match</span>
                        <h2 class="tutor-name"><?php echo htmlspecialchars($t['tutor_name']); ?></h2>
                        <p class="stat-label"><?php echo htmlspecialchars($t['categories'] ?: $t['subject']); ?></p>
                    </div>
                </div>
                <p class="page-subtitle mt-2" style="font-size:0.88rem"><?php echo htmlspecialchars(mb_strimwidth($t['bio'] ?? '', 0, 120, '…')); ?></p>
                <p class="tutor-meta">$<?php echo number_format((float)$t['hourly_rate'], 2); ?>/hr · <?php echo (int)$t['experience_years']; ?> yrs exp</p>
                <div class="card-actions">
                    <a class="btn btn-sm btn-ghost" href="<?php echo page_url('tutor/view.php?id=' . (int)$t['id']); ?>">View Profile</a>
                    <a class="btn btn-sm" href="<?php echo page_url('bookings/create.php?tutor_id=' . (int)$t['id']); ?>">Book</a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <p class="page-subtitle">No matching tutors yet. <a href="<?php echo page_url('tutor/list.php'); ?>">Browse all tutors</a> or update your <a href="<?php echo page_url('student/questionnaire.php'); ?>">profile</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
