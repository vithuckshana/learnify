<?php

require_once __DIR__ . '/../config/db.php';

echo "<pre>Learnify seed v2\n\n";
echo "Run migrate first: setup/migrate.php\n\n";

$password = password_hash('Learnify123', PASSWORD_DEFAULT);

$users = [
    ['Alex Rivera', 'student@learnify.com', 'student', 'active'],
    ['Dr. Morgan Blake', 'tutor@learnify.com', 'tutor', 'active'],
    ['Jordan Lee', 'tutor2@learnify.com', 'tutor', 'active'],
];

foreach ($users as [$name, $email, $role, $status]) {
    $safeEmail = $conn->real_escape_string($email);
    $safeName = $conn->real_escape_string($name);
    $check = $conn->query("SELECT id FROM users WHERE email = '$safeEmail'");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE users SET status = '$status' WHERE email = '$safeEmail'");
        echo "Updated: $email\n";
        continue;
    }
    $conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('$safeName', '$safeEmail', '$password', '$role', '$status')");
    echo "Created: $email\n";
}

$tutorProfiles = [
    2 => ['Mathematics', 'Calculus & exam prep specialist.', 45.00, 'PhD Mathematics, 10 years teaching', 10],
    3 => ['Computer Science', 'Full-stack mentor and CS tutor.', 55.00, 'MSc Computer Science, industry experience', 7],
];

foreach ($tutorProfiles as $userId => [$subject, $bio, $rate, $quals, $exp]) {
    $s = $conn->real_escape_string($subject);
    $b = $conn->real_escape_string($bio);
    $q = $conn->real_escape_string($quals);
    $check = $conn->query("SELECT id FROM tutors WHERE id = $userId");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE tutors SET subject='$s', bio='$b', qualifications='$q', experience_years=$exp, hourly_rate=$rate WHERE id=$userId");
    } else {
        $conn->query("INSERT INTO tutors (id, subject, bio, hourly_rate, qualifications, experience_years) VALUES ($userId,'$s','$b',$rate,'$q',$exp)");
    }
    $catSlug = $userId === 2 ? 'mathematics' : 'computer-science';
    $cat = $conn->query("SELECT id FROM categories WHERE slug = '$catSlug'")->fetch_assoc();
    if ($cat) {
        $conn->query("INSERT IGNORE INTO tutor_categories (tutor_id, category_id) VALUES ($userId, {$cat['id']})");
    }
}

$student = $conn->query("SELECT id FROM users WHERE email = 'student@learnify.com'")->fetch_assoc();
if ($student) {
    $sid = (int) $student['id'];
    $conn->query("INSERT INTO student_profiles (user_id, about_me, education_level, learning_goals, preferred_mode, questionnaire_completed)
                  VALUES ($sid, 'High school student passionate about math and programming.', 'high_school', 'Exam prep and coding skills', 'online', 1)
                  ON DUPLICATE KEY UPDATE questionnaire_completed = 1");
    $math = $conn->query("SELECT id FROM categories WHERE slug = 'mathematics'")->fetch_assoc();
    $cs = $conn->query("SELECT id FROM categories WHERE slug = 'computer-science'")->fetch_assoc();
    if ($math) $conn->query("INSERT IGNORE INTO student_categories (student_id, category_id) VALUES ($sid, {$math['id']})");
    if ($cs) $conn->query("INSERT IGNORE INTO student_categories (student_id, category_id) VALUES ($sid, {$cs['id']})");
}

echo "\nLogins (password: Learnify123)\n";
echo "  admin@learnify.com    — Admin\n";
echo "  student@learnify.com  — Student\n";
echo "  tutor@learnify.com    — Tutor (approved)\n";
echo "  tutor2@learnify.com   — Tutor (approved)\n";
echo "</pre>";
