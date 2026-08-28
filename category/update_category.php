<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$cat_id = (int)($_GET['id'] ?? $_POST['category_id'] ?? 0);
$res = mysqli_query($conn, "SELECT * FROM category WHERE Category_ID = $cat_id");
$cat = mysqli_fetch_assoc($res);

if (!$cat) { die("Category not found."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname = mysqli_real_escape_string($conn, $_POST['category_name']);

    $sql = "UPDATE category SET Category_name = '$cname' WHERE Category_ID = $cat_id";
    if (mysqli_query($conn, $sql)) {
        logEmployeeAction($conn, 'CATEGORY_UPDATE', "Updated category #$cat_id to '$cname'", $cat_id);
        header("Location: show_category.php?msg=updated");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Category - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; }
        .card { max-width: 450px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #0284c7; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2>✏️ Modify Category #<?php echo $cat_id; ?></h2>
    <form method="POST">
        <input type="hidden" name="category_id" value="<?php echo $cat_id; ?>">
        <label>Category Name</label>
        <input type="text" name="category_name" class="form-control" value="<?php echo htmlspecialchars($cat['Category_name'] ?? ''); ?>" required>
        <button type="submit" class="btn">Update Category Name</button>
    </form>
</div>
</body>
</html>