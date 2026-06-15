<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

$page_title = 'Register';
$is_auth = true;
$message = '';
$message_type = '';
$categories = get_categories($conn);
$eduLevels = education_levels();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plainPassword = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $role = in_array($role, ['student', 'tutor'], true) ? $role : 'student';

    if (strlen($name) < 2) {
        $message = 'Please enter your full name.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } elseif (strlen($plainPassword) < 8) {
        $message = 'Password must be at least 8 characters.';
        $message_type = 'error';
    } elseif ($role === 'tutor') {
        $subject = trim($_POST['subject'] ?? '');
        $quals = trim($_POST['qualifications'] ?? '');
        $catIds = array_filter(array_map('intval', $_POST['categories'] ?? []));
        if ($subject === '') {
            $message = 'Primary subject is required for tutors.';
            $message_type = 'error';
        } elseif ($quals === '') {
            $message = 'Qualifications are required for tutors.';
            $message_type = 'error';
        } elseif (!$catIds) {
            $message = 'Select at least one category.';
            $message_type = 'error';
        }
    }

    if ($message === '') {
        $safeName = $conn->real_escape_string($name);
        $safeEmail = $conn->real_escape_string($email);
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);
        $status = $role === 'tutor' ? 'pending' : 'active';

        $check = $conn->query("SELECT id FROM users WHERE email = '$safeEmail'");
        if ($check && $check->num_rows > 0) {
            $message = 'That email is already registered.';
            $message_type = 'error';
        } else {
            $conn->begin_transaction();
            $ok = true;
            $newId = 0;

            if (!$conn->query("INSERT INTO users (name, email, password, role, status)
                               VALUES ('$safeName', '$safeEmail', '$password', '$role', '$status')")) {
                $ok = false;
                $message = 'Registration failed: ' . $conn->error;
                $message_type = 'error';
            } else {
                $newId = (int) $conn->insert_id;

                if ($role === 'student') {
                    if (!$conn->query("INSERT INTO student_profiles (user_id) VALUES ($newId)")) {
                        $ok = false;
                        $message = 'Could not create student profile: ' . $conn->error;
                        $message_type = 'error';
                    }
                } else {
                    $subject = $conn->real_escape_string(trim($_POST['subject'] ?? 'General'));
                    $bio = $conn->real_escape_string(trim($_POST['bio'] ?? ''));
                    $quals = $conn->real_escape_string(trim($_POST['qualifications'] ?? ''));
                    $rate = floatval($_POST['hourly_rate'] ?? 40);
                    $exp = max(0, (int) ($_POST['experience_years'] ?? 0));
                    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
                    $contactEmail = $conn->real_escape_string(trim($_POST['contact_email'] ?? $email));
                    $portfolio = $conn->real_escape_string(trim($_POST['portfolio_url'] ?? ''));
                    $classDetails = $conn->real_escape_string(trim($_POST['class_details'] ?? ''));
                    $levels = $_POST['teaches_levels'] ?? ['high_school', 'undergraduate', 'graduate'];
                    $levelsStr = $conn->real_escape_string(implode(',', array_map('strval', $levels)));

                    $profileImg = null;
                    if (!empty($_FILES['profile_image']['name'])) {
                        $profileImg = upload_image($_FILES['profile_image'], 'tutors');
                        $profileImg = $profileImg ? $conn->real_escape_string($profileImg) : null;
                    }
                    $imgSql = $profileImg ? "'$profileImg'" : 'NULL';

                    $tutorSql = "INSERT INTO tutors (id, subject, bio, hourly_rate, qualifications, experience_years, phone, contact_email, portfolio_url, profile_image, teaches_levels, class_details)
                                 VALUES ($newId, '$subject', '$bio', $rate, '$quals', $exp, '$phone', '$contactEmail', '$portfolio', $imgSql, '$levelsStr', '$classDetails')";

                    if (!$conn->query($tutorSql)) {
                        $ok = false;
                        $message = 'Tutor profile could not be saved: ' . $conn->error . ' — Run setup/migrate.php first.';
                        $message_type = 'error';
                    } else {
                        foreach ($catIds as $cid) {
                            if (!$conn->query("INSERT INTO tutor_categories (tutor_id, category_id) VALUES ($newId, $cid)")) {
                                $ok = false;
                                $message = 'Could not save categories: ' . $conn->error;
                                $message_type = 'error';
                                break;
                            }
                        }
                    }
                }
            }

            if ($ok) {
                $conn->commit();
                if ($role === 'student') {
                    $message = 'Account created! Sign in to complete your learning profile.';
                } else {
                    $message = 'Tutor application saved (ID #' . $newId . '). An admin will approve your account — you can sign in to check status.';
                }
                $message_type = 'success';
            } else {
                $conn->rollback();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="auth-split">
    <?php include __DIR__ . '/../includes/auth-visual.php'; ?>
    <div class="auth-split-form">
        <div class="auth-form-inner auth-form-wide">
            <p class="page-eyebrow">Join Learnify</p>
            <h1 class="page-title">Create <em>Account</em></h1>
            <p class="page-subtitle mb-2">Students get matched tutors. Tutors apply for approval.</p>
            <hr class="hairline">

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" data-auth-form id="register-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="field-wrap" data-validate="name">
                        <input class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        <span class="field-line"></span>
                        <span class="field-hint"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="field-wrap" data-validate="email">
                        <input class="form-control" type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <span class="field-line"></span>
                        <span class="field-hint"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="field-wrap">
                        <input class="form-control" type="password" id="password" name="password" data-password-strength required minlength="8">
                        <span class="field-line"></span>
                        <div class="password-strength"><div class="password-strength-bar"><span></span></div><span class="password-strength-label"></span></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>I am a</label>
                    <div class="role-toggle" data-role-toggle data-active="<?php echo htmlspecialchars($_POST['role'] ?? 'student'); ?>">
                        <span class="role-toggle-pill"></span>
                        <button type="button" class="role-toggle-btn" data-role="student">Student</button>
                        <button type="button" class="role-toggle-btn" data-role="tutor">Tutor</button>
                    </div>
                    <input type="hidden" name="role" id="role-input" value="<?php echo htmlspecialchars($_POST['role'] ?? 'student'); ?>">
                </div>

                <div id="tutor-fields" class="tutor-fields-collapse">
                    <div class="form-group">
                        <label for="subject">Primary Subject <span class="req-star">*</span></label>
                        <input class="form-control tutor-required" id="subject" name="subject" placeholder="e.g. Mathematics" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Categories <span class="req-star">*</span></label>
                        <div class="chip-grid" id="tutor-categories">
                            <?php foreach ($categories as $cat): ?>
                                <label class="chip-option">
                                    <input type="checkbox" name="categories[]" value="<?php echo (int) $cat['id']; ?>"
                                        <?php echo in_array((int)$cat['id'], array_map('intval', $_POST['categories'] ?? [])) ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="field-hint" id="category-hint"></span>
                    </div>
                    <div class="form-group">
                        <label>Levels You Teach</label>
                        <div class="chip-grid">
                            <?php foreach ($eduLevels as $key => $label): ?>
                                <label class="chip-option">
                                    <input type="checkbox" name="teaches_levels[]" value="<?php echo $key; ?>"
                                        <?php echo in_array($key, $_POST['teaches_levels'] ?? ['high_school','undergraduate','graduate']) ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($label); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="qualifications">Qualifications <span class="req-star">*</span></label>
                        <textarea class="form-control tutor-required" id="qualifications" name="qualifications" rows="3"><?php echo htmlspecialchars($_POST['qualifications'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="2"><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="class_details">Course & Class Details</label>
                        <textarea class="form-control" id="class_details" name="class_details" rows="2"><?php echo htmlspecialchars($_POST['class_details'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hourly_rate">Hourly Rate ($)</label>
                            <input class="form-control" type="number" id="hourly_rate" name="hourly_rate" step="0.01" value="<?php echo htmlspecialchars($_POST['hourly_rate'] ?? '45'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="experience_years">Experience (yrs)</label>
                            <input class="form-control" type="number" id="experience_years" name="experience_years" min="0" value="<?php echo htmlspecialchars($_POST['experience_years'] ?? '1'); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input class="form-control" id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_email">Contact Email</label>
                            <input class="form-control" id="contact_email" name="contact_email" type="email" value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="portfolio_url">Portfolio / Link</label>
                        <input class="form-control" id="portfolio_url" name="portfolio_url" type="url" placeholder="https://" value="<?php echo htmlspecialchars($_POST['portfolio_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="profile_image">Profile Photo (optional)</label>
                        <input class="form-control form-control-file" id="profile_image" name="profile_image" type="file" accept="image/*">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-shimmer btn-submit">
                        <span class="btn-text">Register</span>
                        <span class="btn-spinner"></span>
                    </button>
                </div>
            </form>
            <p class="form-footer">Already have an account? <a href="<?php echo page_url('auth/login.php'); ?>">Sign in</a></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
