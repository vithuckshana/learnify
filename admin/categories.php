<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $slug = $conn->real_escape_string(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-'));
    $desc = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    if ($name) {
        $conn->query("INSERT INTO categories (name, slug, description) VALUES ('$name', '$slug', '$desc')");
    }
    if (isset($_POST['toggle_id'])) {
        $tid = (int) $_POST['toggle_id'];
        $conn->query("UPDATE categories SET is_active = NOT is_active WHERE id = $tid");
    }
    header('Location: ' . page_url('admin/categories.php'));
    exit;
}

$cats = get_categories($conn, false);
$page_title = 'Categories';
include __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <p class="page-eyebrow">Admin</p>
    <h1 class="page-title">Subject <em>Categories</em></h1>
    <hr class="hairline">
</header>

<div class="grid-2">
    <div class="card">
        <h2 class="card-title">Add Category</h2>
        <form method="POST">
            <div class="form-group"><label>Name</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label>Description</label><input class="form-control" name="description"></div>
            <button class="btn btn-sm" type="submit">Add</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($cats as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><span class="badge <?php echo $c['is_active'] ? 'badge-accepted' : 'badge-rejected'; ?>"><?php echo $c['is_active'] ? 'Active' : 'Off'; ?></span></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="toggle_id" value="<?php echo (int)$c['id']; ?>">
                                <button class="btn btn-sm btn-ghost" type="submit">Toggle</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="mt-2"><a href="<?php echo page_url('admin/dashboard.php'); ?>">← Back to dashboard</a></p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
