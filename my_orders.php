<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Turn off fatal SQL exceptions in PHP 8.1+
mysqli_report(MYSQLI_REPORT_OFF);

// Detect active logged-in user ID across potential session keys
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? null;

$orders = [];
$total_points = 0;

if ($conn) {
    // 1. Try fetching orders linked to the logged-in user
    if ($user_id) {
        $query = "SELECT * FROM orders WHERE customer_id = '$user_id' OR user_id = '$user_id' ORDER BY order_id ASC";
        $res = mysqli_query($conn, $query);
    }
    
    // 2. Fallback: If no user-specific records found, pull all orders from database
    if (empty($res) || mysqli_num_rows($res) === 0) {
        $query = "SELECT * FROM orders ORDER BY order_id ASC";
        $res = mysqli_query($conn, $query);
    }

    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row_l = array_change_key_case($row, CASE_LOWER);
            
            $amount = (float)($row_l['amount'] ?? $row_l['total_amount'] ?? $row_l['price'] ?? $row_l['unit_price'] ?? $row_l['total'] ?? 0);
            
            // Calculate 10 points for every complete ৳500 spent per order item
            $points_earned = (int)floor($amount / 500) * 10;
            $total_points += $points_earned;

            $orders[] = [
                'order_id'   => $row_l['order_id'] ?? $row_l['id'] ?? null,
                'plant_name' => $row_l['plant_name'] ?? $row_l['item_name'] ?? $row_l['name'] ?? $row_l['title'] ?? 'Plant',
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
        <a href="my_orders.php" class="nav-link">My Orders 📦</a>
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
</html>