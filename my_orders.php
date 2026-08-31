<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

$raw_id =
    $_SESSION['customer_id']
    ?? $_SESSION['Customer_id']
    ?? $_SESSION['Customer_ID']
    ?? $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? null;

$clean_customer_id = (int)preg_replace(
    '/[^0-9]/',
    '',
    (string)$raw_id
);

if ($clean_customer_id <= 0) {
    header("Location: login.php");
    exit;
}

/*
 * One exchange belongs to one order.
 * This is why Order_ID is stored in exchange.
 */
$query = "
    SELECT
        o.*,
        p.Plant_name,
        p.Unit_price,
        e.exchange_id,
        e.status AS exchange_status,
        e.Exchange_value,
        e.payment_method,
        e.payment_status,
        e.adjustment_direction,
        e.Offered_plant_ID,
        e.Received_plant_ID
    FROM orders o
    LEFT JOIN plant p
        ON o.Plant_id = p.Plant_ID
    LEFT JOIN exchange e
        ON e.Order_ID = o.Order_id
       AND e.Customer_ID = o.Customer_id
    WHERE o.Customer_id = ?
    ORDER BY o.Order_id DESC, e.exchange_id DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $clean_customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Plant Hub</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#eef7f2;margin:0;color:#1e293b}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.card{background:white;padding:25px;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,.05);border:1px solid #e2e8f0;overflow-x:auto}
h2{margin-top:0;color:#065f46}
.orders-table{width:100%;border-collapse:collapse;margin-top:20px;min-width:1050px}
.orders-table th,.orders-table td{padding:13px 12px;text-align:left;border-bottom:1px solid #e2e8f0;vertical-align:top}
.orders-table th{background:#f8fafc;color:#334155;font-weight:700}
.orders-table tr:hover{background:#f8fffb}
.status-badge{display:inline-block;padding:5px 10px;border-radius:12px;font-size:12px;font-weight:bold}
.status-none{background:#f1f5f9;color:#475569}
.status-pending{background:#fef3c7;color:#92400e}
.status-approved{background:#dbeafe;color:#1d4ed8}
.status-completed{background:#dcfce7;color:#166534}
.exchange-btn{display:inline-block;background:#0284c7;color:white;text-decoration:none;padding:8px 13px;border-radius:7px;font-weight:700;font-size:13px}
.disabled-btn{display:inline-block;background:#e2e8f0;color:#64748b;padding:8px 13px;border-radius:7px;font-weight:600;font-size:13px}
.amount{color:#10b981;font-weight:bold}
.cod{color:#dc2626;font-weight:700}
.wallet{color:#059669;font-weight:700}
.approved-box{background:#eff6ff;color:#1d4ed8;padding:8px;border-radius:7px;font-size:13px}
.empty{text-align:center;color:#64748b;padding:30px}
@media(max-width:800px){
.orders-table{font-size:13px}
.orders-table th,.orders-table td{padding:9px 7px}
}
</style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">
<div class="card">

<h2>My Orders 📦</h2>

<p style="color:#64748b;">
View your purchase history and request a plant exchange.
</p>

<table class="orders-table">
<thead>
<tr>
<th>Order #</th>
<th>Plant Name</th>
<th>Amount Paid</th>
<th>Points Redeemed</th>
<th>Exchange Status</th>
<th>Price Adjustment</th>
<th>Exchange</th>
</tr>
</thead>

<tbody>

<?php if ($result && mysqli_num_rows($result) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($result)): ?>

<?php
$order_id = (int)($row['Order_id'] ?? 0);
$plant_id = (int)($row['Plant_id'] ?? 0);
$plant_name = $row['Plant_name'] ?? 'Unknown Plant';
$amount = (float)($row['Amount'] ?? 0);
$points = (int)($row['points_redeemed'] ?? 0);

$exchange_status = trim(
    (string)($row['exchange_status'] ?? '')
);

$status_lower = strtolower($exchange_status);

$difference = (float)($row['Exchange_value'] ?? 0);
$payment_status = $row['payment_status'] ?? '';
?>

<tr>

<td><strong>#<?=htmlspecialchars((string)$order_id)?></strong></td>

<td>
🌱 <?=htmlspecialchars($plant_name)?>
</td>

<td class="amount">
৳<?=number_format($amount,2)?>
</td>

<td>
<?=$points?> PTS
</td>

<td>

<?php if ($status_lower === 'pending'): ?>

<span class="status-badge status-pending">
⏳ Exchange Pending
</span>

<br>
<small style="color:#64748b;">
Waiting for employee approval
</small>

<?php elseif ($status_lower === 'approved'): ?>

<span class="status-badge status-approved">
✓ Exchange Approved
</span>

<br>
<small style="color:#1d4ed8;">
Exchange has been processed by employee
</small>

<?php elseif ($status_lower === 'completed'): ?>

<span class="status-badge status-completed">
✓ Exchange Completed
</span>

<?php else: ?>

<span class="status-badge status-none">
No Exchange
</span>

<?php endif; ?>

</td>

<td>

<?php if ($status_lower === 'approved'): ?>

<?php if ($difference > 0): ?>

<div class="cod">
💵 COD: ৳<?=number_format($difference,2)?>
</div>

<small>
Pay ৳<?=number_format($difference,2)?>
by Cash on Delivery.
</small>

<?php elseif ($difference < 0): ?>

<div class="wallet">
💰 Wallet Credited:
৳<?=number_format(abs($difference),2)?>
</div>

<small>
Refund added to your Store Wallet.
</small>

<?php else: ?>

<div class="wallet">
💵 COD: ৳0.00
</div>

<small>
No additional payment required.
</small>

<?php endif; ?>

<?php elseif ($status_lower === 'pending'): ?>

<small style="color:#92400e;">
Price adjustment will be finalized after approval.
</small>

<?php else: ?>

—

<?php endif; ?>

</td>

<td>

<?php if ($status_lower === 'pending'): ?>

<span class="disabled-btn">
⏳ Waiting for Employee
</span>

<?php elseif ($status_lower === 'approved'): ?>

<span class="approved-box">
✓ Approved & Processed
</span>

<?php elseif ($status_lower === 'completed'): ?>

<span class="disabled-btn">
✓ Done
</span>

<?php else: ?>

<?php if ($plant_id > 0 && $order_id > 0): ?>

<a
href="process_exchanges.php?order_id=<?=$order_id?>&offered_plant_id=<?=$plant_id?>"
class="exchange-btn"
>
🔄 Request Exchange
</a>

<?php else: ?>

<span class="disabled-btn">
Not Available
</span>

<?php endif; ?>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="7" class="empty">
No order history found.
<br><br>
<a href="shop.php" style="color:#065f46;font-weight:bold;">
Start Shopping 🌱
</a>
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

<?php
if (file_exists("footer.php")) {
    include("footer.php");
}
?>

</body>
</html>
