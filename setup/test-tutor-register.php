<?php
/**
 * CLI test: simulates tutor registration and verifies DB rows.
 * Usage: php setup/test-tutor-register.php
 */
require_once __DIR__ . '/../config/db.php';

$testEmail = 'test_tutor_' . time() . '@learnify.com';
$name = 'Test Tutor';
$password = password_hash('TestPass123', PASSWORD_DEFAULT);
$subject = 'Physics';
$quals = 'MSc Physics';

$conn->begin_transaction();

$conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('$name', '$testEmail', '$password', 'tutor', 'pending')");
$id = (int) $conn->insert_id;

$ok = $conn->query("INSERT INTO tutors (id, subject, bio, hourly_rate, qualifications, experience_years, teaches_levels)
                    VALUES ($id, '$subject', 'Test bio', 50, '$quals', 3, 'high_school,undergraduate')");

$cat = $conn->query("SELECT id FROM categories LIMIT 1")->fetch_assoc();
if ($cat) {
    $conn->query("INSERT INTO tutor_categories (tutor_id, category_id) VALUES ($id, {$cat['id']})");
}

if ($ok) {
    $conn->commit();
    echo "PASS: Tutor #$id created ($testEmail)\n";
    $check = $conn->query("SELECT u.id, t.subject FROM users u JOIN tutors t ON u.id=t.id WHERE u.email='$testEmail'");
    print_r($check->fetch_assoc());
    $conn->query("DELETE FROM tutor_categories WHERE tutor_id = $id");
    $conn->query("DELETE FROM tutors WHERE id = $id");
    $conn->query("DELETE FROM users WHERE id = $id");
    echo "Cleaned up test user.\n";
} else {
    $conn->rollback();
    echo "FAIL: " . $conn->error . "\n";
}
