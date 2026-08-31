<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "DBconnect.php";

// Only a logged-in customer can access purchase history.
if (($_SESSION['role'] ?? '') !== 'customer' || (int)($_SESSION['customer_id'] ?? 0) <= 0) {
    header("Location: login.php");
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];

$query = "SELECT
            o.Order_id,
            o.Customer_id,
            o.Plant_id,
            o.Amount,
            o.Order_date,
            o.Exchange_status,
            o.points_redeemed,
            p.Plant_name,
            p.Unit_price
          FROM orders o
          LEFT JOIN plant p ON o.Plant_id = p.Plant_ID
          WHERE o.Customer_id = ?
            AND o.Amount > 0
          ORDER BY o.Order_date DESC, o.Order_id DESC";

$stmt = mysqli_prepare($conn, $query);
$result = false;

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History - Plant Hub</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:#eef7f2;margin:0;color:#1e293b}
        .container{max-width:1100px;margin:30px auto;padding:0 20px}
        .card{background:#fff;padding:25px;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,.05);border:1px solid #e2e8f0}
        h2{margin-top:0;color:#065f46}.orders-table{width:100%;border-collapse:collapse;margin-top:20px}
        .orders-table th,.orders-table td{padding:13px 12px;text-align:left;border-bottom:1px solid #e2e8f0}
        .orders-table th{background:#f8fafc;color:#334155;font-weight:700}.orders-table tr:hover{background:#f8fffb}
        .status-badge,.exchange-btn,.disabled-btn{display:inline-block;border-radius:7px;font-size:13px;font-weight:700;padding:8px 12px}
        .status-none{background:#f1f5f9;color:#475569;border-radius:12px;padding:5px 10px}.status-pending{background:#fef3c7;color:#92400e;border-radius:12px;padding:5px 10px}.status-completed{background:#dcfce7;color:#166534;border-radius:12px;padding:5px 10px}
        .exchange-btn{background:#0284c7;color:#fff;text-decoration:none}.exchange-btn:hover{background:#0369a1}
        .disabled-btn{background:#e2e8f0;color:#64748b}.amount{color:#10b981;font-weight:bold}.empty{text-align:center;color:#64748b;padding:30px}
        @media(max-width:800px){.orders-table{font-size:13px}.orders-table th,.orders-table td{padding:9px 7px}}
    </style>
</head>
<body>
<?php if (file_exists("header.php")) include("header.php"); ?>
<div class="container">
    <div class="card">
        <h2>Purchase History 📦</h2>
        <p style="color:#64748b;">Here are the purchases made from your account. You can request an exchange for an eligible purchase.</p>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th><th>Plant Name</th><th>Amount Paid</th><th>Points Redeemed</th><th>Exchange Status</th><th>Exchange</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    $order_id = (int)$row['Order_id'];
                    $plant_id = (int)$row['Plant_id'];
                    $plant_name = $row['Plant_name'] ?? 'Unknown Plant';
                    $amount = (float)$row['Amount'];
                    $points = (int)$row['points_redeemed'];
                    $status = trim((string)($row['Exchange_status'] ?? 'None')) ?: 'None';
                    $status_lower = strtolower($status);
                    ?>
                    <tr>
                        <td><strong>#<?php echo $order_id; ?></strong></td>
                        <td>🌱 <?php echo htmlspecialchars($plant_name); ?></td>
                        <td class="amount">৳<?php echo number_format($amount, 2); ?></td>
                        <td><?php echo $points; ?> PTS</td>
                        <td>
                            <?php if ($status_lower === 'pending'): ?>
                                <span class="status-badge status-pending">Exchange Pending</span>
                            <?php elseif ($status_lower === 'completed'): ?>
                                <span class="status-badge status-completed">Exchange Completed</span>
                            <?php else: ?>
                                <span class="status-badge status-none">No Exchange</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status_lower === 'pending'): ?>
                                <span class="disabled-btn">⏳ Pending</span>
                            <?php elseif ($status_lower === 'completed'): ?>
                                <span class="disabled-btn">✓ Done</span>
                            <?php elseif ($plant_id > 0): ?>
                                <a class="exchange-btn" href="process_exchanges.php?order_id=<?php echo $order_id; ?>">🔄 Exchange</a>
                            <?php else: ?>
                                <span class="disabled-btn">Not Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="empty">No purchase history found.<br><br><a href="shop.php" style="color:#065f46;font-weight:bold;">Start Shopping 🌱</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if (file_exists("footer.php")) include("footer.php"); ?>
</body>
</html>
