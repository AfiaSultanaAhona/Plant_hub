<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$msg = ""; $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, $_POST['plant_name'] ?? '');
    $cat      = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock_quantity'] ?? 0);

    if (!empty($name) && $price > 0) {
        $sql = "INSERT INTO plant (Plant_name, Category, Price, Stock_quantity) VALUES ('$name', '$cat', '$price', '$stock')";
        if (mysqli_query($conn, $sql)) {
            $plant_id = mysqli_insert_id($conn);
            logEmployeeAction($conn, 'PLANT_ADD', "Added new plant '$name' (Initial Stock: $stock, Price: ৳$price)", $plant_id);
            header("Location: show_plant.php?msg=added");
            exit;
        } else { $error = mysqli_error($conn); }
    } else { $error = "Please fill in all required fields."; }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; }
        .card { max-width: 500px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #10b981; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2>➕ Add Plant</h2>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
    <form method="POST">
        <label>Plant Name *</label>
        <input type="text" name="plant_name" class="form-control" required>
        <label>Category</label>
        <input type="text" name="category" class="form-control">
        <label>Price (৳) *</label>
        <input type="number" step="0.01" name="price" class="form-control" required>
        <label>Stock Quantity *</label>
        <input type="number" name="stock_quantity" class="form-control" value="10" required>
        <button type="submit" class="btn">Save Plant & Log</button>
    </form>
</div>
</body>
</html>