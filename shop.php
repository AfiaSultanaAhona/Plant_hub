<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Turn off fatal SQL exceptions in PHP 8.1+
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = "";

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $plant_id   = trim($_POST['plant_id'] ?? '');
    $plant_name = trim($_POST['plant_name'] ?? 'Plant');
    $price      = (float)($_POST['plant_price'] ?? 0);

    if (!empty($plant_id)) {
        if (isset($_SESSION['cart'][$plant_id])) {
            $_SESSION['cart'][$plant_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$plant_id] = [
                'id' => $plant_id,
                'name' => $plant_name,
                'price' => $price,
                'quantity' => 1
            ];
        }
        $message = "🛒 " . htmlspecialchars($plant_name) . " added to cart! <a href='cart.php' style='color:#065f46; font-weight:bold;'>Go to Cart</a>";
    }
}

$cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
$selected_category = $_GET['category'] ?? 'All';

// 1. Map Suppliers
$suppliers_map = [];
$sup_res = mysqli_query($conn, "SELECT * FROM supplier");
if ($sup_res) {
    while ($srow = mysqli_fetch_assoc($sup_res)) {
        $srow_l = array_change_key_case($srow, CASE_LOWER);
        $s_id = $srow_l['supplier_id'] ?? $srow_l['id'] ?? null;
        $s_name = $srow_l['supplier_name'] ?? $srow_l['name'] ?? $srow_l['supplier'] ?? 'GreenFlora Nursery';
        if ($s_id) {
            $suppliers_map[$s_id] = $s_name;
        }
    }
}

// 2. Map Category IDs to Names
$category_map = [
    '1' => 'Indoor Plants',
    '2' => 'Outdoor Plants',
    '3' => 'Flowering Plants'
];

$cat_res = mysqli_query($conn, "SELECT * FROM category");
if ($cat_res) {
    while ($crow = mysqli_fetch_assoc($cat_res)) {
        $crow_l = array_change_key_case($crow, CASE_LOWER);
        $c_id = $crow_l['category_id'] ?? $crow_l['id'] ?? null;
        $c_name = $crow_l['category_name'] ?? $crow_l['name'] ?? $crow_l['title'] ?? null;
        if ($c_id && $c_name) {
            $category_map[(string)$c_id] = $c_name;
        }
    }
}

function resolveCategoryName($val, $map) {
    $v_str = trim((string)$val);
    if (isset($map[$v_str])) {
        return $map[$v_str];
    }
    return is_numeric($val) ? 'Category ' . $val : $val;
}

$categories = ['All', 'Indoor Plants', 'Outdoor Plants', 'Flowering Plants'];

// 3. Fetch Database Plants
$all_plants = [];
$has_outdoor_in_db = false;

$res = mysqli_query($conn, "SELECT * FROM plant");
if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $all_plants[] = $row;
        
        foreach ($row as $col => $val) {
            $col_l = strtolower($col);
            if (!empty($val) && (strpos($col_l, 'cat') !== false || strpos($col_l, 'type') !== false)) {
                $cat_label = resolveCategoryName($val, $category_map);
                if ($cat_label === 'Outdoor Plants') {
                    $has_outdoor_in_db = true;
                }
            }
        }
    }
}

// Fallback: If no Outdoor Plants in database, add sample ones with care and stock stats
if (!$has_outdoor_in_db) {
    $all_plants[] = ['Plant_ID' => '901', 'Plant_name' => 'Bougainvillea', 'Unit_price' => 220.00, 'Category_ID' => '2', 'supplier_id' => '1', 'Stock_quantity' => 12, 'sunlight' => 'Full Sun', 'watering' => 'Weekly', 'difficulty' => 'Easy'];
    $all_plants[] = ['Plant_ID' => '902', 'Plant_name' => 'Areca Palm Tree', 'Unit_price' => 450.00, 'Category_ID' => '2', 'supplier_id' => '1', 'Stock_quantity' => 5, 'sunlight' => 'Partial Shade', 'watering' => 'Twice Weekly', 'difficulty' => 'Moderate'];
    $all_plants[] = ['Plant_ID' => '903', 'Plant_name' => 'Red Hibiscus', 'Unit_price' => 180.00, 'Category_ID' => '2', 'supplier_id' => '1', 'Stock_quantity' => 0, 'sunlight' => 'Direct Sun', 'watering' => 'Daily', 'difficulty' => 'Easy'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Plants - Plant Hub</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .cart-link { background: #10b981; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: bold; }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 14px; border-radius: 8px; margin-bottom: 25px; text-align: center; }
        
        .category-wrapper { margin-bottom: 30px; }
        .category-title { font-size: 14px; font-weight: 700; color: #4b5563; text-transform: uppercase; margin-bottom: 10px; display: block; }
        .category-pills { display: flex; gap: 10px; flex-wrap: wrap; }
        .cat-pill { text-decoration: none; background: white; border: 1px solid #10b981; color: #10b981; padding: 8px 18px; border-radius: 20px; font-weight: 600; font-size: 14px; transition: all 0.2s ease; }
        .cat-pill:hover, .cat-pill.active { background-color: #10b981; color: white; }

        .plant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .plant-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e1e8ed; display: flex; flex-direction: column; justify-content: space-between; }
        .supplier-badge { color: #6b7280; font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block; }
        .cat-badge { background-color: #e0e7ff; color: #3730a3; font-size: 11px; font-weight: bold; padding: 4px 9px; border-radius: 6px; display: inline-block; margin-bottom: 10px; }
        .price { color: #10b981; font-size: 22px; font-weight: 800; margin: 6px 0 10px 0; }
        
        .stock-badge { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; margin-bottom: 10px; }
        .in-stock { background: #d1fae5; color: #059669; }
        .low-stock { background: #fef3c7; color: #d97706; }
        .out-stock { background: #ffe4e6; color: #e11d48; }

        .care-info { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 8px 10px; border-radius: 6px; font-size: 11px; color: #475569; margin-bottom: 12px; }
        .care-item { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .care-item:last-child { margin-bottom: 0; }

        .btn-add { width: 100%; background-color: #10b981; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; }
        .btn-add:hover { background-color: #059669; }
        .btn-disabled { background-color: #9ca3af; cursor: not-allowed; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">
    <div class="header-bar">
        <h2 style="color: #0a2318; margin: 0;">Available Plants 🌿</h2>
        <a href="cart.php" class="cart-link">View Cart (<?php echo $cart_count; ?>)</a>
    </div>

    <!-- Category Filter Bar -->
    <div class="category-wrapper">
        <span class="category-title">Select Category:</span>
        <div class="category-pills">
            <?php foreach ($categories as $cat): ?>
                <a href="shop.php?category=<?php echo urlencode($cat); ?>" 
                   class="cat-pill <?php echo ($selected_category === $cat) ? 'active' : ''; ?>">
                   <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="plant-grid">
        <?php
        $count_displayed = 0;

        foreach ($all_plants as $row) {
            $row_lower = array_change_key_case($row, CASE_LOWER);
            
            $plant_id   = $row_lower['plant_id'] ?? $row_lower['id'] ?? array_values($row)[0];
            $plant_name = $row_lower['plant_name'] ?? $row_lower['name'] ?? 'Plant';
            
            // Resolve Category Name
            $plant_cat = 'General';
            foreach ($row as $k => $v) {
                $kl = strtolower($k);
                if (!empty($v) && (strpos($kl, 'cat') !== false || strpos($kl, 'type') !== false)) {
                    $plant_cat = resolveCategoryName($v, $category_map);
                    break;
                }
            }

            // Category Filtering Logic
            if ($selected_category !== 'All' && strtolower(trim($plant_cat)) !== strtolower(trim($selected_category))) {
                continue;
            }

            $count_displayed++;

            // Resolve Supplier
            $sup_id = $row_lower['supplier_id'] ?? $row_lower['supplier'] ?? null;
            $supplier_name = 'GreenFlora Nursery';
            if ($sup_id && isset($suppliers_map[$sup_id])) {
                $supplier_name = $suppliers_map[$sup_id];
            } elseif (!empty($row_lower['supplier_name'])) {
                $supplier_name = $row_lower['supplier_name'];
            }

            // Resolve Price
            $raw_price = $row_lower['unit_price'] ?? $row_lower['price'] ?? $row_lower['amount'] ?? 100;

            // Resolve Stock & Care Details using DB column 'Stock_quantity'
            $stock = isset($row_lower['stock_quantity']) ? (int)$row_lower['stock_quantity'] : (int)($row_lower['stock'] ?? 0);
            $sunlight = htmlspecialchars($row_lower['sunlight'] ?? 'Indirect Light');
            $watering = htmlspecialchars($row_lower['watering'] ?? 'Weekly');
            $difficulty = htmlspecialchars($row_lower['difficulty'] ?? 'Easy');

            $stock_class = ($stock > 5) ? 'in-stock' : (($stock > 0) ? 'low-stock' : 'out-stock');
            $stock_label = ($stock > 0) ? "$stock in stock" : "Out of Stock";

            echo '
            <div class="plant-card">
                <div>
                    <span class="supplier-badge">🏭 Supplier: ' . htmlspecialchars($supplier_name) . '</span>
                    <span class="cat-badge">🏷️ ' . htmlspecialchars($plant_cat) . '</span>
                    <h3 style="margin: 5px 0; color: #143d2b;">' . htmlspecialchars($plant_name) . '</h3>
                    <div class="price">৳' . number_format((float)$raw_price, 2) . '</div>
                    
                    <!-- Live Stock Level -->
                    <span class="stock-badge ' . $stock_class . '">' . $stock_label . '</span>

                    <!-- Plant Care Details -->
                    <div class="care-info">
                        <div class="care-item"><span>☀️ Light:</span> <strong>' . $sunlight . '</strong></div>
                        <div class="care-item"><span>💧 Water:</span> <strong>' . $watering . '</strong></div>
                        <div class="care-item"><span>🌱 Level:</span> <strong>' . $difficulty . '</strong></div>
                    </div>
                </div>

                <form method="POST" action="shop.php?category=' . urlencode($selected_category) . '">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="plant_id" value="' . htmlspecialchars($plant_id) . '">
                    <input type="hidden" name="plant_name" value="' . htmlspecialchars($plant_name) . '">
                    <input type="hidden" name="plant_price" value="' . htmlspecialchars($raw_price) . '">';

            if ($stock > 0) {
                echo '<button type="submit" class="btn-add">Add to Cart 🛒</button>';
            } else {
                echo '<button type="button" class="btn-add btn-disabled" disabled>Sold Out</button>';
            }

            echo '
                </form>
            </div>';
        }
        ?>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>