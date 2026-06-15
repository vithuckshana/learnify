<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: text/plain');

echo "Learnify Repair\n" . str_repeat('=', 40) . "\n\n";

$orphans = $conn->query("SELECT u.id, u.email, u.status FROM users u
    LEFT JOIN tutors t ON u.id = t.id WHERE u.role = 'tutor' AND t.id IS NULL");

$fixed = 0;
if ($orphans) {
    while ($row = $orphans->fetch_assoc()) {
        $id = (int) $row['id'];
        if (ensure_tutor_profile($conn, $id)) {
            echo "Fixed tutor profile for #{$id} {$row['email']}\n";
            $fixed++;
        } else {
            echo "FAILED for #{$id} {$row['email']}: " . $conn->error . "\n";
        }
    }
}

if ($fixed === 0) {
    echo "No orphan tutor accounts found.\n";
}

$students = $conn->query("SELECT u.id FROM users u LEFT JOIN student_profiles sp ON u.id = sp.user_id
    WHERE u.role = 'student' AND sp.user_id IS NULL");
$sfixed = 0;
if ($students) {
    while ($row = $students->fetch_assoc()) {
        $id = (int) $row['id'];
        if ($conn->query("INSERT INTO student_profiles (user_id) VALUES ($id)")) {
            echo "Fixed student profile for #$id\n";
            $sfixed++;
        }
    }
}

echo "\nDone. Tutors fixed: $fixed | Students fixed: $sfixed\n";
