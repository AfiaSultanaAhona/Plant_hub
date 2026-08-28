<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$msg = ""; $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pname = mysqli_real_escape_string($conn, $_POST['plant_name'] ?? '');
    $cat   = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock_quantity'] ?? 0);

    if (!empty($pname) && $price > 0) {
        $sql = "INSERT INTO plant (Plant_name, Category, Price, Stock_quantity) VALUES ('$pname', '$cat', '$price', '$stock')";
        if (mysqli_query($conn, $sql)) {
            $msg = "✅ Plant '$pname' added successfully!";
        } else { $error = "Error: " . mysqli_error($conn); }
    } else { $error = "Please fill in all required fields."; }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 20px; color: #1e293b; }
        .card { background: white; max-width: 500px; margin: 40px auto; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .form-control { width: 100%; padding: 10px; margin: 6px 0 14px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-back { background: #64748b; text-decoration: none; display: inline-block; text-align: center; margin-top: 10px; width: 93%; }
        .alert-success { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="card">
    <h2>➕ Add New Plant</h2>
    <?php if ($msg): ?><div class="alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
        <label>Plant Name *</label>
        <input type="text" name="plant_name" class="form-control" required>
        <label>Category</label>
        <input type="text" name="category" class="form-control">
        <label>Price (৳) *</label>
        <input type="number" step="0.01" name="price" class="form-control" required>
        <label>Stock Quantity *</label>
        <input type="number" name="stock_quantity" class="form-control" value="10" required>
        <button type="submit" class="btn">Save Plant</button>
        <a href="employee_dashboard.php" class="btn btn-back">Back to Dashboard</a>
    </form>
</div>
</body>
</html>