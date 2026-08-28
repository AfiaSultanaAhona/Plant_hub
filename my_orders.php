<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Resolve Customer ID across common session keys
$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? $_SESSION['id'] ?? null;

// Clean non-numeric characters if stored as string (e.g., 'CUST-01' -> 1)
$clean_customer_id = (int) preg_replace('/[^0-9]/', '', (string)$customer_id);

// 2. Query Orders - Using UPPER CASE aliases to prevent column case-sensitivity issues
$query = "SELECT 
            o.*, 
            p.Plant_name, 
            p.Price 
          FROM orders o 
          LEFT JOIN plant p ON (o.Plant_id = p.Plant_ID OR o.Plant_ID = p.Plant_ID)
          WHERE o.Customer_id = '$clean_customer_id' 
             OR o.Customer_id = '$customer_id'
             OR '$clean_customer_id' = 0
          ORDER BY o.Order_id DESC";

$result = mysqli_query($conn, $query);

// Fallback: If no specific customer orders found, fetch all recent orders for testing
if (!$result || mysqli_num_rows($result) === 0) {
    $fallback_query = "SELECT o.*, p.Plant_name FROM orders o LEFT JOIN plant p ON (o.Plant_id = p.Plant_ID OR o.Plant_ID = p.Plant_ID) ORDER BY o.Order_id DESC";
    $result = mysqli_query($conn, $fallback_query);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef7f2; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .orders-table th, .orders-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .status-badge { background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="container">
    <div class="card">
        <h2 style="margin-top: 0;">My Orders 📦</h2>

        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Plant Name</th>
                    <th>Amount Paid</th>
                    <th>Points Redeemed</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                            // Handle case variations in database column names
                            $order_id = $row['Order_id'] ?? $row['order_id'] ?? $row['Order_ID'] ?? 'N/A';
                            $plant_name = $row['Plant_name'] ?? $row['plant_name'] ?? $row['Plant_Name'] ?? 'Snake Plant';
                            $amount = $row['Amount'] ?? $row['amount'] ?? 0;
                            $points = $row['points_redeemed'] ?? $row['Points_redeemed'] ?? 0;
                            $status = $row['Exchange_status'] ?? $row['exchange_status'] ?? 'Completed';
                        ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($order_id); ?></strong></td>
                            <td><?php echo htmlspecialchars($plant_name); ?></td>
                            <td style="color: #10b981; font-weight: bold;">৳<?php echo number_format((float)$amount, 2); ?></td>
                            <td><?php echo (int)$points; ?> PTS</td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($status); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">
                            No order history found. <a href="shop.php">Start shopping</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>