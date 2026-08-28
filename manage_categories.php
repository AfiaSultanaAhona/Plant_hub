<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$msg = "";
$error = "";

// Handle Adding a New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category'])) {
    $cat_name = trim(mysqli_real_escape_string($conn, $_POST['category_name']));

    if (!empty($cat_name)) {
        // Check for duplicates (case-insensitive)
        $check = mysqli_query($conn, "SELECT * FROM category WHERE LOWER(Category_name) = LOWER('$cat_name')");
        
        if ($check && mysqli_num_rows($check) > 0) {
            $error = "Category '$cat_name' already exists.";
        } else {
            // Find highest existing ID to avoid 'Duplicate entry 0' errors
            $max_res = mysqli_query($conn, "SELECT MAX(Category_ID) AS max_id FROM category");
            $max_row = mysqli_fetch_assoc($max_res);
            $next_id = ((int)($max_row['max_id'] ?? -1)) + 1;

            // Explicitly insert with calculated next ID
            $insert_sql = "INSERT INTO category (Category_ID, Category_name) VALUES ($next_id, '$cat_name')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $msg = "✅ Category '$cat_name' added successfully with ID #$next_id!";
            } else {
                $error = "Error adding category: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "Please provide a valid category name.";
    }
}

// Fetch categories ordered sequentially by ID
$categories = mysqli_query($conn, "SELECT * FROM category ORDER BY Category_ID ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; padding: 40px; margin: 0; }
        .card { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .form-group { display: flex; gap: 10px; margin-top: 15px; }
        input[type="text"] { flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
        .btn { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #059669; }
        .table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .table th, .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .table th { background: #f8fafc; color: #475569; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #0284c7; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <a href="employee_dashboard.php?view=inventory" class="back-link">← Back to Inventory</a>
    <h2>🏷️ Manage Plant Categories</h2>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" action="manage_categories.php">
        <label><strong>Add New Category</strong></label>
        <div class="form-group">
            <input type="text" name="category_name" placeholder="e.g. Bonsai, Cactus" required>
            <button type="submit" name="new_category" class="btn">Add</button>
        </div>
    </form>

    <h3 style="margin-top:25px;">Existing Categories</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                    <tr>
                        <td>#<?php echo $c['Category_ID']; ?></td>
                        <td><strong><?php echo htmlspecialchars($c['Category_name']); ?></strong></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2" style="text-align:center; color:#64748b;">No categories found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>