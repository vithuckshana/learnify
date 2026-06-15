<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('tutor');

$userId = (int) $_SESSION['user_id'];
ensure_tutor_profile($conn, $userId);
$categories = get_categories($conn);
$eduLevels = education_levels();
$tutor = get_tutor_row($conn, $userId);
$tutorCats = [];
$cr = $conn->query("SELECT category_id FROM tutor_categories WHERE tutor_id = $userId");
while ($cr && $r = $cr->fetch_assoc()) {
    $tutorCats[] = (int) $r['category_id'];
}
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $subject = $conn->real_escape_string($_POST['subject'] ?? '');
    $bio = $conn->real_escape_string($_POST['bio'] ?? '');
    $quals = $conn->real_escape_string($_POST['qualifications'] ?? '');
    $rate = floatval($_POST['hourly_rate'] ?? 0);
    $exp = (int) ($_POST['experience_years'] ?? 0);
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $contactEmail = $conn->real_escape_string(trim($_POST['contact_email'] ?? ''));
    $portfolio = $conn->real_escape_string(trim($_POST['portfolio_url'] ?? ''));
    $classDetails = $conn->real_escape_string(trim($_POST['class_details'] ?? ''));
    $levels = implode(',', array_map('strval', $_POST['teaches_levels'] ?? []));
    $levels = $conn->real_escape_string($levels);

    $imgUpdate = '';
    if (!empty($_FILES['profile_image']['name'])) {
        $path = upload_image($_FILES['profile_image'], 'tutors');
        if ($path) {
            $imgUpdate = ", profile_image = '" . $conn->real_escape_string($path) . "'";
        }
    }

    $conn->query("UPDATE tutors SET subject='$subject', bio='$bio', qualifications='$quals', hourly_rate=$rate,
        experience_years=$exp, phone='$phone', contact_email='$contactEmail', portfolio_url='$portfolio',
        teaches_levels='$levels', class_details='$classDetails' $imgUpdate WHERE id = $userId");

    $conn->query("DELETE FROM tutor_categories WHERE tutor_id = $userId");
    foreach (array_map('intval', $_POST['categories'] ?? []) as $cid) {
        if ($cid > 0) {
            $conn->query("INSERT INTO tutor_categories (tutor_id, category_id) VALUES ($userId, $cid)");
        }
    }
    $message = 'Profile updated.';
    $message_type = 'success';
    $res = $conn->query("SELECT * FROM tutors WHERE id = $userId");
    $tutor = $res->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $title = $conn->real_escape_string(trim($_POST['course_title'] ?? ''));
    $level = $conn->real_escape_string(trim($_POST['course_level'] ?? ''));
    $desc = $conn->real_escape_string(trim($_POST['course_desc'] ?? ''));
    $link = $conn->real_escape_string(trim($_POST['join_link'] ?? ''));
    $cPhone = $conn->real_escape_string(trim($_POST['course_phone'] ?? ''));
    $cEmail = $conn->real_escape_string(trim($_POST['course_email'] ?? ''));
    $imgPath = null;
    if (!empty($_FILES['course_image']['name'])) {
        $imgPath = upload_image($_FILES['course_image'], 'courses');
    }
    $imgSql = $imgPath ? "'" . $conn->real_escape_string($imgPath) . "'" : 'NULL';
    if ($title) {
        $conn->query("INSERT INTO tutor_courses (tutor_id, title, class_level, description, image_path, join_link, contact_phone, contact_email)
                      VALUES ($userId, '$title', '$level', '$desc', $imgSql, '$link', '$cPhone', '$cEmail')");
        $message = 'Course added.';
        $message_type = 'success';
    }
}

$courses = $conn->query("SELECT * FROM tutor_courses WHERE tutor_id = $userId ORDER BY id DESC");
$page_title = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Tutor Profile</p>
    <h1 class="page-title">Manage <em>Profile</em></h1>
    <hr class="hairline">
</header>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <h2 class="card-title">Profile Details</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_profile" value="1">
            <div class="form-group"><label>Photo</label><input type="file" class="form-control form-control-file" name="profile_image" accept="image/*"></div>
            <div class="form-group"><label>Subject</label><input class="form-control" name="subject" value="<?php echo htmlspecialchars($tutor['subject'] ?? ''); ?>" required></div>
            <div class="form-group"><label>Categories</label>
                <div class="chip-grid">
                    <?php foreach ($categories as $cat): ?>
                        <label class="chip-option"><input type="checkbox" name="categories[]" value="<?php echo (int)$cat['id']; ?>" <?php echo in_array((int)$cat['id'], $tutorCats) ? 'checked' : ''; ?>><span><?php echo htmlspecialchars($cat['name']); ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group"><label>Levels</label>
                <div class="chip-grid">
                    <?php $tl = explode(',', $tutor['teaches_levels'] ?? ''); foreach ($eduLevels as $k => $l): ?>
                        <label class="chip-option"><input type="checkbox" name="teaches_levels[]" value="<?php echo $k; ?>" <?php echo in_array($k, $tl) ? 'checked' : ''; ?>><span><?php echo $l; ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group"><label>Qualifications</label><textarea class="form-control" name="qualifications" rows="3"><?php echo htmlspecialchars($tutor['qualifications'] ?? ''); ?></textarea></div>
            <div class="form-group"><label>Bio</label><textarea class="form-control" name="bio" rows="2"><?php echo htmlspecialchars($tutor['bio'] ?? ''); ?></textarea></div>
            <div class="form-group"><label>Course & Class Details</label><textarea class="form-control" name="class_details" rows="2"><?php echo htmlspecialchars($tutor['class_details'] ?? ''); ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Rate ($)</label><input class="form-control" type="number" step="0.01" name="hourly_rate" value="<?php echo htmlspecialchars($tutor['hourly_rate'] ?? ''); ?>"></div>
                <div class="form-group"><label>Experience</label><input class="form-control" type="number" name="experience_years" value="<?php echo (int)($tutor['experience_years'] ?? 0); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($tutor['phone'] ?? ''); ?>"></div>
                <div class="form-group"><label>Contact Email</label><input class="form-control" name="contact_email" value="<?php echo htmlspecialchars($tutor['contact_email'] ?? ''); ?>"></div>
            </div>
            <div class="form-group"><label>Portfolio URL</label><input class="form-control" name="portfolio_url" value="<?php echo htmlspecialchars($tutor['portfolio_url'] ?? ''); ?>"></div>
            <button class="btn btn-sm" type="submit">Save Profile</button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">Add Course / Class</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_course" value="1">
            <div class="form-group"><label>Title</label><input class="form-control" name="course_title" required></div>
            <div class="form-group"><label>Class Level</label><input class="form-control" name="course_level" placeholder="e.g. Grade 11, Beginner"></div>
            <div class="form-group"><label>Description</label><textarea class="form-control" name="course_desc" rows="2"></textarea></div>
            <div class="form-group"><label>Join Link</label><input class="form-control" name="join_link" type="url" placeholder="Zoom, Meet, etc."></div>
            <div class="form-row">
                <div class="form-group"><label>Phone</label><input class="form-control" name="course_phone"></div>
                <div class="form-group"><label>Email</label><input class="form-control" name="course_email" type="email"></div>
            </div>
            <div class="form-group"><label>Course Image</label><input class="form-control form-control-file" name="course_image" type="file" accept="image/*"></div>
            <button class="btn btn-sm" type="submit">Add Course</button>
        </form>

        <h3 class="card-title mt-3">Your Courses</h3>
        <?php if ($courses && $courses->num_rows): ?>
            <div class="course-list">
                <?php while ($c = $courses->fetch_assoc()): ?>
                    <article class="course-item">
                        <?php if ($c['image_path']): ?><img src="<?php echo upload_url($c['image_path']); ?>" alt="" class="course-thumb"><?php endif; ?>
                        <div>
                            <strong><?php echo htmlspecialchars($c['title']); ?></strong>
                            <p class="page-subtitle" style="font-size:0.85rem"><?php echo htmlspecialchars($c['class_level']); ?></p>
                            <?php if ($c['join_link']): ?><a href="<?php echo htmlspecialchars($c['join_link']); ?>" target="_blank" rel="noopener">Join class</a><?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="page-subtitle">No courses yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
