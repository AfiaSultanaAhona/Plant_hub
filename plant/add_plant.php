<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, $_POST['plant_name'] ?? '');
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'Indoor');
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock_quantity'] ?? 0);

    if (!empty($name)) {
        // Inspect table structure to insert with precise column matches
        $cols_res = mysqli_query($conn, "SHOW COLUMNS FROM plant");
        $cols = [];
        while ($c = mysqli_fetch_assoc($cols_res)) {
            $cols[] = $c['Field'];
        }

        // Map fields
        $name_col = in_array('Plant_name', $cols) ? 'Plant_name' : 'plant_name';
        $cat_col  = in_array('Category', $cols) ? 'Category' : (in_array('category', $cols) ? 'category' : 'Category_ID');
        $price_col= in_array('Price', $cols) ? 'Price' : (in_array('price', $cols) ? 'price' : 'Plant_price');
        $stock_col= in_array('Stock_quantity', $cols) ? 'Stock_quantity' : 'stock_quantity';

        $sql = "INSERT INTO plant (`$name_col`, `$cat_col`, `$price_col`, `$stock_col`) 
                VALUES ('$name', '$category', $price, $stock)";

        if (mysqli_query($conn, $sql)) {
            header("Location: employee_dashboard.php?view=inventory&msg=added_new");
            exit;
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Plant name is required.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 40px; }
        .card { max-width: 480px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 14px; color: #334155; }
        input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-size: 15px; }
        .btn-submit:hover { background: #059669; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #0284c7; text-decoration: none; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>

<div class="card">
    <a href="employee_dashboard.php?view=inventory" class="back-link">← Back to Inventory</a>
    <h2 style="margin-top:0; color:#064e3b;">➕ Add New Plant</h2>

    <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" action="add_plant.php">
        <div class="form-group">
            <label>Plant Name</label>
            <input type="text" name="plant_name" required placeholder="e.g., Monstera Deliciosa">
        </div>
        <div class="form-group">
            <label>Category</label>
            <input type="text" name="category" placeholder="e.g., Indoor / Succulent" value="Indoor">
        </div>
        <div class="form-group">
            <label>Price (৳)</label>
            <input type="number" step="0.01" name="price" required placeholder="250.00">
        </div>
        <div class="form-group">
            <label>Initial Stock Quantity</label>
            <input type="number" name="stock_quantity" required placeholder="10">
        </div>
        <button type="submit" class="btn-submit">Save Plant</button>
    </form>
</div>

</body>
</html>