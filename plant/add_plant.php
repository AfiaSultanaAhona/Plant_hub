<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$msg = "";
$error = "";

// Fetch active categories for the select dropdown (ordered by ID)
$categories = mysqli_query($conn, "SELECT * FROM category ORDER BY Category_ID ASC");

// Handle Add New Plant Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plant'])) {
    $plant_name = trim(mysqli_real_escape_string($conn, $_POST['plant_name']));
    $cat_id     = (int)$_POST['category_id'];
    $price      = (float)$_POST['price'];
    $stock      = (int)$_POST['stock_quantity'];

    if (!empty($plant_name) && $price >= 0 && $stock >= 0) {
        // Calculate the next available Plant_ID dynamically
        $max_res = mysqli_query($conn, "SELECT MAX(Plant_ID) AS max_id FROM plant");
        $max_row = mysqli_fetch_assoc($max_res);
        $next_plant_id = ((int)($max_row['max_id'] ?? -1)) + 1;

        // Insert new plant record
        $insert_sql = "INSERT INTO plant (Plant_ID, Plant_name, Category_ID, Price, Stock_quantity) 
                       VALUES ($next_plant_id, '$plant_name', $cat_id, $price, $stock)";

        if (mysqli_query($conn, $insert_sql)) {
            // Optional Audit Log Record
            $employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 'EMP';
            @mysqli_query($conn, "INSERT INTO stock_audit (Plant_ID, Employee_ID, Action, Quantity, Timestamp) 
                                  VALUES ($next_plant_id, '$employee_id', 'ADD_NEW_PLANT', $stock, NOW())");

            header("Location: employee_dashboard.php?view=inventory&msg=added_new");
            exit;
        } else {
            $error = "Error adding plant: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all fields with valid details.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; padding: 40px; margin: 0; }
        .card { max-width: 480px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 14px; color: #334155; }
        input, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; font-size: 15px; }
        .btn-submit:hover { background: #059669; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #0284c7; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <a href="employee_dashboard.php?view=inventory" class="back-link">← Back to Inventory</a>
    <h2>🌱 Add New Plant</h2>

    <?php if ($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="add_plant.php">
        <div class="form-group">
            <label>Plant Name</label>
            <input type="text" name="plant_name" placeholder="e.g. Snake Plant" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <option value="" disabled selected>-- Select Category --</option>
                <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                    <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $c['Category_ID']; ?>">
                            #<?php echo $c['Category_ID']; ?> - <?php echo htmlspecialchars($c['Category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="0">Default Category (#0)</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Price (৳)</label>
            <input type="number" step="0.01" name="price" placeholder="0.00" required>
        </div>
        <div class="form-group">
            <label>Initial Stock Quantity</label>
            <input type="number" name="stock_quantity" min="0" placeholder="e.g. 10" required>
        </div>
        <button type="submit" name="add_plant" class="btn-submit">Add Plant to Inventory</button>
    </form>
</div>

</body>
</html>