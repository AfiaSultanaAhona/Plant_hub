<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "DBconnect.php";

$raw_employee = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
$employee_id = (int)preg_replace('/[^0-9]/', '', (string)$raw_employee);

if ($employee_id <= 0) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["approve_exchange"])) {

    $exchange_id = (int)($_POST["exchange_id"] ?? 0);

    mysqli_begin_transaction($conn);

    try {
        $q = mysqli_query(
            $conn,
            "SELECT * FROM exchange
             WHERE exchange_id=$exchange_id
               AND status='Pending'
             FOR UPDATE"
        );

        if (!$q || mysqli_num_rows($q) === 0) {
            throw new Exception("Pending exchange request not found.");
        }

        $e = mysqli_fetch_assoc($q);

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

        if (!$pq) {
            throw new Exception("Could not load plant information.");
        }

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
            $payment_status = "COD due ৳" . number_format($difference, 2);
            $direction = "Customer Pays";
        } elseif ($difference < 0) {
            $refund = abs($difference);

            $wallet = mysqli_query(
                $conn,
                "UPDATE customer
                 SET wallet_balance=COALESCE(wallet_balance,0)+$refund
                 WHERE Customer_ID=$customer_id"
            );

            if (!$wallet || mysqli_affected_rows($conn) !== 1) {
                throw new Exception("Could not credit customer wallet.");
            }

            $method = "Store Wallet Credit";
            $payment_status = "Wallet credited ৳" . number_format($refund, 2);
            $direction = "Store Refunds";
        } else {
            $method = "Cash on Delivery";
            $payment_status = "COD due ৳0.00";
            $direction = "No Adjustment";
        }

        $return_old = mysqli_query(
            $conn,
            "UPDATE plant
             SET Stock_quantity=Stock_quantity+1
             WHERE Plant_ID=$offered_id"
        );

        if (!$return_old || mysqli_affected_rows($conn) !== 1) {
            throw new Exception("Could not return the old plant to inventory.");
        }

        $take_new = mysqli_query(
            $conn,
            "UPDATE plant
             SET Stock_quantity=Stock_quantity-1
             WHERE Plant_ID=$received_id
               AND Stock_quantity>0"
        );

        if (!$take_new || mysqli_affected_rows($conn) !== 1) {
            throw new Exception("Could not remove the requested plant from inventory.");
        }

        $method_e = mysqli_real_escape_string($conn, $method);
        $payment_e = mysqli_real_escape_string($conn, $payment_status);
        $direction_e = mysqli_real_escape_string($conn, $direction);

        $notes = mysqli_real_escape_string(
            $conn,
            "Approved and processed by employee #$employee_id: " .
            $plants[$offered_id]["Plant_name"] . " → " .
            $plants[$received_id]["Plant_name"]
        );

        $update = mysqli_query(
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

        if (!$update || mysqli_affected_rows($conn) !== 1) {
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

        if (function_exists("logEmployeeAction")) {
            logEmployeeAction(
                $conn,
                "EXCHANGE",
                "Approved and processed exchange #$exchange_id",
                $exchange_id,
                $employee_id
            );
        }

        $message = "Exchange #$exchange_id approved and processed successfully.";

    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        $error = $ex->getMessage();
    }
}

/*
 * Employee portal intentionally shows ONLY Pending requests.
 * Approved requests disappear from this list.
 */
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
     WHERE e.status='Pending'
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
table{width:100%;border-collapse:collapse;min-width:900px}
th,td{padding:11px;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:top}
th{background:#f8fafc}
.status{display:inline-block;padding:5px 9px;border-radius:6px;font-size:12px;font-weight:700}
.pending{background:#fef3c7;color:#92400e}
.pay{color:#dc2626;font-weight:700}
.refund{color:#059669;font-weight:700}
.approve-btn{background:#10b981;color:white;border:0;padding:8px 12px;border-radius:6px;font-weight:700;cursor:pointer}
</style>
</head>
<body>
<?php if (file_exists("header.php")) include "header.php"; ?>

<div class="container">
<a class="back" href="employee_dashboard.php">← Back to Employee Dashboard</a>

<div class="card">
<h2>🔄 Plant Exchange Management</h2>
<p style="color:#64748b">
Only pending exchange requests are shown here. Approving a request
also completes the inventory and price-adjustment processing.
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
<th>Action</th>
</tr>
</thead>
<tbody>

<?php if ($exchanges && mysqli_num_rows($exchanges) > 0): ?>
<?php while ($e = mysqli_fetch_assoc($exchanges)): ?>

<?php
$difference = (float)$e["Received_Price"] - (float)$e["Offered_Price"];
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
<strong>COD ৳0.00</strong>
<?php endif; ?>
</td>

<td>
<span class="status pending">⏳ Pending</span>
</td>

<td>
<form method="POST">
<input type="hidden" name="exchange_id" value="<?=$e["exchange_id"]?>">
<button
type="submit"
name="approve_exchange"
class="approve-btn"
onclick="return confirm('Approve and process this exchange?')">
✓ Approve & Process
</button>
</form>
</td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="8" style="text-align:center;color:#64748b;padding:25px">
No pending exchange requests.
</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</body>
</html>
