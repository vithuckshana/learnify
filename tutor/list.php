<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$categories = get_categories($conn);
$catFilter = (int) ($_GET['category'] ?? 0);
$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));

$where = "u.role = 'tutor' AND u.status = 'active'";
if ($catFilter) {
    $where .= " AND tc.category_id = $catFilter";
}
if ($search) {
    $where .= " AND (u.name LIKE '%$search%' OR t.subject LIKE '%$search%' OR t.bio LIKE '%$search%')";
}

$sql = "SELECT t.*, u.name AS tutor_name,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS categories
    FROM tutors t
    JOIN users u ON t.id = u.id
    LEFT JOIN tutor_categories tc ON tc.tutor_id = t.id
    LEFT JOIN categories c ON c.id = tc.category_id
    WHERE $where
    GROUP BY t.id
    ORDER BY t.experience_years DESC, t.hourly_rate ASC";

$result = $conn->query($sql);
$page_title = 'Find Tutors';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Directory</p>
    <h1 class="page-title">Find <em>Tutors</em></h1>
    <p class="page-subtitle">Filter by subject and book instantly.</p>
    <hr class="hairline">
</header>

<form class="filter-bar card mb-2" method="GET">
    <div class="filter-row">
        <input class="form-control" type="search" name="q" placeholder="Search tutors..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
        <select class="form-control" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php echo $catFilter === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm" type="submit">Filter</button>
    </div>
</form>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
    <p class="mb-2"><a href="<?php echo page_url('student/suggestions.php'); ?>">View personalized suggestions →</a></p>
<?php endif; ?>

<div class="card-grid">
    <?php if ($result && $result->num_rows): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <article class="card tutor-card tutor-card-interactive">
                <img src="<?php echo upload_url($row['profile_image'] ?? null); ?>" alt="" class="tutor-card-bg">
                <div class="tutor-card-body">
                    <p class="stat-label"><?php echo htmlspecialchars($row['categories'] ?: $row['subject']); ?></p>
                    <h2 class="tutor-name"><?php echo htmlspecialchars($row['tutor_name']); ?></h2>
                    <p class="page-subtitle mt-1" style="font-size:0.88rem"><?php echo htmlspecialchars(mb_strimwidth($row['bio'] ?? '', 0, 100, '…')); ?></p>
                    <p class="tutor-meta">$<?php echo number_format((float)$row['hourly_rate'], 2); ?>/hr · <?php echo (int)$row['experience_years']; ?> yrs</p>
                    <div class="card-actions">
                        <a class="btn btn-sm btn-ghost" href="<?php echo page_url('tutor/view.php?id=' . (int)$row['id']); ?>">Profile</a>
                        <a class="btn btn-sm" href="<?php echo page_url('bookings/create.php?tutor_id=' . (int)$row['id']); ?>">Book</a>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card"><p class="page-subtitle">No tutors found. Run <a href="<?php echo page_url('setup/migrate.php'); ?>">setup/migrate.php</a> first.</p></div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
