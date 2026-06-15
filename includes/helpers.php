<?php

require_once __DIR__ . '/../config/app.php';

function require_login(?string $role = null): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . page_url('auth/login.php'));
        exit;
    }
    if ($role && ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . page_url('auth/login.php'));
        exit;
    }

    if ($role === 'tutor' && ($_SESSION['status'] ?? 'active') === 'pending') {
        $script = basename($_SERVER['PHP_SELF'] ?? '');
        if ($script !== 'pending.php') {
            header('Location: ' . page_url('tutor/pending.php'));
            exit;
        }
    }
}

function redirect_home(): void
{
    global $conn;
    $role = $_SESSION['role'] ?? '';
    $status = $_SESSION['status'] ?? 'active';
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($role === 'admin') {
        header('Location: ' . page_url('admin/dashboard.php'));
    } elseif ($role === 'tutor') {
        if ($status === 'pending') {
            header('Location: ' . page_url('tutor/pending.php'));
        } elseif ($status === 'rejected') {
            header('Location: ' . page_url('auth/login.php?error=rejected'));
        } else {
            if (isset($conn) && $userId) {
                ensure_tutor_profile($conn, $userId);
            }
            header('Location: ' . page_url('tutor/dashboard.php'));
        }
    } else {
        header('Location: ' . page_url('student/dashboard.php'));
    }
    exit;
}

function ensure_tutor_profile(mysqli $conn, int $userId): bool
{
    $check = $conn->query("SELECT id FROM tutors WHERE id = $userId LIMIT 1");
    if ($check && $check->num_rows > 0) {
        return true;
    }

    $user = $conn->query("SELECT email FROM users WHERE id = $userId AND role = 'tutor' LIMIT 1");
    if (!$user || !$user->num_rows) {
        return false;
    }
    $email = $user->fetch_assoc()['email'];
    $safeEmail = $conn->real_escape_string($email);

    $sql = "INSERT INTO tutors (id, subject, bio, hourly_rate, qualifications, experience_years, contact_email, teaches_levels)
            VALUES ($userId, 'General', 'Tutor on Learnify', 40.00, '', 0, '$safeEmail', 'high_school,undergraduate,graduate')";
    return (bool) $conn->query($sql);
}

function get_tutor_row(mysqli $conn, int $userId): ?array
{
    $res = $conn->query("SELECT * FROM tutors WHERE id = $userId LIMIT 1");
    return ($res && $res->num_rows) ? $res->fetch_assoc() : null;
}

function education_levels(): array
{
    return [
        'high_school'   => 'High School',
        'undergraduate' => 'Undergraduate',
        'graduate'      => 'Graduate',
        'professional'  => 'Professional',
        'other'         => 'Other',
    ];
}

function preferred_modes(): array
{
    return [
        'online'     => 'Online',
        'in_person'  => 'In Person',
        'both'       => 'Online & In Person',
    ];
}

function get_categories(mysqli $conn, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM categories';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY name';
    $result = $conn->query($sql);
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function is_questionnaire_complete(mysqli $conn, int $userId): bool
{
    $res = $conn->query("SELECT questionnaire_completed FROM student_profiles WHERE user_id = $userId LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return (bool) $row['questionnaire_completed'];
    }
    return false;
}

function get_student_profile(mysqli $conn, int $userId): ?array
{
    $res = $conn->query("SELECT * FROM student_profiles WHERE user_id = $userId LIMIT 1");
    return ($res && $res->num_rows) ? $res->fetch_assoc() : null;
}

function get_student_category_ids(mysqli $conn, int $studentId): array
{
    $ids = [];
    $res = $conn->query("SELECT category_id FROM student_categories WHERE student_id = $studentId");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['category_id'];
        }
    }
    return $ids;
}

function get_suggested_tutors(mysqli $conn, int $studentId, int $limit = 12): array
{
    $profile = get_student_profile($conn, $studentId);
    $studentCats = get_student_category_ids($conn, $studentId);
    $eduLevel = $profile['education_level'] ?? 'other';

    $catFilter = '';
    if ($studentCats) {
        $catList = implode(',', $studentCats);
        $catFilter = "AND tc.category_id IN ($catList)";
    }

    $sql = "SELECT t.*, u.name AS tutor_name,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS categories,
            COUNT(DISTINCT tc.category_id) AS match_count
        FROM tutors t
        JOIN users u ON t.id = u.id
        LEFT JOIN tutor_categories tc ON t.id = tc.tutor_id
        LEFT JOIN categories c ON c.id = tc.category_id
        WHERE u.role = 'tutor' AND u.status = 'active'
        $catFilter
        GROUP BY t.id
        ORDER BY match_count DESC, t.experience_years DESC, t.hourly_rate ASC
        LIMIT $limit";

    $result = $conn->query($sql);
    $tutors = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $levels = array_map('trim', explode(',', $row['teaches_levels'] ?? ''));
            $row['level_match'] = in_array($eduLevel, $levels, true);
            $row['match_score'] = ((int) $row['match_count'] * 40) + ($row['level_match'] ? 25 : 0) + min((int) $row['experience_years'], 10) * 3;
            $tutors[] = $row;
        }
        usort($tutors, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
    }
    return $tutors;
}

function upload_image(array $file, string $subdir): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed, true)) {
        return null;
    }
    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $path = $dir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return 'uploads/' . $subdir . '/' . $filename;
    }
    return null;
}

function upload_url(?string $path): string
{
    if (!$path) {
        return asset_url('assets/images/tutor-placeholder.svg');
    }
    return page_url($path);
}
