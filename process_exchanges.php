<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "DBconnect.php";

$raw_customer =
    $_SESSION['customer_id']
    ?? $_SESSION['Customer_id']
    ?? $_SESSION['Customer_ID']
    ?? $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? null;

$customer_id = (int)preg_replace('/[^0-9]/', '', (string)$raw_customer);

if ($customer_id <= 0) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

$order_id = (int)(
    $_GET['order_id']
    ?? $_POST['order_id']
    ?? 0
);

$offered_from_order = (int)(
    $_GET['offered_plant_id']
    ?? $_POST['offered_plant_id']
    ?? 0
);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_exchange"])) {

    $offered = (int)($_POST["offered_plant_id"] ?? 0);
    $received = (int)($_POST["received_plant_id"] ?? 0);
    $order_id = (int)($_POST["order_id"] ?? 0);

    if ($offered <= 0 || $received <= 0 || $offered === $received) {
        $error = "Please select two different plants.";
    } else {

        /*
         * Verify that this exact order belongs to the logged-in customer
         * and contains the offered plant.
         */
        $order_result = false;

        if ($order_id > 0) {
            $order_result = mysqli_query(
                $conn,
                "SELECT Order_id, Customer_id, Plant_id, Amount
                 FROM orders
                 WHERE Order_id=$order_id
                   AND Customer_id=$customer_id
                   AND Plant_id=$offered
                   AND Amount>0
                 LIMIT 1"
            );
        }

        if (!$order_result || mysqli_num_rows($order_result) === 0) {
            $error = "The selected order or plant could not be verified.";
        } else {

            $offered_q = mysqli_query(
                $conn,
                "SELECT Plant_ID, Plant_name, Unit_price
                 FROM plant
                 WHERE Plant_ID=$offered
                 LIMIT 1"
            );

            $received_q = mysqli_query(
                $conn,
                "SELECT Plant_ID, Plant_name, Unit_price, Stock_quantity
                 FROM plant
                 WHERE Plant_ID=$received
                 LIMIT 1"
            );

            if (
                !$offered_q ||
                mysqli_num_rows($offered_q) === 0 ||
                !$received_q ||
                mysqli_num_rows($received_q) === 0
            ) {
                $error = "Selected plant could not be found.";
            } else {

                $op = mysqli_fetch_assoc($offered_q);
                $rp = mysqli_fetch_assoc($received_q);

                if ((int)$rp["Stock_quantity"] <= 0) {
                    $error = "The requested plant is currently out of stock.";
                } else {

                    /*
                     * Prevent more than one active request for the same order.
                     */
                    $duplicate = mysqli_query(
                        $conn,
                        "SELECT exchange_id
                         FROM exchange
                         WHERE Order_ID=$order_id
                           AND Customer_ID=$customer_id
                           AND status IN ('Pending','Approved')
                         LIMIT 1"
                    );

                    if ($duplicate && mysqli_num_rows($duplicate) > 0) {
                        $error = "This order already has an active exchange request.";
                    } else {

                        $difference = round(
                            (float)$rp["Unit_price"] -
                            (float)$op["Unit_price"],
                            2
                        );

                        if ($difference > 0) {
                            $method = "Cash on Delivery";
                            $payment_status =
                                "COD due ৳" . number_format($difference, 2);
                            $direction = "Customer Pays";
                        } elseif ($difference < 0) {
                            $method = "Store Wallet Credit";
                            $payment_status =
                                "Wallet refund ৳" .
                                number_format(abs($difference), 2) .
                                " after employee approval";
                            $direction = "Store Refunds";
                        } else {
                            $method = "Cash on Delivery";
                            $payment_status = "COD due ৳0.00";
                            $direction = "No Adjustment";
                        }

                        $notes = mysqli_real_escape_string(
                            $conn,
                            "Customer requests " .
                            $op["Plant_name"] .
                            " → " .
                            $rp["Plant_name"]
                        );

                        $method_e = mysqli_real_escape_string($conn, $method);
                        $payment_e = mysqli_real_escape_string($conn, $payment_status);
                        $direction_e = mysqli_real_escape_string($conn, $direction);

                        /*
                         * IMPORTANT:
                         * Requesting an exchange does NOT change stock or wallet.
                         * Employee approval does the actual processing.
                         */
                        $sql = "
                            INSERT INTO exchange
                            (
                                Exchange_date,
                                Exchange_value,
                                Received_plant_ID,
                                Customer_ID,
                                Employee_ID,
                                Offered_plant_ID,
                                Order_ID,
                                status,
                                payment_method,
                                payment_status,
                                adjustment_direction,
                                notes
                            )
                            VALUES
                            (
                                CURDATE(),
                                $difference,
                                $received,
                                $customer_id,
                                NULL,
                                $offered,
                                $order_id,
                                'Pending',
                                '$method_e',
                                '$payment_e',
                                '$direction_e',
                                '$notes'
                            )
                        ";

                        if (mysqli_query($conn, $sql)) {

                            mysqli_query(
                                $conn,
                                "UPDATE orders
                                 SET Exchange_status='Pending'
                                 WHERE Order_id=$order_id
                                   AND Customer_id=$customer_id"
                            );

                            $message =
                                "Exchange request submitted successfully. " .
                                "Please wait for employee approval.";

                            $offered_from_order = $offered;

                        } else {
                            $error =
                                "Could not submit exchange: " .
                                mysqli_error($conn);
                        }
                    }
                }
            }
        }
    }
}

$owned = mysqli_query(
    $conn,
    "SELECT DISTINCT
        p.Plant_ID,
        p.Plant_name,
        p.Unit_price
     FROM plant p
     INNER JOIN orders o ON o.Plant_id=p.Plant_ID
     WHERE o.Customer_id=$customer_id
       AND o.Amount>0
     ORDER BY p.Plant_name"
);

$available = mysqli_query(
    $conn,
    "SELECT
        Plant_ID,
        Plant_name,
        Unit_price,
        Stock_quantity
     FROM plant
     WHERE Stock_quantity>0
     ORDER BY Plant_name"
);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Request Plant Exchange - Plant Hub</title>
<style>
body{font-family:Segoe UI,sans-serif;background:#eef7f2;margin:0;color:#1e293b}
.box{max-width:620px;margin:40px auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.07)}
.back{display:inline-block;margin-bottom:18px;color:#0284c7;text-decoration:none;font-weight:700}
h2{color:#065f46;margin-top:0}
.description{color:#64748b;line-height:1.6}
.info{background:#eff6ff;color:#1d4ed8;padding:13px;border-radius:8px;margin:15px 0}
.success{background:#dcfce7;color:#166534;padding:13px;border-radius:8px;margin:15px 0}
.error{background:#fee2e2;color:#991b1b;padding:13px;border-radius:8px;margin:15px 0}
label{display:block;font-weight:700;margin-top:18px}
select{width:100%;padding:12px;margin-top:7px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:14px}
button{width:100%;padding:12px;margin-top:22px;border:0;border-radius:8px;background:#10b981;color:#fff;font-weight:700;font-size:15px;cursor:pointer}
button:hover{background:#059669}
.note{margin-top:18px;font-size:13px;color:#64748b;line-height:1.6}
</style>
</head>
<body>

<?php if (file_exists("header.php")) include "header.php"; ?>

<div class="box">

<a class="back" href="my_orders.php">← Back to My Orders</a>

<h2>🔄 Request Plant Exchange</h2>

<p class="description">
Choose the plant from this order and the plant you want to receive.
The request will be sent to an employee for approval.
</p>

<?php if ($order_id > 0): ?>
<div class="info">
<strong>Order #<?=htmlspecialchars((string)$order_id)?></strong>
<br>
This request is linked to your selected order.
</div>
<?php endif; ?>

<?php if ($message): ?>
<div class="success"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="error"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<form method="POST" action="process_exchanges.php">

<input type="hidden" name="order_id" value="<?= (int)$order_id ?>">

<label for="offered_plant_id">Current Plant You Own</label>

<select id="offered_plant_id" name="offered_plant_id" required>
<option value="">Select your plant</option>

<?php if ($owned): ?>
<?php while ($p = mysqli_fetch_assoc($owned)): ?>
<option
value="<?= (int)$p["Plant_ID"] ?>"
<?= ((int)$p["Plant_ID"] === (int)$offered_from_order) ? "selected" : "" ?>
>
<?=htmlspecialchars($p["Plant_name"])?>
— ৳<?=number_format((float)$p["Unit_price"],2)?>
</option>
<?php endwhile; ?>
<?php endif; ?>

</select>

<label for="received_plant_id">Plant You Want to Receive</label>

<select id="received_plant_id" name="received_plant_id" required>
<option value="">Select target plant</option>

<?php if ($available): ?>
<?php while ($p = mysqli_fetch_assoc($available)): ?>
<option value="<?= (int)$p["Plant_ID"] ?>">
<?=htmlspecialchars($p["Plant_name"])?>
— ৳<?=number_format((float)$p["Unit_price"],2)?>
(Stock: <?= (int)$p["Stock_quantity"] ?>)
</option>
<?php endwhile; ?>
<?php endif; ?>

</select>

<button type="submit" name="submit_exchange">
Submit Exchange Request
</button>

</form>

<div class="note">
<strong>Price adjustment:</strong><br>
• New plant costs more → the difference is shown as Cash on Delivery.<br>
• Same price → COD is ৳0.00.<br>
• New plant costs less → the difference is credited to your Store Wallet after employee approval.<br>
• No wallet or inventory change happens when the request is submitted.
</div>

</div>
</body>
</html>
