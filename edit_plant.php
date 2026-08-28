<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$pid = (int)($_GET['id'] ?? 0);
$msg = "";
$error = "";

// Save Updated Plant Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_plant'])) {
    $name = mysqli_real_escape_string($conn, $_POST['plant_name']);
    $cat_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock_quantity'];

    $update_sql = "UPDATE plant SET Plant_name = '$name', Category_ID = $cat_id, Price = $price, Stock_quantity = $stock WHERE Plant_ID = $pid";
    if (mysqli_query($conn, $update_sql)) {
        header("Location: employee_dashboard.php?view=inventory&msg=updated");
        exit;
    } else {
        $error = "Error updating plant: " . mysqli_error($conn);
    }
}

// Fetch Current Plant Details
$plant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM plant WHERE Plant_ID = $pid"));
$categories = mysqli_query($conn, "SELECT * FROM category ORDER BY Category_name ASC");

if (!$plant) {
    die("Plant record not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; padding: 40px; margin: 0; }
        .card { max-width: 480px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 14px; color: #334155; }
        input, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { background: #0284c7; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #0284c7; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <a href="employee_dashboard.php?view=inventory" class="back-link">← Back to Inventory</a>
    <h2>✏️ Edit Plant & Category</h2>

    <form method="POST" action="edit_plant.php?id=<?php echo $pid; ?>">
        <div class="form-group">
            <label>Plant Name</label>
            <input type="text" name="plant_name" value="<?php echo htmlspecialchars($plant['Plant_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?php echo $c['Category_ID']; ?>" <?php echo ($plant['Category_ID'] == $c['Category_ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['Category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Price (৳)</label>
            <input type="number" step="0.01" name="price" value="<?php echo $plant['Price'] ?? 0; ?>" required>
        </div>
        <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock_quantity" value="<?php echo $plant['Stock_quantity'] ?? 0; ?>" required>
        </div>
        <button type="submit" name="update_plant" class="btn-submit">Update Plant</button>
    </form>
</div>

</body>
</html>