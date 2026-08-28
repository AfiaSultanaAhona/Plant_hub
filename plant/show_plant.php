<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

if (isset($_GET['delete_plant'])) {
    $del_id = (int)$_GET['delete_plant'];
    mysqli_query($conn, "DELETE FROM plant WHERE Plant_ID = $del_id");
    header("Location: show_plant.php");
    exit;
}

$plants_query = mysqli_query($conn, "SELECT * FROM plant ORDER BY Plant_ID DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Plants - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 20px; color: #1e293b; }
        .container { max-width: 900px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .table th { background: #f8fafc; }
        .btn-del { background: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; }
        .btn-back { background: #64748b; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>🌿 Manage Plant Inventory</h2>
        <a href="employee_dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php if ($plants_query && mysqli_num_rows($plants_query) > 0): ?>
                <?php while ($p = mysqli_fetch_assoc($plants_query)): ?>
                <tr>
                    <td>#<?php echo $p['Plant_ID']; ?></td>
                    <td><strong><?php echo htmlspecialchars($p['Plant_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['Category'] ?? '-'); ?></td>
                    <td>৳<?php echo number_format($p['Price'], 2); ?></td>
                    <td><?php echo $p['Stock_quantity']; ?></td>
                    <td><a href="show_plant.php?delete_plant=<?php echo $p['Plant_ID']; ?>" class="btn-del" onclick="return confirm('Delete plant?')">Delete</a></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No plants available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>