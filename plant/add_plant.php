<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1;
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
    <h2>➕ Add Plant to Inventory</h2>
    <form action="insert_plant.php" method="POST">
        <label>Plant Name *</label>
        <input type="text" name="plant_name" class="form-control" placeholder="Monstera Deliciosa" required>
        
        <label>Category</label>
        <input type="text" name="category" class="form-control" placeholder="Indoor">
        
        <label>Price (৳) *</label>
        <input type="number" step="0.01" name="price" class="form-control" placeholder="450.00" required>
        
        <label>Initial Stock Quantity *</label>
        <input type="number" name="stock_quantity" class="form-control" value="10" required>
        
        <button type="submit" class="btn">Save Plant & Log Action</button>
    </form>
</div>
</body>
</html>