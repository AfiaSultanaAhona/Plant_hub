<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Session Validation
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

$role_label = '👤 Employee';
$emp_username = $_SESSION['username'] ?? $_SESSION['Employee_name'] ?? ('emp' . preg_replace('/[^0-9]/', '', (string)$employee_id));

// 2. Handle Add Stock Action (With PRG Pattern to stop double execution)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_stock') {
    $pid = (int)$_POST['plant_id'];
    $add_qty = (int)$_POST['add_quantity'];
    
    if ($add_qty > 0 && $pid >= 0) {
        // Detect exact primary key and stock column names dynamically
        $pk_col = 'Plant_ID';
        $stock_col = 'Stock_quantity';
        
        $cols = mysqli_query($conn, "SHOW COLUMNS FROM plant");
        if ($cols) {
            while ($c = mysqli_fetch_assoc($cols)) {
                $field_lower = strtolower($c['Field']);
                if ($field_lower === 'plant_id' || $field_lower === 'id') {
                    $pk_col = $c['Field'];
                }
                if ($field_lower === 'stock_quantity' || $field_lower === 'stock' || $field_lower === 'quantity') {
                    $stock_col = $c['Field'];
                }
            }
        }

        // Single atomic update
        $stmt = mysqli_prepare($conn, "UPDATE plant SET `$stock_col` = `$stock_col` + ? WHERE `$pk_col` = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $add_qty, $pid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // REDIRECT IMMEDIATELY: Prevents form re-submission / double POST trigger
    header("Location: employee_dashboard.php?view=inventory&msg=added&qty=$add_qty&id=$pid");
    exit;
}

// 3. Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    
    $pk_col = 'Plant_ID';
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM plant");
    if ($cols) {
        while ($c = mysqli_fetch_assoc($cols)) {
            $field_lower = strtolower($c['Field']);
            if ($field_lower === 'plant_id' || $field_lower === 'id') {
                $pk_col = $c['Field'];
                break;
            }
        }
    }
    
    mysqli_query($conn, "DELETE FROM plant WHERE `$pk_col` = $del_id");
    header("Location: employee_dashboard.php?view=inventory&msg=deleted&id=$del_id");
    exit;
}

// 4. Status Messages from Query Parameters
$msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $q = (int)($_GET['qty'] ?? 0);
        $i = (int)($_GET['id'] ?? 0);
        $msg = "✅ Added exactly $q stock to Plant #$i.";
    } elseif ($_GET['msg'] === 'deleted') {
        $i = (int)($_GET['id'] ?? 0);
        $msg = "🗑️ Plant #$i deleted successfully.";
    }
}

// 5. View Switcher State & Inventory Retrieval
$view = $_GET['view'] ?? 'dashboard';
$plants_query = null;
if ($view === 'inventory') {
    $plants_query = mysqli_query($conn, "SELECT * FROM plant ORDER BY 1 ASC");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 0; color: #1e293b; }
        .navbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .navbar .logo { font-size: 22px; font-weight: bold; color: #15803d; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #334155; text-decoration: none; font-weight: 600; font-size: 14px; }
        .nav-links a.active { color: #15803d; border-bottom: 2px solid #15803d; padding-bottom: 4px; }
        .user-badge { background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; }
        .btn-logout { background: #fee2e2; color: #ef4444; padding: 6px 16px; border-radius: 20px; text-decoration: none; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px; }
        .table th { background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; color: #475569; }
        .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .btn-add { background: #10b981; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn-del { background: #ef4444; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .alert-info { background: #dbeafe; color: #1e40af; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; }
        .stock-form { display: flex; gap: 6px; }
        .stock-input { width: 60px; padding: 4px 6px; border: 1px solid #cbd5e1; border-radius: 4px; }
        .btn-stock { background: #0284c7; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="employee_dashboard.php" class="logo">🌱 Plant Hub</a>
    <div class="nav-links">
        <a href="employee_dashboard.php" class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>">Home 🏠</a>
        <a href="employee_dashboard.php?view=inventory" class="<?php echo $view === 'inventory' ? 'active' : ''; ?>">Inventory 🌿</a>
        <div class="user-badge"><?php echo $role_label; ?></div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="container">
    <?php if ($view === 'inventory'): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>🌿 Plant Inventory Management</h2>
                <div>
                    <a href="add_plant.php" class="btn-add" style="margin-right: 15px;">➕ Add Plant</a>
                    <a href="employee_dashboard.php" style="color:#0284c7; text-decoration:none; font-weight:bold;">← Back to Dashboard</a>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert-info" style="margin-top:15px;"><?php echo $msg; ?></div>
            <?php endif; ?>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plant Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Quantity</th>
                        <th>Add Custom Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($plants_query && mysqli_num_rows($plants_query) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($plants_query)): 
                            // Case-insensitive array access
                            $row = array_change_key_case($p, CASE_LOWER);
                            $pid    = $row['plant_id'] ?? $row['id'] ?? 0;
                            $pname  = $row['plant_name'] ?? $row['name'] ?? '-';
                            $pcat   = $row['category'] ?? $row['category_name'] ?? '-';
                            $pprice = (float)($row['price'] ?? $row['plant_price'] ?? $row['unit_price'] ?? 0);
                            $pstock = $row['stock_quantity'] ?? $row['stock'] ?? 0;
                        ?>
                        <tr>
                            <td>#<?php echo $pid; ?></td>
                            <td><strong><?php echo htmlspecialchars($pname); ?></strong></td>
                            <td><?php echo htmlspecialchars($pcat); ?></td>
                            <td>৳<?php echo number_format($pprice, 2); ?></td>
                            <td><strong><?php echo $pstock; ?></strong></td>
                            <td>
                                <form method="POST" action="employee_dashboard.php?view=inventory" class="stock-form">
                                    <input type="hidden" name="action" value="add_stock">
                                    <input type="hidden" name="plant_id" value="<?php echo $pid; ?>">
                                    <input type="number" name="add_quantity" class="stock-input" min="1" placeholder="Qty" required>
                                    <button type="submit" class="btn-stock">+ Add</button>
                                </form>
                            </td>
                            <td>
                                <a href="employee_dashboard.php?view=inventory&action=delete&id=<?php echo $pid; ?>" class="btn-del" onclick="return confirm('Delete plant?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No plant records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>