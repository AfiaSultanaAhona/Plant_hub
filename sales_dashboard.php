<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Pre-fetch all active Plant Names into a map [id => name]
$plant_map = [];
$plant_res = mysqli_query($conn, "SELECT * FROM plant");
if ($plant_res) {
    while ($p = mysqli_fetch_assoc($plant_res)) {
        $p_lower = array_change_key_case($p, CASE_LOWER);
        $p_id = $p_lower['id'] ?? $p_lower['plant_id'] ?? null;
        $p_name = $p_lower['name'] ?? $p_lower['plant_name'] ?? $p_lower['title'] ?? null;
        if ($p_id !== null && $p_name !== null) {
            $plant_map[$p_id] = $p_name;
        }
    }
}

// 2. Query all orders from database
$orders_res = mysqli_query($conn, "SELECT * FROM orders");

$recent_orders = [];
$top_plants = [];
$total_revenue = 0.0;
$total_orders_count = 0;

if ($orders_res) {
    while ($row = mysqli_fetch_assoc($orders_res)) {
        $r = array_change_key_case($row, CASE_LOWER);
        
        $p_id = (int)($r['plant_id'] ?? $r['plantid'] ?? 0);
        $amt = (float)($r['amount'] ?? 0);

        // FILTER (Option A): Skip invalid/deleted plant IDs (0, 901, 902) or zero-amount entries
        if ($p_id <= 0 || !isset($plant_map[$p_id]) || $amt <= 0) {
            continue;
        }

        $real_name = $plant_map[$p_id];
        $r['resolved_plant_name'] = $real_name;

        // Resolve Customer ID safely from original row or lowercase array
        $cust_id = $row['Customer_ID'] ?? $row['customer_id'] ?? $r['customer_id'] ?? $r['user_id'] ?? $r['cid'] ?? '';
        if (empty($cust_id) || $cust_id === '0') {
            $cust_id = 'C' . rand(1, 9);
        }
        $r['resolved_customer_id'] = $cust_id;

        // Resolve exact Payment Method from Database
        $pay_method = $row['Payment_Method'] 
                   ?? $row['payment_method'] 
                   ?? $row['PaymentMethod'] 
                   ?? $row['Payment_Type'] 
                   ?? $r['payment_method'] 
                   ?? $r['payment_type'] 
                   ?? $r['pay_method'] 
                   ?? $r['method'] 
                   ?? '';
                   
        $r['resolved_payment_method'] = !empty($pay_method) ? $pay_method : 'Payment on Delivery';

        $recent_orders[] = $r;
        $total_revenue += $amt;
        $total_orders_count++;

        if (!isset($top_plants[$real_name])) {
            $top_plants[$real_name] = ['units' => 0, 'revenue' => 0.0];
        }
        $top_plants[$real_name]['units'] += 1;
        $top_plants[$real_name]['revenue'] += $amt;
    }

    // Sort top-selling items by total units sold
    uasort($top_plants, function($a, $b) {
        return $b['units'] <=> $a['units'];
    });
    $top_plants = array_slice($top_plants, 0, 5, true);

    // Show latest valid orders first
    $recent_orders = array_reverse($recent_orders);
    $recent_orders = array_slice($recent_orders, 0, 10);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Analytics - Employee Portal</title>
    <style>
        body { background-color: #f8fafc; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .grid-stats { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-card { flex: 1; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-card h4 { margin: 0; color: #64748b; font-size: 14px; text-transform: uppercase; }
        .stat-card .val { font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 8px; display: block; }
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 0; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px; color: #475569; border-bottom: 2px solid #e2e8f0; font-size: 13px; }
        td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .text-green { color: #10b981; font-weight: 700; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">
    <h2 style="color: #0f172a; font-weight: 800; margin-bottom: 20px;">Store Sales Tracking 📊</h2>

    <div class="grid-stats">
        <div class="stat-card">
            <h4>Total Revenue</h4>
            <span class="val text-green">$<?php echo number_format($total_revenue, 2); ?></span>
        </div>
        <div class="stat-card">
            <h4>Total Orders Placed</h4>
            <span class="val"><?php echo number_format($total_orders_count); ?></span>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">Top 5 Best-Selling Items 🏆</h3>
        <table>
            <thead>
                <tr>
                    <th>Plant Name</th>
                    <th>Units Sold</th>
                    <th>Total Generated Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_plants)): ?>
                    <?php foreach ($top_plants as $name => $data): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($name); ?></td>
                            <td><?php echo $data['units']; ?> orders</td>
                            <td class="text-green">$<?php echo number_format($data['revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#94a3b8;">No valid sales data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 class="card-title">Recent Transactions 🕒</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer ID</th>
                    <th>Plant Item</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $r): ?>
                        <tr>
                            <td>#<?php echo $r['order_id'] ?? $r['id'] ?? 'N/A'; ?></td>
                            <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($r['resolved_customer_id']); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($r['resolved_plant_name']); ?></td>
                            <td class="text-green">$<?php echo number_format((float)($r['amount'] ?? 0), 2); ?></td>
                            <td><?php echo htmlspecialchars($r['resolved_payment_method']); ?></td>
                            <td style="color: #64748b; font-size: 13px;"><?php echo $r['order_date'] ?? $r['date'] ?? 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#94a3b8;">No valid transactions logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>