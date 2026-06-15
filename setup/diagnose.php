<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html><head><title>Learnify Diagnostic</title>
<style>body{font-family:monospace;background:#0a0a0b;color:#f0e8d8;padding:2rem} .ok{color:#a8e6b4}.warn{color:#ffd080}.err{color:#ffb0b0}a{color:#ffb946}</style>
</head><body>
<h1>Learnify Tutor Diagnostic</h1>

<?php
$cols = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if (!$cols || !$cols->num_rows) {
    echo '<p class="err">MISSING users.status — <a href="migrate.php">Run migrate.php</a></p>';
} else {
    echo '<p class="ok">Schema OK (users.status exists)</p>';
}

echo '<h2>Tutor accounts</h2><table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">';
echo '<tr><th>ID</th><th>Email</th><th>Status</th><th>Tutors row</th><th>Can login?</th></tr>';

$r = $conn->query("SELECT u.id, u.email, u.status, t.id AS tutor_row, t.subject
    FROM users u LEFT JOIN tutors t ON u.id = t.id WHERE u.role = 'tutor' ORDER BY u.id");
while ($r && $row = $r->fetch_assoc()) {
    $hasRow = $row['tutor_row'] ? 'Yes' : '<span class="err">MISSING</span>';
    $canLogin = $row['status'] === 'active' && $row['tutor_row'] ? '<span class="ok">Dashboard</span>'
        : ($row['status'] === 'pending' ? '<span class="warn">Pending page</span>' : '<span class="err">Blocked</span>');
    echo "<tr><td>{$row['id']}</td><td>{$row['email']}</td><td>{$row['status']}</td><td>$hasRow</td><td>$canLogin</td></tr>";
}
echo '</table>';

$orphans = $conn->query("SELECT COUNT(*) c FROM users u LEFT JOIN tutors t ON u.id=t.id WHERE u.role='tutor' AND t.id IS NULL")->fetch_assoc()['c'];
if ($orphans > 0) {
    echo "<p class=\"err\">$orphans orphan tutor(s) — <a href='repair.php'>Run repair.php</a></p>";
} else {
    echo '<p class="ok">No orphan tutor accounts.</p>';
}
?>

<p><a href="../admin/dashboard.php">Admin Dashboard</a> · <a href="migrate.php">Migrate</a> · <a href="repair.php">Repair</a></p>
</body></html>
