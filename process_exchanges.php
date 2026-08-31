<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "DBconnect.php";

if (($_SESSION['role'] ?? '') !== 'customer' || (int)($_SESSION['customer_id'] ?? 0) <= 0) {
    header("Location: login.php");
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$message = "";
$error = "";

// The order is supplied by My Orders. The offered plant is NEVER chosen by the customer.
$order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    header("Location: my_orders.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exchange'])) {
    $received_id = (int)($_POST['received_plant_id'] ?? 0);

    if ($received_id <= 0) {
        $error = "Please select the plant you want to receive.";
    } else {
        mysqli_begin_transaction($conn);

        try {
            // Lock the customer's order and verify ownership.
            $stmt = mysqli_prepare($conn, "SELECT o.Order_id, o.Customer_id, o.Plant_id, o.Amount, o.Exchange_status,
                                                   p.Plant_name AS Offered_Name, p.Unit_price AS Offered_Price
                                            FROM orders o
                                            LEFT JOIN plant p ON p.Plant_ID = o.Plant_id
                                            WHERE o.Order_id = ? AND o.Customer_id = ? AND o.Amount > 0
                                            FOR UPDATE");
            if (!$stmt) {
                throw new Exception("Could not validate the purchase.");
            }
            mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
            mysqli_stmt_execute($stmt);
            $order_result = mysqli_stmt_get_result($stmt);
            $order = $order_result ? mysqli_fetch_assoc($order_result) : null;

            if (!$order) {
                throw new Exception("This purchase does not belong to your account.");
            }

            $exchange_status = strtolower(trim((string)($order['Exchange_status'] ?? 'None')));
            if ($exchange_status === 'pending') {
                throw new Exception("An exchange request is already pending for this order.");
            }
            if ($exchange_status === 'completed') {
                throw new Exception("This order has already been exchanged.");
            }

            $offered_id = (int)$order['Plant_id'];
            if ($offered_id <= 0) {
                throw new Exception("The purchased plant could not be found.");
            }

            if ($received_id === $offered_id) {
                throw new Exception("Please select a different plant for the exchange.");
            }

            // Lock the requested plant so stock cannot change during validation.
            $plant_stmt = mysqli_prepare($conn, "SELECT Plant_ID, Plant_name, Unit_price, Stock_quantity
                                                 FROM plant
                                                 WHERE Plant_ID = ?
                                                 FOR UPDATE");
            if (!$plant_stmt) {
                throw new Exception("Could not validate the requested plant.");
            }
            mysqli_stmt_bind_param($plant_stmt, "i", $received_id);
            mysqli_stmt_execute($plant_stmt);
            $plant_result = mysqli_stmt_get_result($plant_stmt);
            $received = $plant_result ? mysqli_fetch_assoc($plant_result) : null;

            if (!$received) {
                throw new Exception("The requested plant could not be found.");
            }
            if ((int)$received['Stock_quantity'] <= 0) {
                throw new Exception("The requested plant is currently out of stock.");
            }

            // Price difference is recorded now but no money or inventory is changed until approval.
            $difference = round((float)$received['Unit_price'] - (float)$order['Offered_Price'], 2);
            $method = $difference > 0 ? 'Cash' : ($difference < 0 ? 'Store Wallet Credit' : 'N/A');
            $payment_status = $difference > 0
                ? 'Customer pays ৳' . number_format($difference, 2) . ' after approval'
                : ($difference < 0
                    ? 'Store refunds ৳' . number_format(abs($difference), 2) . ' to wallet after approval'
                    : 'No cash adjustment');
            $direction = $difference > 0 ? 'Customer Pays' : ($difference < 0 ? 'Store Pays' : 'No Adjustment');
            $notes = "Order #$order_id: customer requests {$order['Offered_Name']} → {$received['Plant_name']}";

            $next_id_result = mysqli_query($conn, "SELECT COALESCE(MAX(exchange_id),0)+1 AS next_id FROM exchange FOR UPDATE");
            if (!$next_id_result) {
                throw new Exception("Could not create exchange request.");
            }
            $exchange_id = (int)mysqli_fetch_assoc($next_id_result)['next_id'];

            $stmt = mysqli_prepare($conn, "INSERT INTO exchange
                (exchange_id, Order_ID, Exchange_date, Exchange_value, Received_plant_ID, Customer_ID,
                 Employee_ID, Offered_plant_ID, status, payment_method, payment_status, adjustment_direction, notes)
                VALUES (?, ?, CURDATE(), ?, ?, ?, NULL, ?, 'Pending', ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Could not prepare exchange request.");
            }

            $method_e = $method;
            $payment_status_e = $payment_status;
            $direction_e = $direction;
            $notes_e = $notes;
            mysqli_stmt_bind_param(
                $stmt,
                "iidiiissss",
                $exchange_id,
                $order_id,
                $difference,
                $received_id,
                $customer_id,
                $offered_id,
                $method_e,
                $payment_status_e,
                $direction_e,
                $notes_e
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Could not submit exchange request: " . mysqli_stmt_error($stmt));
            }

            if (!mysqli_query($conn, "UPDATE orders SET Exchange_status='Pending' WHERE Order_id=$order_id AND Customer_id=$customer_id")) {
                throw new Exception("Could not update purchase exchange status.");
            }

            mysqli_commit($conn);
            $message = "Exchange request #$exchange_id submitted successfully. An employee must approve/process it before any inventory or wallet changes occur.";
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Always reload the selected order after POST so the page shows current information.
$order_stmt = mysqli_prepare($conn, "SELECT o.Order_id, o.Plant_id, o.Amount, o.Order_date, o.Exchange_status,
                                            p.Plant_name, p.Unit_price
                                     FROM orders o
                                     LEFT JOIN plant p ON p.Plant_ID=o.Plant_id
                                     WHERE o.Order_id=? AND o.Customer_id=? AND o.Amount>0
                                     LIMIT 1");
mysqli_stmt_bind_param($order_stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order = $order_result ? mysqli_fetch_assoc($order_result) : null;

if (!$order) {
    header("Location: my_orders.php");
    exit;
}

$available = mysqli_query($conn, "SELECT Plant_ID, Plant_name, Unit_price, Stock_quantity
                                  FROM plant
                                  WHERE Stock_quantity > 0 AND Plant_ID <> " . (int)$order['Plant_id'] . "
                                  ORDER BY Plant_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Plant Exchange - Plant Hub</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#eef7f2;margin:0;color:#1e293b}.box{max-width:620px;margin:40px auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,.05)}
h2{color:#065f46}.back{color:#0284c7;text-decoration:none;font-weight:700}.info{background:#f0fdf4;border:1px solid #bbf7d0;padding:14px;border-radius:8px;margin:18px 0}.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:15px}label{font-weight:700;display:block;margin-top:15px}select,button{width:100%;box-sizing:border-box;padding:11px;margin-top:7px;border-radius:7px}select{border:1px solid #cbd5e1}button{background:#10b981;color:#fff;border:0;font-weight:700;cursor:pointer}button:hover{background:#059669}.note{font-size:13px;color:#64748b;margin-top:8px}
</style>
</head>
<body>
<?php if(file_exists("header.php")) include "header.php"; ?>
<div class="box">
    <a class="back" href="my_orders.php">← Back to Purchase History</a>
    <h2>🔄 Request Plant Exchange</h2>

    <?php if($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="info">
        <strong>Order #<?php echo (int)$order['Order_id']; ?></strong><br>
        You purchased: <strong><?php echo htmlspecialchars($order['Plant_name'] ?? 'Unknown Plant'); ?></strong><br>
        Original price: <strong>৳<?php echo number_format((float)$order['Unit_price'],2); ?></strong>
    </div>

    <?php if (strtolower((string)$order['Exchange_status']) === 'none' || trim((string)$order['Exchange_status']) === ''): ?>
        <form method="post">
            <input type="hidden" name="order_id" value="<?php echo (int)$order['Order_id']; ?>">
            <label for="received_plant_id">Plant You Want to Receive</label>
            <select id="received_plant_id" name="received_plant_id" required>
                <option value="">Select target plant</option>
                <?php if($available && mysqli_num_rows($available)>0): ?>
                    <?php while($p=mysqli_fetch_assoc($available)): ?>
                        <option value="<?php echo (int)$p['Plant_ID']; ?>">
                            <?php echo htmlspecialchars($p['Plant_name']); ?> — ৳<?php echo number_format((float)$p['Unit_price'],2); ?> (Stock: <?php echo (int)$p['Stock_quantity']; ?>)
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <p class="note">The plant you purchased is automatically used as the offered plant. No inventory or wallet balance changes until an employee processes the request.</p>
            <button type="submit" name="submit_exchange">Submit Exchange Request</button>
        </form>
    <?php elseif (strtolower((string)$order['Exchange_status']) === 'pending'): ?>
        <div class="info">⏳ This order already has a pending exchange request. Please wait for an employee to process it.</div>
    <?php else: ?>
        <div class="info">✓ This order has already been exchanged.</div>
    <?php endif; ?>
</div>
</body>
</html>
