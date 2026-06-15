<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('admin');

$id = (int) ($_GET['id'] ?? 0);
$res = $conn->query("SELECT u.*, t.* FROM users u JOIN tutors t ON u.id = t.id WHERE u.id = $id AND u.role = 'tutor'");
if (!$res || !$res->num_rows) {
    header('Location: ' . page_url('admin/dashboard.php'));
    exit;
}
$tutor = $res->fetch_assoc();

$cats = $conn->query("SELECT c.name FROM tutor_categories tc JOIN categories c ON c.id = tc.category_id WHERE tc.tutor_id = $id");
$catNames = [];
while ($cats && $r = $cats->fetch_assoc()) {
    $catNames[] = $r['name'];
}

$page_title = 'Review Tutor';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Application Review</p>
    <h1 class="page-title"><?php echo htmlspecialchars($tutor['name']); ?></h1>
    <hr class="hairline">
</header>

<div class="profile-layout">
    <div class="card">
        <img src="<?php echo upload_url($tutor['profile_image']); ?>" alt="" class="profile-hero-img">
        <p class="stat-label mt-2">Status</p>
        <span class="badge badge-pending"><?php echo ucfirst($tutor['status']); ?></span>
        <p class="mt-2"><strong>Subject:</strong> <?php echo htmlspecialchars($tutor['subject']); ?></p>
        <p><strong>Categories:</strong> <?php echo htmlspecialchars(implode(', ', $catNames) ?: '—'); ?></p>
        <p><strong>Levels:</strong> <?php echo htmlspecialchars($tutor['teaches_levels']); ?></p>
        <p><strong>Rate:</strong> $<?php echo number_format((float)$tutor['hourly_rate'], 2); ?>/hr</p>
        <p><strong>Experience:</strong> <?php echo (int)$tutor['experience_years']; ?> years</p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($tutor['phone'] ?: '—'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($tutor['contact_email'] ?: $tutor['email']); ?></p>
        <?php if ($tutor['portfolio_url']): ?>
            <p><a href="<?php echo htmlspecialchars($tutor['portfolio_url']); ?>" target="_blank" rel="noopener">Portfolio</a></p>
        <?php endif; ?>
        <h3 class="card-title mt-2">Qualifications</h3>
        <p class="page-subtitle"><?php echo nl2br(htmlspecialchars($tutor['qualifications'] ?: '—')); ?></p>
        <h3 class="card-title mt-2">Bio</h3>
        <p class="page-subtitle"><?php echo nl2br(htmlspecialchars($tutor['bio'] ?: '—')); ?></p>
        <h3 class="card-title mt-2">Course Details</h3>
        <p class="page-subtitle"><?php echo nl2br(htmlspecialchars($tutor['class_details'] ?: '—')); ?></p>
        <div class="action-links mt-3">
            <a href="<?php echo page_url('admin/actions.php?action=approve&id=' . $id); ?>" class="btn btn-success">Approve</a>
            <a href="<?php echo page_url('admin/actions.php?action=reject&id=' . $id); ?>" class="btn btn-danger">Reject</a>
            <a href="<?php echo page_url('admin/dashboard.php'); ?>" class="btn btn-ghost">Back</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
