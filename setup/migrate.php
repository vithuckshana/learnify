<?php

require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Learnify Migration v2\n" . str_repeat('=', 40) . "\n\n";

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

function run(mysqli $conn, string $sql, string $label): void
{
    if ($conn->query($sql)) {
        echo "OK  $label\n";
    } else {
        echo "ERR $label — " . $conn->error . "\n";
    }
}

run($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('student','tutor','admin') NOT NULL", 'users.role enum');
if (!column_exists($conn, 'users', 'status')) {
    run($conn, "ALTER TABLE users ADD COLUMN status ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'active' AFTER role", 'users.status');
}
run($conn, "UPDATE users SET status = 'active' WHERE status = '' OR status IS NULL", 'users.status backfill');

run($conn, "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)", 'categories table');

run($conn, "CREATE TABLE IF NOT EXISTS student_profiles (
    user_id INT PRIMARY KEY,
    about_me TEXT,
    education_level ENUM('high_school','undergraduate','graduate','professional','other') DEFAULT 'other',
    learning_goals TEXT,
    preferred_mode ENUM('online','in_person','both') DEFAULT 'both',
    questionnaire_completed TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)", 'student_profiles table');

run($conn, "CREATE TABLE IF NOT EXISTS student_categories (
    student_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (student_id, category_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)", 'student_categories table');

$tutorCols = [
    'qualifications'    => "TEXT",
    'experience_years'  => "INT NOT NULL DEFAULT 0",
    'phone'             => "VARCHAR(30) DEFAULT NULL",
    'contact_email'     => "VARCHAR(120) DEFAULT NULL",
    'portfolio_url'     => "VARCHAR(255) DEFAULT NULL",
    'profile_image'     => "VARCHAR(255) DEFAULT NULL",
    'teaches_levels'    => "VARCHAR(120) DEFAULT 'high_school,undergraduate,graduate'",
    'class_details'     => "TEXT",
];
foreach ($tutorCols as $col => $def) {
    if (!column_exists($conn, 'tutors', $col)) {
        run($conn, "ALTER TABLE tutors ADD COLUMN $col $def", "tutors.$col");
    }
}

run($conn, "CREATE TABLE IF NOT EXISTS tutor_categories (
    tutor_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (tutor_id, category_id),
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)", 'tutor_categories table');

run($conn, "CREATE TABLE IF NOT EXISTS tutor_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    class_level VARCHAR(80) DEFAULT NULL,
    description TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    join_link VARCHAR(255) DEFAULT NULL,
    contact_phone VARCHAR(30) DEFAULT NULL,
    contact_email VARCHAR(120) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
)", 'tutor_courses table');

run($conn, "CREATE TABLE IF NOT EXISTS tutor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
)", 'tutor_availability table');

if (!column_exists($conn, 'bookings', 'booking_time')) {
    run($conn, "ALTER TABLE bookings ADD COLUMN booking_time TIME DEFAULT NULL", 'bookings.booking_time');
}
if (!column_exists($conn, 'bookings', 'notes')) {
    run($conn, "ALTER TABLE bookings ADD COLUMN notes TEXT", 'bookings.notes');
}

$categories = [
    ['Mathematics', 'mathematics', 'Algebra, calculus, statistics'],
    ['Computer Science', 'computer-science', 'Programming, web, algorithms'],
    ['Physics', 'physics', 'Mechanics, electricity, modern physics'],
    ['Chemistry', 'chemistry', 'Organic, inorganic, lab prep'],
    ['Biology', 'biology', 'Cell biology, genetics, ecology'],
    ['English', 'english', 'Literature, writing, IELTS'],
    ['Business', 'business', 'Accounting, economics, management'],
    ['Languages', 'languages', 'French, Spanish, Mandarin'],
];
foreach ($categories as [$name, $slug, $desc]) {
    $n = $conn->real_escape_string($name);
    $s = $conn->real_escape_string($slug);
    $d = $conn->real_escape_string($desc);
    $conn->query("INSERT IGNORE INTO categories (name, slug, description) VALUES ('$n','$s','$d')");
}
echo "OK  categories seeded\n";

$adminEmail = 'admin@learnify.com';
$check = $conn->query("SELECT id FROM users WHERE email = '$adminEmail'");
if ($check && $check->num_rows === 0) {
    $hash = password_hash('Learnify123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('Site Admin', '$adminEmail', '$hash', 'admin', 'active')");
    echo "OK  admin@learnify.com created (Learnify123)\n";
} else {
    $conn->query("UPDATE users SET role = 'admin', status = 'active' WHERE email = '$adminEmail'");
    echo "OK  admin account ensured\n";
}

$conn->query("UPDATE users SET status = 'active' WHERE role IN ('student','admin')");
$conn->query("UPDATE users u JOIN tutors t ON u.id = t.id SET u.status = 'active' WHERE u.role = 'tutor' AND u.status = 'pending'");

echo "\nDone. Open: " . (dirname($_SERVER['SCRIPT_NAME']) === '/Learnify/setup' ? '/Learnify' : '') . "/admin/dashboard.php\n";
