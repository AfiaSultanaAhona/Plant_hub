<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Extract Customer ID from active session
$raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? null;
$customer_id = (int) preg_replace('/[^0-9]/', '', (string)$raw_id);
if ($customer_id <= 0) {
    $customer_id = 1; // Fallback customer ID for local testing
}

// 2. Fetch Customer Order History with Joined Plant Information
$query = "SELECT o.Order_id, o.Amount, o.Exchange_status, o.points_redeemed, p.Plant_name, p.Price 
          FROM orders o 
          LEFT JOIN plant p ON o.Plant_id = p.Plant_ID 
          WHERE o.Customer_id = '$customer_id' 
          ORDER BY o.Order_id DESC";

$result = mysqli_query($conn, $query);
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
        .status-badge { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; }
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
                        <tr>
                            <td><strong>#<?php echo $row['Order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['Plant_name'] ?? 'Plant Item'); ?></td>
                            <td style="color: #10b981; font-weight: bold;">৳<?php echo number_format($row['Amount'], 2); ?></td>
                            <td><?php echo (int)($row['points_redeemed'] ?? 0); ?> PTS</td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($row['Exchange_status'] ?? 'Completed'); ?></span></td>
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