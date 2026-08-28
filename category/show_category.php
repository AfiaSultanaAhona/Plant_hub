<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$result = mysqli_query($conn, "SELECT * FROM category ORDER BY Category_ID DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Category Management - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { background: #10b981; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .btn-edit { background: #0284c7; } .btn-del { background: #e11d48; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📁 Plant Categories</h2>
        <div>
            <a href="add_category.php" class="btn">➕ Add Category</a>
            <a href="employee_dashboard.php" class="btn" style="background:#64748b;">Dashboard</a>
        </div>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Category Name</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo $row['Category_ID'] ?? $row['category_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['Category_name'] ?? $row['category_name']); ?></strong></td>
                    <td>
                        <a href="modify_category.php?id=<?php echo $row['Category_ID'] ?? $row['category_id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="delete_category.php?id=<?php echo $row['Category_ID'] ?? $row['category_id']; ?>" class="btn btn-del" onclick="return confirm('Delete category?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center; color:#64748b;">No categories created yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>