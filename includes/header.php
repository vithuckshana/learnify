<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

$is_hero = $is_hero ?? false;
$is_auth = $is_auth ?? false;
$page_title = $page_title ?? 'Learnify';
$extra_js = $extra_js ?? [];
$body_class = trim(
    ($is_hero || $is_auth ? 'corner-marks' : 'corner-marks app-body')
    . ($is_auth ? ' page-auth' : '')
    . ' ' . ($body_class ?? '')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — Learnify</title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/features.css'); ?>">
    <script defer src="<?php echo asset_url('assets/js/main.js'); ?>"></script>
    <?php if (!empty($is_auth)): ?>
    <script defer src="<?php echo asset_url('assets/js/auth.js'); ?>"></script>
    <?php endif; ?>
    <?php foreach ($extra_js as $js): ?>
    <script defer src="<?php echo asset_url($js); ?>"></script>
    <?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($body_class); ?>">

<?php if (!$is_hero && !$is_auth): ?>
<div class="ambient-video" data-video-bg="https://res.cloudinary.com/dfonotyfb/video/upload/v1775585556/dds3_1_rqhg7x.mp4"></div>
<div class="ambient-overlay"></div>
<?php endif; ?>

<?php if (!$is_auth): ?>
<header class="navbar<?php echo $is_hero ? ' navbar-hero' : ''; ?>">
    <a href="<?php echo page_url('index.php'); ?>" class="navbar-brand">Learn<em>ify</em></a>
    <button class="nav-toggle" id="nav-toggle" aria-label="Menu"><span></span></button>
    <nav class="navbar-links" id="navbar-links">
        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'student'): ?>
                <a href="<?php echo page_url('student/dashboard.php'); ?>">Dashboard</a>
                <a href="<?php echo page_url('student/suggestions.php'); ?>">Suggestions</a>
                <a href="<?php echo page_url('tutor/list.php'); ?>">Tutors</a>
                <a href="<?php echo page_url('student/calendar.php'); ?>">Calendar</a>
            <?php elseif ($_SESSION['role'] === 'tutor'): ?>
                <a href="<?php echo page_url('tutor/dashboard.php'); ?>">Dashboard</a>
                <a href="<?php echo page_url('tutor/profile.php'); ?>">Profile</a>
                <a href="<?php echo page_url('tutor/calendar.php'); ?>">Calendar</a>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="<?php echo page_url('admin/dashboard.php'); ?>">Admin</a>
                <a href="<?php echo page_url('admin/categories.php'); ?>">Categories</a>
            <?php endif; ?>
            <span class="navbar-user hide-mobile"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span>
            <a href="<?php echo page_url('auth/logout.php'); ?>" class="btn btn-sm btn-ghost">Logout</a>
        <?php else: ?>
            <a href="<?php echo page_url('auth/login.php'); ?>">Login</a>
            <a href="<?php echo page_url('auth/register.php'); ?>" class="btn btn-sm">Register</a>
        <?php endif; ?>
    </nav>
</header>
<?php endif; ?>

<?php if ($is_hero || $is_auth): ?>
<?php else: ?>
<main class="app-main">
<div class="container">
<?php endif; ?>
