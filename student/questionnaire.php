<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('student');

$userId = (int) $_SESSION['user_id'];
$categories = get_categories($conn);
$eduLevels = education_levels();
$modes = preferred_modes();
$profile = get_student_profile($conn, $userId);
$selectedCats = get_student_category_ids($conn, $userId);
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $about = $conn->real_escape_string(trim($_POST['about_me'] ?? ''));
    $edu = $conn->real_escape_string($_POST['education_level'] ?? 'other');
    $goals = $conn->real_escape_string(trim($_POST['learning_goals'] ?? ''));
    $mode = $conn->real_escape_string($_POST['preferred_mode'] ?? 'both');
    $catIds = array_map('intval', $_POST['categories'] ?? []);

    if (strlen($about) < 10) {
        $message = 'Tell us a bit more about yourself (min 10 characters).';
        $message_type = 'error';
    } elseif (!$catIds) {
        $message = 'Select at least one subject category.';
        $message_type = 'error';
    } else {
        $conn->query("INSERT INTO student_profiles (user_id, about_me, education_level, learning_goals, preferred_mode, questionnaire_completed)
                      VALUES ($userId, '$about', '$edu', '$goals', '$mode', 1)
                      ON DUPLICATE KEY UPDATE about_me='$about', education_level='$edu', learning_goals='$goals',
                      preferred_mode='$mode', questionnaire_completed=1");
        $conn->query("DELETE FROM student_categories WHERE student_id = $userId");
        foreach ($catIds as $cid) {
            if ($cid > 0) {
                $conn->query("INSERT INTO student_categories (student_id, category_id) VALUES ($userId, $cid)");
            }
        }
        header('Location: ' . page_url('student/suggestions.php'));
        exit;
    }
}

$page_title = 'Learning Profile';
$extra_js = ['assets/js/questionnaire.js'];
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Step <span id="step-indicator">1</span> of 4</p>
    <h1 class="page-title">Your <em>Learning Profile</em></h1>
    <p class="page-subtitle">Help us match you with the right tutors.</p>
    <div class="progress-track"><div class="progress-fill" id="progress-fill" style="width:25%"></div></div>
    <hr class="hairline">
</header>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form method="POST" id="questionnaire-form" class="wizard-form">
    <div class="wizard-step is-active" data-step="1">
        <div class="card">
            <h2 class="card-title">Who are you?</h2>
            <p class="page-subtitle mb-2">Introduce yourself so tutors understand your background.</p>
            <div class="form-group">
                <label for="about_me">About Me</label>
                <textarea class="form-control" id="about_me" name="about_me" rows="4" required placeholder="I'm a student who loves..."><?php echo htmlspecialchars($profile['about_me'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="wizard-step" data-step="2">
        <div class="card">
            <h2 class="card-title">Education Level</h2>
            <div class="option-grid">
                <?php foreach ($eduLevels as $key => $label): ?>
                    <label class="option-card">
                        <input type="radio" name="education_level" value="<?php echo $key; ?>" <?php echo ($profile['education_level'] ?? '') === $key ? 'checked' : ($key === 'undergraduate' && !$profile ? 'checked' : ''); ?>>
                        <span><?php echo htmlspecialchars($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="wizard-step" data-step="3">
        <div class="card">
            <h2 class="card-title">Subjects You're Looking For</h2>
            <div class="chip-grid">
                <?php foreach ($categories as $cat): ?>
                    <label class="chip-option">
                        <input type="checkbox" name="categories[]" value="<?php echo (int) $cat['id']; ?>" <?php echo in_array((int)$cat['id'], $selectedCats) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="wizard-step" data-step="4">
        <div class="card">
            <h2 class="card-title">Goals & Preferences</h2>
            <div class="form-group">
                <label for="learning_goals">Learning Goals</label>
                <textarea class="form-control" id="learning_goals" name="learning_goals" rows="3" placeholder="Exam prep, improve grades, learn a new skill..."><?php echo htmlspecialchars($profile['learning_goals'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Preferred Mode</label>
                <div class="option-grid option-grid-3">
                    <?php foreach ($modes as $key => $label): ?>
                        <label class="option-card">
                            <input type="radio" name="preferred_mode" value="<?php echo $key; ?>" <?php echo ($profile['preferred_mode'] ?? 'both') === $key ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-nav flex-between mt-3">
        <button type="button" class="btn btn-sm btn-ghost" id="wizard-prev" disabled>Back</button>
        <button type="button" class="btn btn-sm" id="wizard-next">Continue</button>
        <button type="submit" class="btn btn-sm btn-submit" id="wizard-submit" style="display:none">
            <span class="btn-text">Find My Tutors</span>
            <span class="btn-spinner"></span>
        </button>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
