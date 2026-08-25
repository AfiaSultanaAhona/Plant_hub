<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");
mysqli_report(MYSQLI_REPORT_OFF);

$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'Staff';
$message = '';
$message_type = '';

// 1. Handle Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $plant_id = (int)($_POST['plant_id'] ?? 0);
    $new_stock = (int)($_POST['stock_quantity'] ?? 0);

    if ($plant_id > 0) {
        $stmt = $conn->prepare("UPDATE plant SET Stock_quantity = ? WHERE Plant_ID = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $new_stock, $plant_id);
            if ($stmt->execute()) {
                $message = "Stock updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating stock: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// 2. Handle Adding a New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $cat_name = trim($_POST['category_name'] ?? '');
    if (!empty($cat_name)) {
        // Auto-generate next Category_ID
        $id_query = mysqli_query($conn, "SELECT MAX(Category_ID) AS max_id FROM category");
        $id_row = mysqli_fetch_assoc($id_query);
        $next_cat_id = ($id_row['max_id'] !== null) ? ((int)$id_row['max_id'] + 1) : 1;

        $stmt = $conn->prepare("INSERT INTO category (Category_ID, Category_name) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $next_cat_id, $cat_name);
            if ($stmt->execute()) {
                $message = "New category '$cat_name' created successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding category: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// 3. Handle Adding a New Plant (Generates Plant_ID automatically)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    $stock = (int)($_POST['stock'] ?? 0);
    $care = trim($_POST['care_info'] ?? 'Regular watering and adequate light.');
    $category_id = (int)($_POST['category_id'] ?? 1);
    $low_stock = 5;

    if (!empty($name)) {
        // Calculate max Plant_ID + 1 to prevent DB constraint errors
        $id_query = mysqli_query($conn, "SELECT MAX(Plant_ID) AS max_id FROM plant");
        $id_row = mysqli_fetch_assoc($id_query);
        $next_id = ($id_row['max_id'] !== null) ? ((int)$id_row['max_id'] + 1) : 1;

        $stmt = $conn->prepare("INSERT INTO plant (Plant_ID, Plant_name, Unit_price, Stock_quantity, Low_stock_level, Care_info, Category_ID) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isdiisi", $next_id, $name, $price, $stock, $low_stock, $care, $category_id);
            if ($stmt->execute()) {
                $message = "New plant '$name' added successfully! (ID: #$next_id)";
                $message_type = "success";
            } else {
                $message = "Error adding plant: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } else {
        $message = "Plant name cannot be blank.";
        $message_type = "error";
    }
}

// Fetch categories for select dropdown
$categories = [];
$cat_result = mysqli_query($conn, "SELECT * FROM category");
if ($cat_result) {
    while ($c = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $c;
    }
}

// Fetch existing plants list
$plants = [];
$plant_result = mysqli_query($conn, "SELECT p.*, c.Category_name FROM plant p LEFT JOIN category c ON p.Category_ID = c.Category_ID ORDER BY p.Plant_ID DESC");
if ($plant_result) {
    while ($p = mysqli_fetch_assoc($plant_result)) {
        $plants[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Management - Plant Hub 🌿</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; }
        h2 { color: #0d3822; margin-top: 0; border-bottom: 2px solid #eef7f2; padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 13px; font-weight: bold; color: #374151; margin-bottom: 5px; }
        input, select, textarea { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .btn { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #059669; }
        
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; color: #374151; font-size: 13px; text-transform: uppercase; }
        .stock-input { width: 70px; padding: 6px; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-low { background: #fef3c7; color: #92400e; }
        .badge-ok { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Add New Category -->
    <div class="card">
        <h2>📂 Add Category</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_category">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="category_name" placeholder="Category Name (e.g. Succulents)" required style="flex-grow: 1;">
                <button type="submit" class="btn">Add Category</button>
            </div>
        </form>
    </div>

    <!-- Add New Plant -->
    <div class="card">
        <h2>🌱 Add New Plant to Catalog</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_item">
            <div class="form-grid">
                <div class="form-group">
                    <label>Plant Name</label>
                    <input type="text" name="name" required placeholder="e.g. Snake Plant">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['Category_ID']; ?>"><?php echo htmlspecialchars($cat['Category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit Price ($)</label>
                    <input type="number" step="0.01" name="price" required placeholder="19.99">
                </div>
                <div class="form-group">
                    <label>Initial Stock</label>
                    <input type="number" name="stock" required placeholder="10">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Care Instructions</label>
                <textarea name="care_info" rows="2" placeholder="Watering guidelines, light needs..."></textarea>
            </div>
            <button type="submit" class="btn">Add Plant</button>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="card">
        <h2>📦 Current Inventory Stock</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock Quantity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plants as $plant): ?>
                    <tr>
                        <td>#<?php echo $plant['Plant_ID']; ?></td>
                        <td><strong><?php echo htmlspecialchars($plant['Plant_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($plant['Category_name'] ?? 'Uncategorized'); ?></td>
                        <td>$<?php echo number_format($plant['Unit_price'], 2); ?></td>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="plant_id" value="<?php echo $plant['Plant_ID']; ?>">
                            <td>
                                <input type="number" name="stock_quantity" class="stock-input" value="<?php echo $plant['Stock_quantity']; ?>">
                            </td>
                            <td>
                                <?php if ($plant['Stock_quantity'] <= $plant['Low_stock_level']): ?>
                                    <span class="badge badge-low">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-ok">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px;">Update</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>