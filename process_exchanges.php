<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

/*
|--------------------------------------------------------------------------
| CUSTOMER LOGIN CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['customer_id']) || (int)$_SESSION['customer_id'] <= 0) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

$msg = "";
$error = "";

/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/
$order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    header("Location: my_orders.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| LOAD THE CUSTOMER'S ORDER
|--------------------------------------------------------------------------
*/
$order_sql = "
    SELECT
        o.Order_id,
        o.Customer_id,
        o.Plant_id,
        o.Amount,
        o.Exchange_status,
        p.Plant_name,
        p.Unit_price
    FROM orders o
    LEFT JOIN plant p
        ON o.Plant_id = p.Plant_ID
    WHERE o.Order_id = $order_id
      AND o.Customer_id = $customer_id
    LIMIT 1
";

$order_result = mysqli_query($conn, $order_sql);

if (!$order_result || mysqli_num_rows($order_result) === 0) {
    die("Order not found or this order does not belong to your account.");
}

$order = mysqli_fetch_assoc($order_result);

$offered_plant_id = (int)$order['Plant_id'];
$offered_plant_name = $order['Plant_name'] ?? 'Unknown Plant';
$offered_price = (float)$order['Unit_price'];
$exchange_status = $order['Exchange_status'] ?? 'None';

/*
|--------------------------------------------------------------------------
| PREVENT MULTIPLE EXCHANGE REQUESTS
|--------------------------------------------------------------------------
*/
if (
    strcasecmp($exchange_status, 'Pending') === 0 ||
    strcasecmp($exchange_status, 'Completed') === 0
) {
    $error = "This order already has an exchange request.";
}

/*
|--------------------------------------------------------------------------
| SUBMIT EXCHANGE REQUEST
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_exchange']) &&
    empty($error)
) {

    $received_plant_id = (int)($_POST['received_plant_id'] ?? 0);

    if ($received_plant_id <= 0) {

        $error = "Please select a plant to receive.";

    } elseif ($received_plant_id === $offered_plant_id) {

        $error = "You cannot exchange a plant for the same plant.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | MAKE SURE TARGET PLANT EXISTS
        |--------------------------------------------------------------------------
        */

        $target_sql = "
            SELECT
                Plant_ID,
                Plant_name,
                Unit_price,
                Stock_quantity
            FROM plant
            WHERE Plant_ID = $received_plant_id
            LIMIT 1
        ";

        $target_result = mysqli_query($conn, $target_sql);

        if (!$target_result || mysqli_num_rows($target_result) === 0) {

            $error = "Selected plant does not exist.";

        } else {

            $target = mysqli_fetch_assoc($target_result);

            $received_name = $target['Plant_name'];
            $received_price = (float)$target['Unit_price'];
            $stock = (int)$target['Stock_quantity'];

            if ($stock <= 0) {

                $error = "The selected plant is currently out of stock.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | CALCULATE DIFFERENCE
                |--------------------------------------------------------------------------
                */

                $exchange_value = round(
                    $received_price - $offered_price,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | DO NOT REFUND OR CHARGE MONEY HERE.
                |
                | Employee will process the request later.
                |--------------------------------------------------------------------------
                */

                if ($exchange_value > 0) {

                    $payment_method = "Cash";
                    $payment_status =
                        "Pending payment ৳" .
                        number_format($exchange_value, 2);

                    $adjustment_direction = "Customer Pays";

                } elseif ($exchange_value < 0) {

                    $refund = abs($exchange_value);

                    $payment_method = "Store Wallet Credit";

                    $payment_status =
                        "Refund pending ৳" .
                        number_format($refund, 2);

                    $adjustment_direction = "Store Pays";

                } else {

                    $payment_method = "N/A";
                    $payment_status = "No cash adjustment";
                    $adjustment_direction = "No Adjustment";
                }

                /*
                |--------------------------------------------------------------------------
                | INSERT EXCHANGE REQUEST
                |--------------------------------------------------------------------------
                */

                $today = date('Y-m-d');

                $payment_method_e =
                    mysqli_real_escape_string(
                        $conn,
                        $payment_method
                    );

                $payment_status_e =
                    mysqli_real_escape_string(
                        $conn,
                        $payment_status
                    );

                $adjustment_direction_e =
                    mysqli_real_escape_string(
                        $conn,
                        $adjustment_direction
                    );

                $insert_sql = "
                    INSERT INTO exchange
                    (
                        Exchange_date,
                        Exchange_value,
                        Received_plant_ID,
                        Customer_ID,
                        Offered_plant_ID,
                        status,
                        payment_method,
                        payment_status,
                        adjustment_direction
                    )
                    VALUES
                    (
                        '$today',
                        '$exchange_value',
                        '$received_plant_id',
                        '$customer_id',
                        '$offered_plant_id',
                        'Pending',
                        '$payment_method_e',
                        '$payment_status_e',
                        '$adjustment_direction_e'
                    )
                ";

                if (mysqli_query($conn, $insert_sql)) {

                    /*
                    |--------------------------------------------------------------------------
                    | MARK ORDER AS PENDING EXCHANGE
                    |--------------------------------------------------------------------------
                    */

                    $update_order = "
                        UPDATE orders
                        SET Exchange_status = 'Pending'
                        WHERE Order_id = $order_id
                          AND Customer_id = $customer_id
                    ";

                    if (mysqli_query($conn, $update_order)) {

                        $msg =
                            "✅ Exchange request submitted successfully. " .
                            "An employee will process your request.";

                        $exchange_status = "Pending";

                    } else {

                        $error =
                            "Exchange created, but order status could not be updated: " .
                            mysqli_error($conn);
                    }

                } else {

                    $error =
                        "Failed to submit exchange request: " .
                        mysqli_error($conn);
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET AVAILABLE PLANTS
|--------------------------------------------------------------------------
*/
$plants_sql = "
    SELECT
        Plant_ID,
        Plant_name,
        Unit_price,
        Stock_quantity
    FROM plant
    WHERE Plant_ID != $offered_plant_id
      AND Stock_quantity > 0
    ORDER BY Plant_name ASC
";

$plants = mysqli_query($conn, $plants_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Request Plant Exchange - Plant Hub</title>

    <style>

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef7f2;
            margin: 0;
            color: #1e293b;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        h2 {
            margin-top: 0;
            color: #065f46;
        }

        .info-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            box-sizing: border-box;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #059669;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #0284c7;
            text-decoration: none;
            font-weight: bold;
        }

        .disabled {
            background: #e2e8f0;
            color: #475569;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }

    </style>

</head>

<body>

<?php include("header.php"); ?>

<div class="container">

    <a
        href="my_orders.php"
        class="back"
    >
        ← Back to My Orders
    </a>

    <div class="card">

        <h2>🔄 Request Plant Exchange</h2>

        <?php if ($msg): ?>

            <div class="success">
                <?= htmlspecialchars($msg) ?>
            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <div class="info-box">

            <strong>Your Plant:</strong><br>

            <?= htmlspecialchars($offered_plant_name) ?>

            <br>

            <strong>Value:</strong>
            ৳<?= number_format($offered_price, 2) ?>

            <br>

            <strong>Order #:</strong>
            <?= $order_id ?>

        </div>

        <?php if ($exchange_status === 'Pending'): ?>

            <div class="disabled">
                ⏳ Exchange request is already pending.
            </div>

        <?php elseif ($exchange_status === 'Completed'): ?>

            <div class="disabled">
                ✓ This order has already been exchanged.
            </div>

        <?php else: ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="order_id"
                    value="<?= $order_id ?>"
                >

                <label for="received_plant_id">
                    Select Plant You Want to Receive:
                </label>

                <select
                    name="received_plant_id"
                    id="received_plant_id"
                    required
                >

                    <option value="">
                        -- Select a plant --
                    </option>

                    <?php if ($plants && mysqli_num_rows($plants) > 0): ?>

                        <?php while ($plant = mysqli_fetch_assoc($plants)): ?>

                            <option
                                value="<?= (int)$plant['Plant_ID'] ?>"
                            >
                                <?= htmlspecialchars($plant['Plant_name']) ?>
                                -
                                ৳<?= number_format(
                                    (float)$plant['Unit_price'],
                                    2
                                ) ?>
                                -
                                Stock:
                                <?= (int)$plant['Stock_quantity'] ?>
                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

                <button
                    type="submit"
                    name="submit_exchange"
                >
                    🔄 Submit Exchange Request
                </button>

            </form>

        <?php endif; ?>

    </div>

</div>

</body>

</html>