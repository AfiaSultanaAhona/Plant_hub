<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

// Session Check
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

// Handle Delete Request
if (isset($_GET['delete_plant'])) {
    $del_id = (int)$_GET['delete_plant'];
    mysqli_query($conn, "DELETE FROM plant WHERE Plant_ID = $del_id");
    header("Location: show_plant.php");
    exit;
}

// Fetch Plants
$plants_query = mysqli_query($conn, "SELECT * FROM plant ORDER BY Plant_ID DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Plant Inventory - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 0; color: #1e293b; }
        .navbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .navbar .logo { font-size: 22px; font-weight: bold; color: #15803d; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #334155; text-decoration: none; font-weight: 600; font-size: 14px; }
        .nav-links .btn-logout { background: #fee2e2; color: #ef4444; padding: 6px 16px; border-radius: 20px; text-decoration: none; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-flex h2 { margin: 0; color: #064e3b; }
        .btn-add { background: #10b981; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; }
        
        .table { width: 100%; border-collapse: collapse; text-align: left; }
        .table th { background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 14px; }
        .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #334155; }
        .btn-del { background: #ef4444; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="employee_dashboard.php" class="logo">🌱 Plant Hub</a>
    <div class="nav-links">
        <a href="employee_dashboard.php">Home 🏠</a>
        <a href="show_plant.php">Inventory 🌿</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <div class="header-flex">
            <h2>🌿 Plant Inventory Management</h2>
            <a href="add_plant.php" class="btn-add">➕ Add Plant</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Plant Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Action</th>
                </tr>
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
                        <td>
                            <a href="show_plant.php?delete_plant=<?php echo $p['Plant_ID']; ?>" class="btn-del" onclick="return confirm('Are you sure you want to remove this plant?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No plant stock found in the database.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>