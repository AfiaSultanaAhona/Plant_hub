<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$msg = ""; $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname = mysqli_real_escape_string($conn, $_POST['category_name'] ?? '');

    if (!empty($cname)) {
        $sql = "INSERT INTO category (Category_name) VALUES ('$cname')";
        if (mysqli_query($conn, $sql)) {
            $cat_id = mysqli_insert_id($conn);
            logEmployeeAction($conn, 'CATEGORY_ADD', "Created new category '$cname'", $cat_id);
            header("Location: show_category.php?msg=added");
            exit;
        } else { $error = mysqli_error($conn); }
    } else { $error = "Category name cannot be empty."; }
}
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
    <h2>📁 Add Category</h2>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
    <form method="POST">
        <label>Category Name *</label>
        <input type="text" name="category_name" class="form-control" required placeholder="e.g. Indoor Plants">
        <button type="submit" class="btn">Save Category & Log</button>
    </form>
</div>
</body>
</html>