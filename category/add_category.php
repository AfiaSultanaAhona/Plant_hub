<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Category - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; }
        .card { max-width: 450px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #10b981; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2>📁 Add Plant Category</h2>
    <form action="insert_category.php" method="POST">
        <label>Category Name *</label>
        <input type="text" name="category_name" class="form-control" placeholder="e.g. Succulents" required>
        <button type="submit" class="btn">Save Category & Log</button>
    </form>
</div>
</body>
</html>