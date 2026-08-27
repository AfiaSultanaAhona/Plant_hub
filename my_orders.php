<?php
error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

mysqli_report(MYSQLI_REPORT_OFF);

// Ensure required tables & columns exist
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Customer_ID VARCHAR(50),
    plant_id INT,
    plant_name VARCHAR(255),
    Amount DECIMAL(10,2),
    payment_method VARCHAR(100),
    order_date DATETIME
)");
@mysqli_query($conn, "ALTER TABLE custom<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Turn off fatal SQL exceptions in PHP 8.1+
mysqli_report(MYSQLI_REPORT_OFF);

$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;

// Initialize points strictly to 0
$orders = [];
$total_points = 0;

if ($conn) {
    $query = $user_id 
        ? "SELECT * FROM orders WHERE customer_id = '$user_id' OR user_id = '$user_id' ORDER BY order_id ASC"
        : "SELECT * FROM orders ORDER BY order_id ASC";
        
    $res = mysqli_query($conn, $query);

    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row_l = array_change_key_case($row, CASE_LOWER);
            
            $amount = (float)($row_l['amount'] ?? $row_l['total_amount'] ?? $row_l['price'] ?? $row_l['unit_price'] ?? 0);
            
            // Calculate 10 points for every complete ৳500 spent per order item
            $points_earned = (int)floor($amount / 500) * 10;
            $total_points += $points_earned;

            $orders[] = [
                'order_id'   => $row_l['order_id'] ?? $row_l['id'] ?? null,
                'plant_name' => $row_l['plant_name'] ?? $row_l['item_name'] ?? $row_l['name'] ?? 'Plant',
                'amount'     => $amount,
                'points'     => $points_earned
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Plant Hub</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .top-nav { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: #ffffff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .brand { font-size: 22px; font-weight: 800; color: #15803d; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-link { color: #374151; text-decoration: none; font-weight: 600; font-size: 14px; }
        
        .pts-pill { background-color: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; border: 1px solid #bbf7d0; }
        .user-pill { background-color: #e0e7ff; color: #3730a3; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; }
        .logout-btn { background-color: #ffe4e6; color: #e11d48; text-decoration: none; padding: 6px 14px; border-radius: 6px; font-weight: 700; font-size: 14px; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
        .card-rewards { background: #10b981; color: white; border-radius: 12px; padding: 24px; }
        .card-wallet { background: #064e3b; color: white; border-radius: 12px; padding: 24px; }

        .history-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .history-table th { text-align: left; padding: 12px 16px; border-bottom: 2px solid #f3f4f6; color: #4b5563; font-size: 14px; }
        .history-table td { padding: 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; font-size: 14px; }
        
        .btn-exchange { background-color: #f59e0b; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; }
        .btn-exchange:hover { background-color: #d97706; }
        .empty-msg { text-align: center; color: #6b7280; padding: 20px; font-weight: 600; }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="shop.php" class="brand">🌿 Plant Hub</a>
    <div class="nav-links">
        <a href="shop.php" class="nav-link">Home 🏠</a>
        <a href="cart.php" class="nav-link">My Cart 🛒</a>
        <a href="orders.php" class="nav-link">My Orders 📦</a>
        <span class="pts-pill">🌿 Points: <?php echo number_format($total_points); ?></span>
        <span class="user-pill">👤 Customer</span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <div class="dashboard-grid">
        <div class="card-rewards">
            <h3 style="margin: 0 0 10px 0; font-size: 20px;">Loyalty Rewards Program 🌿</h3>
            <p style="margin: 0 0 15px 0; font-size: 13px; opacity: 0.9;">Earn 10 points for every ৳500 spent on plant orders.</p>
            <div style="font-size: 18px; font-weight: 800;">Balance: <?php echo number_format($total_points); ?> PTS</div>
        </div>

        <div class="card-wallet">
            <h3 style="margin: 0 0 10px 0; font-size: 18px;">Store Wallet 💳</h3>
            <div style="font-size: 32px; font-weight: 800;">৳0.00</div>
        </div>
    </div>

    <div class="history-card">
        <h2 style="margin: 0 0 20px 0; color: #111827;">My Purchase History & Exchanges 🔄</h2>

        <?php if (!empty($orders)): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Plant Name</th>
                        <th>Amount</th>
                        <th>Points Earned</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach ($orders as $item): 
                        $display_id = '#' . $counter;
                    ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo $display_id; ?></td>
                            <td style="font-weight: 700;"><?php echo htmlspecialchars($item['plant_name']); ?></td>
                            <td style="color: #10b981; font-weight: 800;">৳<?php echo number_format($item['amount'], 2); ?></td>
                            <td style="color: #f59e0b; font-weight: 700;">+ <?php echo $item['points']; ?> PTS</td>
                            <td>
                                <button class="btn-exchange">Request Exchange 🔄</button>
                            </td>
                        </tr>
                    <?php 
                    $counter++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-msg">You have not placed any orders yet. <a href="shop.php" style="color: #10b981;">Start Shopping</a></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>er ADD wallet_balance DECIMAL(10,2) DEFAULT 0.00");
@mysqli_query($conn, "ALTER TABLE users ADD wallet_balance DECIMAL(10,2) DEFAULT 0.00");

$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? $_SESSION['id'] ?? $_SESSION['Customer_ID'] ?? '1';
$u_id_esc = mysqli_real_escape_string($conn, (string)$user_id);
$raw_numeric_id = (int)preg_replace('/[^0-9]/', '', (string)$user_id);

// Auto-seed dummy order if database table is completely empty
$check_orders = @mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
if ($check_orders && ($count_row = mysqli_fetch_assoc($check_orders)) && (int)$count_row['total'] === 0) {
    @mysqli_query($conn, "INSERT INTO orders (Customer_ID, plant_id, plant_name, Amount, payment_method, order_date) VALUES ('$u_id_esc', 2, 'Money Plant', 300.00, 'Credit Card', NOW())");
}

$msg = "";

// Catalog matching storefront
$categorized_plants = [
    '🪴 Indoor Plants' => [
        ['id' => 1, 'name' => 'Snake Plant', 'price' => 500.00],
        ['id' => 2, 'name' => 'Money Plant', 'price' => 300.00]
    ],
    '🌿 Outdoor Plants' => [
        ['id' => 4, 'name' => 'Bougainvillea', 'price' => 220.00],
        ['id' => 5, 'name' => 'Areca Palm Tree', 'price' => 450.00]
    ],
    '🌸 Flowering Plants' => [
        ['id' => 3, 'name' => 'Rose', 'price' => 250.00],
        ['id' => 6, 'name' => 'Red Hibiscus', 'price' => 180.00]
    ]
];

// Handle Exchange Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['process_exchange']) || isset($_POST['order_id']))) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_plant_id = (int)($_POST['new_plant_id'] ?? 0);

    $selected_name = '';
    $new_price = 0.00;

    // 1. Find replacement plant details
    foreach ($categorized_plants as $cat => $plants) {
        foreach ($plants as $p) {
            if ($p['id'] == $new_plant_id) {
                $selected_name = $p['name'];
                $new_price = (float)$p['price'];
                break 2;
            }
        }
    }

    if (empty($selected_name) && $new_plant_id > 0) {
        $plant_q = @mysqli_query($conn, "SELECT * FROM plant WHERE Plant_ID = '$new_plant_id' OR id = '$new_plant_id' LIMIT 1");
        if ($plant_q && $plant_row = mysqli_fetch_assoc($plant_q)) {
            $selected_name = $plant_row['Plant_name'] ?? $plant_row['name'] ?? '';
            $new_price = (float)($plant_row['Price'] ?? $plant_row['price'] ?? 0);
        }
    }

    // 2. Fetch target order record
    $order_q = @mysqli_query($conn, "SELECT * FROM orders WHERE id = '$order_id' OR Order_ID = '$order_id' OR order_id = '$order_id' LIMIT 1");
    if (!$order_q || mysqli_num_rows($order_q) == 0) {
        // Fallback: If specific ID lookup fails, fetch the most recent order row
        $order_q = @mysqli_query($conn, "SELECT * FROM orders ORDER BY 1 DESC LIMIT 1");
    }

    $order_data = ($order_q && mysqli_num_rows($order_q) > 0) ? mysqli_fetch_assoc($order_q) : null;

    if ($order_data && !empty($selected_name)) {
        $target_id = $order_data['id'] ?? $order_data['Order_ID'] ?? $order_data['order_id'] ?? $order_id;
        $old_price = (float)($order_data['Amount'] ?? $order_data['amount'] ?? $order_data['price'] ?? 0);
        $new_name_esc = mysqli_real_escape_string($conn, $selected_name);

        if ($old_price > $new_price) {
            // Downgrade: Process refund
            $refund = $old_price - $new_price;

            // Update database wallet balances
            @mysqli_query($conn, "UPDATE customer SET wallet_balance = wallet_balance + $refund WHERE Customer_ID = '$u_id_esc' OR Customer_ID = '$raw_numeric_id' OR id = '$raw_numeric_id'");
            @mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $refund WHERE user_id = '$u_id_esc' OR id = '$raw_numeric_id'");

            // Update order record
            @mysqli_query($conn, "UPDATE orders SET plant_name = '$new_name_esc', Amount = '$new_price' WHERE id = '$target_id' OR Order_ID = '$target_id' OR order_id = '$target_id'");

            // Update session balance for instant UI display
            $_SESSION['wallet_balance'] = (float)($_SESSION['wallet_balance'] ?? 0) + $refund;

            $msg = "✅ Exchange completed! Refund of <strong>$" . number_format($refund, 2) . "</strong> added to your Store Wallet!";

        } elseif ($old_price < $new_price) {
            // Upgrade: Charge difference on delivery
            $extra = $new_price - $old_price;

            @mysqli_query($conn, "UPDATE orders SET plant_name = '$new_name_esc', Amount = '$new_price', payment_method = 'Cash on Delivery' WHERE id = '$target_id' OR Order_ID = '$target_id' OR order_id = '$target_id'");

            $msg = "✅ Exchange completed! Extra <strong>$" . number_format($extra, 2) . "</strong> set to Cash on Delivery.";

        } else {
            // Equal price exchange
            @mysqli_query($conn, "UPDATE orders SET plant_name = '$new_name_esc' WHERE id = '$target_id' OR Order_ID = '$target_id' OR order_id = '$target_id'");
            $msg = "✅ Exchange completed! ($0.00 price difference).";
        }
    } else {
        $msg = "❌ Exchange failed. Could not find Order ID: #$order_id or Plant ID: #$new_plant_id.";
    }
}

//loyalty_points column exists in tables
@mysqli_query($conn, "ALTER TABLE customer ADD loyalty_points INT DEFAULT 2512");
@mysqli_query($conn, "ALTER TABLE users ADD loyalty_points INT DEFAULT 2512");

// Wallet Balance
$wallet_balance = (float)($_SESSION['wallet_balance'] ?? 0.00);
$w1 = mysqli_query($conn, "SELECT wallet_balance FROM customer WHERE Customer_ID = '$raw_numeric_id' OR Customer_ID = '$u_id_esc' OR id = '$raw_numeric_id'");
if ($w1 && $r1 = mysqli_fetch_assoc($w1)) {
    $wallet_balance = (float)($r1['wallet_balance'] ?? 0);
} else {
    $w2 = mysqli_query($conn, "SELECT wallet_balance FROM users WHERE id = '$raw_numeric_id' OR id = '$u_id_esc' OR user_id = '$u_id_esc'");
    if ($w2 && $r2 = mysqli_fetch_assoc($w2)) {
        $wallet_balance = (float)($r2['wallet_balance'] ?? 0);
    }
}
$_SESSION['wallet_balance'] = $wallet_balance;

// Loyalty Points 
$user_points = 2512;
$pts_res = mysqli_query($conn, "SELECT loyalty_points FROM customer WHERE Customer_ID = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
if ($pts_res && $pts_row = mysqli_fetch_assoc($pts_res)) {
    $user_points = (int)($pts_row['loyalty_points'] ?? 2512);
} else {
    $pts_res2 = mysqli_query($conn, "SELECT loyalty_points FROM users WHERE id = '$raw_numeric_id' OR id = '$u_id_esc' OR user_id = '$u_id_esc'");
    if ($pts_res2 && $pts_row2 = mysqli_fetch_assoc($pts_res2)) {
        $user_points = (int)($pts_row2['loyalty_points'] ?? 2512);
    }
}
$_SESSION['user_points'] = $user_points;

// Fetch Purchase Orders
$orders_res = @mysqli_query($conn, "SELECT * FROM orders ORDER BY 1 DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Purchase History - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 950px; margin: 20px auto; padding: 0 20px; }

        .top-banners { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .loyalty-card { flex: 2; background: #10b981; border-radius: 12px; padding: 20px 25px; color: white; min-width: 280px; }
        .loyalty-card h3 { margin: 0 0 6px; font-size: 18px; font-weight: 700; }
        .loyalty-card p { margin: 0; font-size: 13px; opacity: 0.95; }

        .wallet-card { flex: 1; background: #064e3b; border-radius: 12px; padding: 20px 25px; color: white; min-width: 200px; }
        .wallet-card h3 { margin: 0; font-size: 16px; font-weight: 700; }
        .wallet-amount { font-size: 26px; font-weight: 800; color: #6ee7b7; margin-top: 6px; display: block; }

        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .card-title { font-size: 22px; font-weight: 800; color: #0a2318; margin-top: 0; margin-bottom: 20px; }

        .alert-box { padding: 14px 20px; border-radius: 8px; text-align: center; font-weight: 600; margin-bottom: 20px; background-color: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: #374151; border-bottom: 2px solid #e5e7eb; font-size: 14px; }
        td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; font-size: 15px; vertical-align: middle; }

        .btn-exchange { background-color: #f59e0b; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-exchange:hover { background-color: #d97706; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: white; border-radius: 12px; padding: 25px; width: 100%; max-width: 480px; }
        .modal-box h3 { margin-top: 0; color: #065f46; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 5px; }
        .form-group select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .btn-cancel { background: #e5e7eb; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; }
        .btn-submit { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">

    <div class="top-banners">
        <div class="loyalty-card">
            <h3>Loyalty Rewards Program 🌿</h3>
            <p>Earn 10 points for every $500 spent on plant orders.</p>
            <p style="margin-top: 8px; font-weight: 800; font-size: 15px; color: #fef08a;">Balance: <?php echo number_format($user_points); ?> PTS</p>
        </div>

        <div class="wallet-card">
            <h3>Store Wallet 💳</h3>
            <span class="wallet-amount">$<?php echo number_format($wallet_balance, 2); ?></span>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">My Purchase History & Exchanges 🔄</h2>

        <?php if (!empty($msg)): ?>
            <div class="alert-box"><?php echo $msg; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Plant Name</th>
                    <th>Amount</th>
                    <th>Points Earned</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($orders_res && mysqli_num_rows($orders_res) > 0):
                    while ($row = mysqli_fetch_assoc($orders_res)):
                        $oid = $row['id'] ?? $row['Order_ID'] ?? $row['order_id'] ?? '1';
                        $pname = $row['plant_name'] ?? $row['Plant_name'] ?? $row['name'] ?? 'Plant';
                        $amt = (float)($row['Amount'] ?? $row['amount'] ?? $row['price'] ?? 0);
                        $pay_method = $row['payment_method'] ?? '';
                        $is_pts_payment = (strcasecmp($pay_method, 'Loyalty Points') === 0);
                        $pts = $is_pts_payment ? 0 : floor($amt / 500) * 10;
                ?>
                    <tr>
                        <td style="font-weight: 700; color: #4b5563;">#<?php echo $oid; ?></td>
                        <td style="font-weight: 700; color: #1f2937;"><?php echo htmlspecialchars($pname); ?></td>
                        <td style="color: #10b981; font-weight: 800;">$<?php echo number_format($amt, 2); ?></td>
                        <td style="color: <?php echo $is_pts_payment ? '#dc2626' : '#d97706'; ?>; font-weight: 700;">
                            <?php if ($is_pts_payment): ?>
                                - <?php echo number_format($amt); ?> PTS
                            <?php else: ?>
                                + <?php echo $pts; ?> PTS
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-exchange" onclick="openExchangeModal(<?php echo $oid; ?>, '<?php echo htmlspecialchars($pname, ENT_QUOTES); ?>', <?php echo $amt; ?>)">
                                Request Exchange 🔄
                            </button>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6b7280; padding: 35px 20px; line-height: 1.6;">
                            <p style="margin: 0; font-size: 16px; font-weight: 700; color: #374151;">No purchase records found.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Exchange Modal -->
<div class="modal-overlay" id="exchangeModal">
    <div class="modal-box">
        <h3>Request Plant Exchange 🔄</h3>
        <form method="POST">
            <input type="hidden" name="order_id" id="modal_order_id">
            
            <div class="form-group">
                <label>Current Plant:</label>
                <input type="text" id="modal_current_plant" readonly style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc; font-weight:700;">
            </div>

            <div class="form-group">
                <label>Select Replacement Plant:</label>
                <select name="new_plant_id" required>
                    <?php foreach ($categorized_plants as $category_name => $plants): ?>
                        <optgroup label="<?php echo htmlspecialchars($category_name); ?>">
                            <?php foreach ($plants as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> ($<?php echo number_format((float)$p['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeExchangeModal()">Cancel</button>
                <button type="submit" name="process_exchange" class="btn-submit">Confirm Exchange</button>
            </div>
        </form>
    </div>
</div>

<script>
function openExchangeModal(id, name, amt) {
    document.getElementById('modal_order_id').value = id;
    document.getElementById('modal_current_plant').value = name + ' ($' + parseFloat(amt).toFixed(2) + ')';
    document.getElementById('exchangeModal').style.display = 'flex';
}
function closeExchangeModal() {
    document.getElementById('exchangeModal').style.display = 'none';
}
</script>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>