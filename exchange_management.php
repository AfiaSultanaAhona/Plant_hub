<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "DBconnect.php";

$raw_employee = $_SESSION['employee_id'] ?? $_SESSION['Employee_id'] ?? null;
$employee_id = (int)preg_replace('/[^0-9]/', '', (string)$raw_employee);
if ($employee_id <= 0 || ($_SESSION['role'] ?? '') !== 'employee') {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["process_exchange"])) {
    $exchange_id = (int)($_POST["exchange_id"] ?? 0);
    if ($exchange_id <= 0) {
        $error = "Invalid exchange request.";
    } else {
        mysqli_begin_transaction($conn);

        try {
            // Lock the exchange request so two employees cannot process it at once.
            $q = mysqli_query($conn, "SELECT * FROM exchange WHERE exchange_id=$exchange_id FOR UPDATE");
            if (!$q || mysqli_num_rows($q) === 0) throw new Exception("Exchange request not found.");
            $e = mysqli_fetch_assoc($q);

            if (strtolower((string)$e['status']) === 'completed') {
                throw new Exception("This exchange is already completed.");
            }

            $order_id = (int)($e['Order_ID'] ?? 0);
            $offered_id = (int)$e['Offered_plant_ID'];
            $received_id = (int)$e['Received_plant_ID'];
            $customer_id = (int)$e['Customer_ID'];

            if ($customer_id <= 0 || $offered_id <= 0 || $received_id <= 0) {
                throw new Exception("This exchange request contains invalid customer or plant information.");
            }

            // If linked to an order, lock and verify that order as well.
            $order = null;
            if ($order_id > 0) {
                $oq = mysqli_query($conn, "SELECT Order_id, Customer_id, Plant_id, Exchange_status
                                           FROM orders
                                           WHERE Order_id=$order_id AND Customer_id=$customer_id
                                           FOR UPDATE");
                if (!$oq || mysqli_num_rows($oq) === 0) {
                    throw new Exception("The purchase linked to this exchange could not be found.");
                }
                $order = mysqli_fetch_assoc($oq);
                if ((int)$order['Plant_id'] !== $offered_id) {
                    throw new Exception("The exchange no longer matches the purchased plant.");
                }
                if (strtolower((string)$order['Exchange_status']) === 'completed') {
                    throw new Exception("This purchase has already been exchanged.");
                }
            }

            $plants = mysqli_query($conn, "SELECT Plant_ID,Plant_name,Unit_price,Stock_quantity
                                           FROM plant
                                           WHERE Plant_ID IN ($offered_id,$received_id)
                                           FOR UPDATE");
            if (!$plants) throw new Exception("Could not load plant information.");

            $map = [];
            while ($p = mysqli_fetch_assoc($plants)) $map[(int)$p['Plant_ID']] = $p;

            if (!isset($map[$offered_id]) || !isset($map[$received_id])) {
                throw new Exception("One of the plants no longer exists.");
            }
            if ((int)$map[$received_id]['Stock_quantity'] <= 0) {
                throw new Exception("Requested plant is out of stock.");
            }

            $difference = round((float)$map[$received_id]['Unit_price'] - (float)$map[$offered_id]['Unit_price'], 2);

            if ($difference < 0) {
                $refund = abs($difference);
                $refund_ok = mysqli_query($conn, "UPDATE customer
                    SET wallet_balance=COALESCE(wallet_balance,0)+$refund
                    WHERE Customer_ID=$customer_id");
                if (!$refund_ok || mysqli_affected_rows($conn) !== 1) {
                    throw new Exception("Could not credit customer wallet.");
                }
                $method = "Store Wallet Credit";
                $payment_status = "Refunded ৳" . number_format($refund, 2) . " to wallet";
                $direction = "Store Pays";
            } elseif ($difference > 0) {
                // The employee confirms the extra amount is due from the customer.
                $method = "Cash";
                $payment_status = "Customer paid ৳" . number_format($difference, 2);
                $direction = "Customer Pays";
            } else {
                $method = "N/A";
                $payment_status = "No cash adjustment";
                $direction = "No Adjustment";
            }

            // Inventory changes happen only after employee processing.
            if (!mysqli_query($conn, "UPDATE plant SET Stock_quantity=Stock_quantity+1 WHERE Plant_ID=$offered_id")) {
                throw new Exception("Could not add offered plant to inventory.");
            }
            if (!mysqli_query($conn, "UPDATE plant SET Stock_quantity=Stock_quantity-1 WHERE Plant_ID=$received_id AND Stock_quantity>0")) {
                throw new Exception("Could not remove received plant from inventory.");
            }
            if (mysqli_affected_rows($conn) !== 1) {
                throw new Exception("Requested plant became unavailable. Please try again.");
            }

            $notes = mysqli_real_escape_string($conn, "Completed: customer gave {$map[$offered_id]['Plant_name']} and received {$map[$received_id]['Plant_name']}");
            $method_e = mysqli_real_escape_string($conn, $method);
            $status_e = mysqli_real_escape_string($conn, $payment_status);
            $direction_e = mysqli_real_escape_string($conn, $direction);

            $update = "UPDATE exchange SET
                        Exchange_value=$difference,
                        Employee_ID=$employee_id,
                        status='Completed',
                        payment_method='$method_e',
                        payment_status='$status_e',
                        adjustment_direction='$direction_e',
                        notes='$notes'
                       WHERE exchange_id=$exchange_id";
            if (!mysqli_query($conn, $update)) {
                throw new Exception("Could not update exchange record.");
            }

            // Keep My Orders status synchronized with the exchange table.
            if ($order_id > 0) {
                if (!mysqli_query($conn, "UPDATE orders SET Exchange_status='Completed'
                                           WHERE Order_id=$order_id AND Customer_id=$customer_id")) {
                    throw new Exception("Could not update purchase exchange status.");
                }
            }

            mysqli_commit($conn);

            if (function_exists("logEmployeeAction")) {
                logEmployeeAction(
                    $conn,
                    "EXCHANGE",
                    "Completed exchange #$exchange_id. $direction " . number_format(abs($difference), 2),
                    $exchange_id,
                    $employee_id
                );
            }

            $message = "Exchange #$exchange_id completed successfully.";
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $error = $ex->getMessage();
        }
    }
}

$exchanges = mysqli_query($conn, "SELECT e.*,
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
    ORDER BY e.exchange_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Exchange Management - Plant Hub</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0fdf4;margin:0;color:#1e293b}.container{max-width:1250px;margin:30px auto;padding:0 20px}.card{background:#fff;padding:25px;border-radius:14px;border:1px solid #e2e8f0}.back{display:inline-block;margin-bottom:20px;color:#0284c7;text-decoration:none;font-weight:700}.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:15px}table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f8fafc}.btn{background:#10b981;color:#fff;border:0;padding:8px 12px;border-radius:6px;font-weight:700;cursor:pointer}.pending{color:#92400e;font-weight:700}.completed{color:#166534;font-weight:700}.pay{color:#dc2626;font-weight:700}.refund{color:#059669;font-weight:700}.order-id{font-weight:700;color:#475569}
</style>
</head>
<body>
<?php if(file_exists("header.php")) include "header.php"; ?>
<div class="container">
<a class="back" href="employee_dashboard.php">← Back to Employee Dashboard</a>
<div class="card">
<h2>🔄 Plant Exchange Management</h2>
<?php if($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<table>
<tr><th>ID</th><th>Order</th><th>Customer</th><th>Customer Gives</th><th>Customer Receives</th><th>Adjustment</th><th>Status</th><th>Action</th></tr>
<?php if($exchanges && mysqli_num_rows($exchanges)): ?>
    <?php while($e=mysqli_fetch_assoc($exchanges)): ?>
        <?php $difference=(float)$e['Received_Price']-(float)$e['Offered_Price']; ?>
        <tr>
            <td>#<?php echo (int)$e['exchange_id']; ?></td>
            <td class="order-id"><?php echo !empty($e['Order_ID']) ? '#'.(int)$e['Order_ID'] : 'Legacy'; ?></td>
            <td><?php echo htmlspecialchars($e['Customer_name'] ?? 'Customer'); ?></td>
            <td><?php echo htmlspecialchars($e['Offered_Name'] ?? '-'); ?><br>৳<?php echo number_format((float)$e['Offered_Price'],2); ?></td>
            <td><?php echo htmlspecialchars($e['Received_Name'] ?? '-'); ?><br>৳<?php echo number_format((float)$e['Received_Price'],2); ?></td>
            <td>
                <?php if($difference>0): ?><span class="pay">Customer Pays ৳<?php echo number_format($difference,2); ?></span>
                <?php elseif($difference<0): ?><span class="refund">Store Refunds ৳<?php echo number_format(abs($difference),2); ?></span>
                <?php else: ?><strong>No Adjustment</strong><?php endif; ?>
            </td>
            <td>
                <?php if(strtolower((string)$e['status'])==='completed'): ?><span class="completed">Completed</span>
                <?php else: ?><span class="pending">Pending</span><?php endif; ?>
            </td>
            <td>
                <?php if(strtolower((string)$e['status'])!=='completed'): ?>
                    <form method="post" onsubmit="return confirm('Process this exchange?');">
                        <input type="hidden" name="exchange_id" value="<?php echo (int)$e['exchange_id']; ?>">
                        <button class="btn" name="process_exchange">✓ Process</button>
                    </form>
                <?php else: ?>✓ Done<?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="8" style="text-align:center">No exchange requests.</td></tr>
<?php endif; ?>
</table>
</div></div>
</body>
</html>
