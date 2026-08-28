<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$plant_id = (int)($_GET['id'] ?? 0);
$res = mysqli_query($conn, "SELECT * FROM plant WHERE Plant_ID = $plant_id");
$plant = mysqli_fetch_assoc($res);

if (!$plant) { die("Plant record not found."); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; }
        .card { max-width: 500px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #0284c7; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2>✏️ Modify Plant #<?php echo $plant_id; ?></h2>
    <form action="update_plant.php" method="POST">
        <input type="hidden" name="plant_id" value="<?php echo $plant_id; ?>">
        
        <label>Plant Name *</label>
        <input type="text" name="plant_name" class="form-control" value="<?php echo htmlspecialchars($plant['Plant_name']); ?>" required>
        
        <label>Category</label>
        <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($plant['Category'] ?? ''); ?>">
        
        <label>Price (৳) *</label>
        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $plant['Price']; ?>" required>
        
        <label>Stock Quantity *</label>
        <input type="number" name="stock_quantity" class="form-control" value="<?php echo $plant['Stock_quantity']; ?>" required>
        
        <button type="submit" class="btn">Update Plant Details</button>
    </form>
</div>
</body>
</html>