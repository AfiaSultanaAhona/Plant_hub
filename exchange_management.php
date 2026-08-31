<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "DBconnect.php";

$employee_id = (int)preg_replace(
    '/[^0-9]/',
    '',
    (string)($_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? null)
);

if ($employee_id <= 0) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

/* Employee only approves: no stock/wallet change here. */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["approve_exchange"])) {
    $exchange_id = (int)($_POST["exchange_id"] ?? 0);

    mysqli_begin_transaction($conn);

    try {
        $q = mysqli_query(
            $conn,
            "SELECT * FROM exchange
             WHERE exchange_id=$exchange_id
             FOR UPDATE"
        );

        if (!$q || mysqli_num_rows($q) === 0) {
            throw new Exception("Exchange request not found.");
        }

        $e = mysqli_fetch_assoc($q);

        if (strcasecmp((string)$e["status"], "Pending") !== 0) {
            throw new Exception("Only Pending requests can be approved.");
        }

        $offered_id  = (int)$e["Offered_plant_ID"];
        $received_id = (int)$e["Received_plant_ID"];
        $customer_id = (int)$e["Customer_ID"];
        $order_id    = (int)($e["Order_ID"] ?? 0);

        $pq = mysqli_query(
            $conn,
            "SELECT Plant_ID, Plant_name, Unit_price, Stock_quantity
             FROM plant
             WHERE Plant_ID IN ($offered_id,$received_id)
             FOR UPDATE"
        );

        $plants = [];
        while ($p = mysqli_fetch_assoc($pq)) {
            $plants[(int)$p["Plant_ID"]] = $p;
        }

        if (!isset($plants[$offered_id]) || !isset($plants[$received_id])) {
            throw new Exception("One of the plants no longer exists.");
        }

        if ((int)$plants[$received_id]["Stock_quantity"] <= 0) {
            throw new Exception("Cannot approve: requested plant is out of stock.");
        }

        $difference = round(
            (float)$plants[$received_id]["Unit_price"] -
            (float)$plants[$offered_id]["Unit_price"],
            2
        );

        if ($difference > 0) {
            $method = "Cash on Delivery";
            $payment = "COD due ৳" . number_format($difference, 2);
            $direction = "Customer Pays";
        } elseif ($difference < 0) {
            $method = "Store Wallet Credit";
            $payment = "Refund ৳" . number_format(abs($difference), 2)
                . " to store wallet after completion";
            $direction = "Store Refunds";
        } else {
            $method = "N/A";
            $payment = "No price adjustment";
            $direction = "No Adjustment";
        }

        $method_e = mysqli_real_escape_string($conn, $method);
        $payment_e = mysqli_real_escape_string($conn, $payment);
        $direction_e = mysqli_real_escape_string($conn, $direction);

        $notes = mysqli_real_escape_string(
            $conn,
            "Approved by employee #$employee_id: " .
            $plants[$offered_id]["Plant_name"] . " → " .
            $plants[$received_id]["Plant_name"]
        );

        $ok = mysqli_query(
            $conn,
            "UPDATE exchange SET
                Employee_ID=$employee_id,
                Exchange_value=$difference,
                status='Approved',
                payment_method='$method_e',
                payment_status='$payment_e',
                adjustment_direction='$direction_e',
                notes='$notes'
             WHERE exchange_id=$exchange_id
               AND status='Pending'"
        );

        if (!$ok || mysqli_affected_rows($conn) !== 1) {
            throw new Exception("Could not approve exchange request.");
        }

        if ($order_id > 0) {
            mysqli_query(
                $conn,
                "UPDATE orders
                 SET Exchange_status='Approved'
                 WHERE Order_id=$order_id
                   AND Customer_id=$customer_id"
            );
        }

        mysqli_commit($conn);

        $message =
            "Exchange #$exchange_id approved. " .
            "The customer can now complete the exchange.";

    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        $error = $ex->getMessage();
    }
}

$exchanges = mysqli_query(
    $conn,
    "SELECT
        e.*,
        c.Customer_name,
        op.Plant_name AS Offered_Name,
        op.Unit_price AS Offered_Price,
        rp.Plant_name AS Received_Name,
        rp.Unit_price AS Received_Price,
        rp.Stock_quantity AS Received_Stock
     FROM exchange e
     LEFT JOIN customer c ON c.Customer_ID=e.Customer_ID
     LEFT JOIN plant op ON op.Plant_ID=e.Offered_plant_ID
     LEFT JOIN plant rp ON rp.Plant_ID=e.Received_plant_ID
     ORDER BY e.exchange_id DESC"
);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Exchange Management - Plant Hub</title>
<style>
body{font-family:Segoe UI,sans-serif;background:#f0fdf4;margin:0;color:#1e293b}
.container{max-width:1250px;margin:30px auto;padding:0 20px}
.card{background:#fff;padding:25px;border-radius:14px;border:1px solid #e2e8f0;overflow-x:auto}
.back{display:inline-block;margin-bottom:20px;color:#0284c7;text-decoration:none;font-weight:700}
.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px}
.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:15px}
table{width:100%;border-collapse:collapse;min-width:950px}
th,td{padding:11px;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:top}
th{background:#f8fafc}
.status{display:inline-block;padding:5px 9px;border-radius:6px;font-size:12px;font-weight:700}
.pending{background:#fef3c7;color:#92400e}
.approved{background:#dbeafe;color:#1d4ed8}
.completed{background:#dcfce7;color:#166534}
.pay{color:#dc2626;font-weight:700}
.refund{color:#059669;font-weight:700}
.approve-btn{background:#10b981;color:white;border:0;padding:8px 12px;border-radius:6px;font-weight:700;cursor:pointer}
.done{color:#64748b;font-weight:700}
</style>
</head>
<body>

<?php if (file_exists("header.php")) include "header.php"; ?>

<div class="container">
<a class="back" href="employee_dashboard.php">← Back to Employee Dashboard</a>

<div class="card">
<h2>🔄 Plant Exchange Management</h2>
<p style="color:#64748b">
Review customer exchange requests. Approval does not complete the exchange;
the customer must complete an approved request.
</p>

<?php if ($message): ?>
<div class="success"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="error"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<table>
<thead>
<tr>
<th>ID</th>
<th>Order</th>
<th>Customer</th>
<th>Customer Gives</th>
<th>Customer Receives</th>
<th>Price Adjustment</th>
<th>Status</th>
<th>Employee Action</th>
</tr>
</thead>
<tbody>

<?php if ($exchanges && mysqli_num_rows($exchanges) > 0): ?>

<?php while ($e = mysqli_fetch_assoc($exchanges)): ?>

<?php
$status = strtolower(trim((string)$e["status"]));
$difference =
    (float)$e["Received_Price"] -
    (float)$e["Offered_Price"];
?>

<tr>
<td><strong>#<?=$e["exchange_id"]?></strong></td>
<td>#<?=((int)($e["Order_ID"] ?? 0))?></td>
<td><?=htmlspecialchars($e["Customer_name"] ?? "Customer")?></td>

<td>
<?=htmlspecialchars($e["Offered_Name"] ?? "-")?><br>
৳<?=number_format((float)$e["Offered_Price"],2)?>
</td>

<td>
<?=htmlspecialchars($e["Received_Name"] ?? "-")?><br>
৳<?=number_format((float)$e["Received_Price"],2)?>

<?php if ((int)($e["Received_Stock"] ?? 0) <= 0): ?>
<br><small style="color:#dc2626">Out of stock</small>
<?php endif; ?>
</td>

<td>
<?php if ($difference > 0): ?>
<span class="pay">
Customer Pays COD ৳<?=number_format($difference,2)?>
</span>
<?php elseif ($difference < 0): ?>
<span class="refund">
Store Wallet Refund ৳<?=number_format(abs($difference),2)?>
</span>
<?php else: ?>
<strong>No Adjustment</strong>
<?php endif; ?>
</td>

<td>
<?php if ($status === "pending"): ?>
<span class="status pending">⏳ Pending</span>
<?php elseif ($status === "approved"): ?>
<span class="status approved">✓ Approved</span>
<?php elseif ($status === "completed"): ?>
<span class="status completed">✓ Completed</span>
<?php else: ?>
<span class="status"><?=htmlspecialchars($e["status"])?></span>
<?php endif; ?>
</td>

<td>
<?php if ($status === "pending"): ?>

<form method="POST">
<input type="hidden" name="exchange_id" value="<?=$e["exchange_id"]?>">
<button
type="submit"
name="approve_exchange"
class="approve-btn"
onclick="return confirm('Approve this exchange request?')">
✓ Approve
</button>
</form>

<?php elseif ($status === "approved"): ?>
<span class="done">Waiting for Customer</span>

<?php elseif ($status === "completed"): ?>
<span class="done">✓ Done</span>
<?php endif; ?>
</td>
</tr>

<?php endwhile; ?>

<?php else: ?>
<tr>
<td colspan="8" style="text-align:center;color:#64748b">
No exchange requests found.
</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>

</body>
</html>
