<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../includes/helpers.php';
    redirect_home();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

$page_title = 'Login';
$is_auth = true;
$message = '';
$message_type = '';

if (isset($_GET['error']) && $_GET['error'] === 'rejected') {
    $message = 'Your tutor application was not approved.';
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $status = $user['status'] ?? 'active';

        if (!password_verify($password, $user['password'])) {
            $message = 'Incorrect password. Try again.';
            $message_type = 'error';
        } elseif ($status === 'suspended') {
            $message = 'This account has been suspended.';
            $message_type = 'error';
        } elseif ($status === 'rejected') {
            $message = 'Your tutor application was not approved. Contact support.';
            $message_type = 'error';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $status;

            if ($user['role'] === 'tutor' && $status === 'active') {
                ensure_tutor_profile($conn, (int) $user['id']);
            }

            redirect_home();
        }
    } else {
        $message = 'No account found for that email.';
        $message_type = 'error';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="auth-split">
    <?php include __DIR__ . '/../includes/auth-visual.php'; ?>
    <div class="auth-split-form">
        <div class="auth-form-inner">
            <p class="page-eyebrow">Secure Access</p>
            <h1 class="page-title">Welcome <em>Back</em></h1>
            <p class="page-subtitle mb-2">Sign in to your Learnify account.</p>
            <hr class="hairline">

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" data-auth-form>
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="field-wrap" data-validate="email">
                        <input class="form-control" type="email" id="email" name="email" required autocomplete="email">
                        <span class="field-line"></span>
                        <span class="field-hint" aria-live="polite"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="field-wrap">
                        <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                        <span class="field-line"></span>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-shimmer btn-submit">
                        <span class="btn-text">Sign In</span>
                        <span class="btn-spinner"></span>
                    </button>
                </div>
            </form>
            <p class="form-footer">New here? <a href="<?php echo page_url('auth/register.php'); ?>">Create an account</a></p>
            <p class="form-footer" style="font-size:0.8rem">Tutors: pending accounts can sign in to check approval status.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
