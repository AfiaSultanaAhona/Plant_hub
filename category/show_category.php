<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $cname = mysqli_real_escape_string($conn, $_POST['category_name']);
    if (!empty($cname)) {
        mysqli_query($conn, "INSERT INTO category (Category_name) VALUES ('$cname')");
        $msg = "Category added!";
    }
}

if (isset($_GET['delete_cat'])) {
    $cid = (int)$_GET['delete_cat'];
    mysqli_query($conn, "DELETE FROM category WHERE Category_ID = $cid");
    header("Location: show_category.php");
    exit;
}

$categories_query = mysqli_query($conn, "SELECT * FROM category ORDER BY Category_ID DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Plant Categories - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 20px; color: #1e293b; }
        .container { max-width: 600px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .form-control { width: 100%; padding: 10px; margin: 6px 0 14px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .btn-del { background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .btn-back { background: #64748b; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; float: right; }
    </style>
</head>
<body>
<div class="container">
    <a href="employee_dashboard.php" class="btn-back">⬅ Dashboard</a>
    <h2>📁 Plant Categories</h2>
    <form method="POST">
        <label>New Category Name</label>
        <input type="text" name="category_name" class="form-control" required>
        <button type="submit" class="btn">Add Category</button>
    </form>
    <table class="table">
        <thead><tr><th>ID</th><th>Category Name</th><th>Action</th></tr></thead>
        <tbody>
            <?php if ($categories_query && mysqli_num_rows($categories_query) > 0): ?>
                <?php while ($c = mysqli_fetch_assoc($categories_query)): ?>
                <tr>
                    <td>#<?php echo $c['Category_ID'] ?? $c['category_id']; ?></td>
                    <td><?php echo htmlspecialchars($c['Category_name'] ?? $c['category_name']); ?></td>
                    <td><a href="show_category.php?delete_cat=<?php echo $c['Category_ID'] ?? $c['category_id']; ?>" class="btn-del" onclick="return confirm('Delete category?')">Delete</a></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>