<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$msg = "";

// Handle Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $pid = (int)$_POST['plant_id'];
    $new_stock = (int)$_POST['stock_quantity'];
    
    mysqli_query($conn, "UPDATE plant SET Stock_quantity = '$new_stock' WHERE Plant_ID = '$pid'");
    $msg = "Stock level updated successfully!";
}

// Handle Adding New Plant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plant'])) {
    $name = mysqli_real_escape_string($conn, $_POST['plant_name']);
    $price = (float)$_POST['unit_price'];
    $stock = (int)$_POST['stock_quantity'];
    $low_stock = (int)$_POST['low_stock'];
    $category_id = (int)$_POST['category_id'];

    mysqli_query($conn, "INSERT INTO plant (Plant_name, Unit_price, Stock_quantity, Low_stock_level, Category_ID) 
                         VALUES ('$name', '$price', '$stock', '$low_stock', '$category_id')");
    $msg = "New plant added to catalog!";
}

$inventory = mysqli_query($conn, "SELECT p.*, c.Category_name FROM plant p LEFT JOIN category c ON p.Category_ID = c.Category_ID");
$categories = mysqli_query($conn, "SELECT * FROM category");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory Management - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 30px; }
        .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .alert { background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Inventory Stock Levels 📦</h2>
    <?php if ($msg) echo "<p style='color:green;'>$msg</p>"; ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Current Stock</th>
            <th>Action</th>
        </tr>
        <?php while($item = mysqli_fetch_assoc($inventory)): ?>
        <tr>
            <td>#<?php echo $item['Plant_ID']; ?></td>
            <td><?php echo htmlspecialchars($item['Plant_name']); ?></td>
            <td><?php echo htmlspecialchars($item['Category_name'] ?? 'General'); ?></td>
            <td>৳<?php echo $item['Unit_price']; ?></td>
            <td>
                <?php echo $item['Stock_quantity']; ?>
                <?php if ($item['Stock_quantity'] <= $item['Low_stock_level']): ?>
                    <span class="alert">Low Stock</span>
                <?php endif; ?>
            </td>
            <td>
                <form method="POST" style="display:inline-flex; gap: 5px;">
                    <input type="hidden" name="plant_id" value="<?php echo $item['Plant_ID']; ?>">
                    <input type="number" name="stock_quantity" value="<?php echo $item['Stock_quantity']; ?>" style="width:60px;">
                    <button type="submit" name="update_stock">Update</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>