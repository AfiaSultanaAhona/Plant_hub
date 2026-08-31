<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "DBconnect.php";

/*
|--------------------------------------------------------------------------
| CUSTOMER SESSION
|--------------------------------------------------------------------------
*/

$raw_customer =
    $_SESSION['customer_id']
    ?? $_SESSION['Customer_ID']
    ?? $_SESSION['user_id']
    ?? null;

$customer_id = (int) preg_replace(
    '/[^0-9]/',
    '',
    (string) $raw_customer
);

if ($customer_id <= 0) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| VALUES FROM MY ORDERS
|--------------------------------------------------------------------------
|
| My Orders sends:
|
| process_exchanges.php?order_id=XX&offered_plant_id=XX
|
*/

$order_id = (int) (
    $_GET['order_id']
    ?? $_POST['order_id']
    ?? 0
);

$offered_from_order = (int) (
    $_GET['offered_plant_id']
    ?? $_POST['offered_plant_id']
    ?? 0
);


/*
|--------------------------------------------------------------------------
| SUBMIT EXCHANGE REQUEST
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Customer submitting a request DOES NOT:
| - change stock
| - change wallet
| - complete exchange
|
| It only creates a Pending request.
|
| Employee will approve it from exchange_management.php.
|
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["submit_exchange"])) {

    $offered = (int) ($_POST["offered_plant_id"] ?? 0);

    $received = (int) ($_POST["received_plant_id"] ?? 0);

    $posted_order_id = (int) ($_POST["order_id"] ?? 0);

    if ($posted_order_id > 0) {
        $order_id = $posted_order_id;
    }


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($offered <= 0 || $received <= 0) {

        $error = "Please select both plants.";

    } elseif ($offered === $received) {

        $error = "You cannot exchange a plant for the same plant.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | VERIFY CUSTOMER'S ORDER
        |--------------------------------------------------------------------------
        */

        if ($order_id > 0) {

            $order_sql = "
                SELECT
                    Order_id,
                    Plant_id,
                    Amount
                FROM orders
                WHERE Order_id = $order_id
                  AND Customer_id = $customer_id
                LIMIT 1
            ";

            $order_result = mysqli_query($conn, $order_sql);

            if (!$order_result) {

                $error = "Could not verify the selected order.";

            } elseif (mysqli_num_rows($order_result) === 0) {

                $error = "The selected order could not be found.";

            } else {

                $order = mysqli_fetch_assoc($order_result);

                $order_plant_id = (int) $order["Plant_id"];

                /*
                 * Make sure the plant selected for exchange
                 * actually belongs to this order.
                 */

                if ($order_plant_id !== $offered) {

                    $error =
                        "The selected plant does not belong to this order.";
                }
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | IF NO ORDER ID WAS PROVIDED
            |--------------------------------------------------------------------------
            */

            $owned_sql = "
                SELECT 1
                FROM orders
                WHERE Customer_id = $customer_id
                  AND Plant_id = $offered
                  AND Amount > 0
                LIMIT 1
            ";

            $owned_result = mysqli_query($conn, $owned_sql);

            if (!$owned_result
                || mysqli_num_rows($owned_result) === 0) {

                $error =
                    "You can only offer a plant that you previously purchased.";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CONTINUE ONLY IF VALID
        |--------------------------------------------------------------------------
        */

        if ($error === "") {

            /*
            |--------------------------------------------------------------------------
            | GET OFFERED PLANT
            |--------------------------------------------------------------------------
            */

            $offered_sql = "
                SELECT
                    Plant_ID,
                    Plant_name,
                    Unit_price
                FROM plant
                WHERE Plant_ID = $offered
                LIMIT 1
            ";

            $offered_result = mysqli_query(
                $conn,
                $offered_sql
            );


            /*
            |--------------------------------------------------------------------------
            | GET RECEIVED PLANT
            |--------------------------------------------------------------------------
            */

            $received_sql = "
                SELECT
                    Plant_ID,
                    Plant_name,
                    Unit_price,
                    Stock_quantity
                FROM plant
                WHERE Plant_ID = $received
                LIMIT 1
            ";

            $received_result = mysqli_query(
                $conn,
                $received_sql
            );


            if (!$offered_result
                || mysqli_num_rows($offered_result) === 0) {

                $error = "The plant you want to exchange could not be found.";

            } elseif (
                !$received_result
                || mysqli_num_rows($received_result) === 0
            ) {

                $error = "The requested plant could not be found.";

            } else {

                $offered_plant =
                    mysqli_fetch_assoc($offered_result);

                $received_plant =
                    mysqli_fetch_assoc($received_result);


                /*
                |--------------------------------------------------------------------------
                | CHECK STOCK
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $received_plant["Stock_quantity"] <= 0
                ) {

                    $error =
                        "The requested plant is currently out of stock.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | CALCULATE PRICE DIFFERENCE
                    |--------------------------------------------------------------------------
                    */

                    $old_price =
                        (float) $offered_plant["Unit_price"];

                    $new_price =
                        (float) $received_plant["Unit_price"];

                    $difference =
                        round($new_price - $old_price, 2);


                    /*
                    |--------------------------------------------------------------------------
                    | PAYMENT INFORMATION
                    |--------------------------------------------------------------------------
                    |
                    | NOTHING IS PAID OR REFUNDED HERE.
                    |
                    | This information is only stored with the request.
                    |
                    */

                    if ($difference > 0) {

                        $payment_method =
                            "Cash on Delivery";

                        $payment_status =
                            "Pending approval - customer pays ৳"
                            . number_format($difference, 2)
                            . " COD";

                        $direction =
                            "Customer Pays";

                    } elseif ($difference < 0) {

                        $payment_method =
                            "Store Wallet Credit";

                        $payment_status =
                            "Pending approval - refund ৳"
                            . number_format(
                                abs($difference),
                                2
                            )
                            . " to store wallet after completion";

                        $direction =
                            "Store Refund";

                    } else {

                        $payment_method =
                            "N/A";

                        $payment_status =
                            "No price adjustment";

                        $direction =
                            "No Adjustment";
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT DUPLICATE ACTIVE REQUEST
                    |--------------------------------------------------------------------------
                    */

                    $duplicate_sql = "
                        SELECT exchange_id
                        FROM exchange
                        WHERE Customer_ID = $customer_id
                          AND Offered_plant_ID = $offered
                          AND Received_plant_ID = $received
                          AND status IN ('Pending', 'Approved')
                    ";

                    if ($order_id > 0) {

                        $duplicate_sql .=
                            " AND Order_ID = $order_id ";

                    } else {

                        $duplicate_sql .=
                            " AND Order_ID IS NULL ";
                    }

                    $duplicate_sql .= " LIMIT 1";


                    $duplicate_result =
                        mysqli_query(
                            $conn,
                            $duplicate_sql
                        );


                    if (
                        $duplicate_result
                        && mysqli_num_rows($duplicate_result) > 0
                    ) {

                        $error =
                            "An active exchange request already exists for this order.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | NOTES
                        |--------------------------------------------------------------------------
                        */

                        $notes =
                            "Customer requests exchange: "
                            . $offered_plant["Plant_name"]
                            . " → "
                            . $received_plant["Plant_name"];


                        /*
                        |--------------------------------------------------------------------------
                        | ESCAPE TEXT
                        |--------------------------------------------------------------------------
                        */

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

                        $direction_e =
                            mysqli_real_escape_string(
                                $conn,
                                $direction
                            );

                        $notes_e =
                            mysqli_real_escape_string(
                                $conn,
                                $notes
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | ORDER ID
                        |--------------------------------------------------------------------------
                        */

                        $order_value =
                            ($order_id > 0)
                            ? (string) $order_id
                            : "NULL";


                        /*
                        |--------------------------------------------------------------------------
                        | INSERT EXCHANGE REQUEST
                        |--------------------------------------------------------------------------
                        */

                        $insert_sql = "
                            INSERT INTO exchange
                            (
                                Exchange_date,
                                Exchange_value,
                                Received_plant_ID,
                                Customer_ID,
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
                                $offered,
                                $order_value,
                                'Pending',
                                '$payment_method_e',
                                '$payment_status_e',
                                '$direction_e',
                                '$notes_e'
                            )
                        ";


                        if (mysqli_query($conn, $insert_sql)) {

                            /*
                            |--------------------------------------------------------------------------
                            | UPDATE ORDER STATUS
                            |--------------------------------------------------------------------------
                            |
                            | @ prevents an error here from breaking
                            | the exchange request if Exchange_status
                            | does not exist.
                            |
                            */

                            if ($order_id > 0) {

                                @mysqli_query(
                                    $conn,
                                    "
                                    UPDATE orders
                                    SET Exchange_status = 'Pending'
                                    WHERE Order_id = $order_id
                                      AND Customer_id = $customer_id
                                    "
                                );
                            }


                            $message =
                                "Exchange request submitted successfully. "
                                . "Please wait for an employee to approve it.";

                            $offered_from_order =
                                $offered;

                        } else {

                            $error =
                                "Could not submit exchange request: "
                                . mysqli_error($conn);
                        }
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CUSTOMER'S PURCHASED PLANTS
|--------------------------------------------------------------------------
*/

$owned = mysqli_query(
    $conn,
    "
    SELECT DISTINCT
        p.Plant_ID,
        p.Plant_name,
        p.Unit_price
    FROM plant p
    INNER JOIN orders o
        ON o.Plant_id = p.Plant_ID
    WHERE o.Customer_id = $customer_id
      AND o.Amount > 0
    ORDER BY p.Plant_name
    "
);


/*
|--------------------------------------------------------------------------
| AVAILABLE PLANTS
|--------------------------------------------------------------------------
*/

$available = mysqli_query(
    $conn,
    "
    SELECT
        Plant_ID,
        Plant_name,
        Unit_price,
        Stock_quantity
    FROM plant
    WHERE Stock_quantity > 0
    ORDER BY Plant_name
    "
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Plant Exchange - Plant Hub</title>

<style>

* {
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #eef7f2;
    margin: 0;
    color: #1e293b;
}

.box {
    max-width: 620px;
    margin: 40px auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
}

.back {
    display: inline-block;
    margin-bottom: 18px;
    color: #0284c7;
    text-decoration: none;
    font-weight: 700;
}

h2 {
    color: #065f46;
    margin-top: 0;
}

.description {
    color: #64748b;
    line-height: 1.6;
}

.info {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 13px;
    border-radius: 8px;
    margin: 15px 0;
    line-height: 1.5;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 13px;
    border-radius: 8px;
    margin: 15px 0;
    line-height: 1.5;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 8px;
    margin: 15px 0;
    line-height: 1.5;
}

label {
    display: block;
    font-weight: 700;
    margin-top: 18px;
}

select {
    width: 100%;
    padding: 12px;
    margin-top: 7px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    font-size: 14px;
}

button {
    width: 100%;
    padding: 12px;
    margin-top: 22px;
    border: 0;
    border-radius: 8px;
    background: #10b981;
    color: #ffffff;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #059669;
}

.note {
    margin-top: 18px;
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
}

</style>

</head>

<body>

<?php

if (file_exists("header.php")) {
    include "header.php";
}

?>

<div class="box">

    <a
        class="back"
        href="my_orders.php"
    >
        ← Back to My Orders
    </a>

    <h2>
        🔄 Request Plant Exchange
    </h2>

    <p class="description">
        Select the plant you purchased and the plant you want to
        receive. Your request will first be sent to an employee
        for approval.
    </p>


    <?php if ($order_id > 0): ?>

        <div class="info">

            <strong>
                Order #<?= htmlspecialchars((string) $order_id) ?>
            </strong>

            <br>

            This exchange request is linked to your selected order.

        </div>

    <?php endif; ?>


    <?php if ($message): ?>

        <div class="success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="process_exchanges.php"
    >

        <input
            type="hidden"
            name="order_id"
            value="<?= (int) $order_id ?>"
        >


        <label for="offered_plant_id">
            Current Plant You Own
        </label>

        <select
            id="offered_plant_id"
            name="offered_plant_id"
            required
        >

            <option value="">
                Select your plant
            </option>


            <?php if ($owned): ?>

                <?php while ($p = mysqli_fetch_assoc($owned)): ?>

                    <?php

                    $selected =
                        (
                            (int) $p["Plant_ID"]
                            === (int) $offered_from_order
                        )
                        ? "selected"
                        : "";

                    ?>

                    <option
                        value="<?= (int) $p["Plant_ID"] ?>"
                        <?= $selected ?>
                    >

                        <?= htmlspecialchars($p["Plant_name"]) ?>

                        —
                        ৳<?= number_format(
                            (float) $p["Unit_price"],
                            2
                        ) ?>

                    </option>

                <?php endwhile; ?>

            <?php endif; ?>

        </select>


        <label for="received_plant_id">
            Plant You Want to Receive
        </label>

        <select
            id="received_plant_id"
            name="received_plant_id"
            required
        >

            <option value="">
                Select target plant
            </option>


            <?php if ($available): ?>

                <?php while ($p = mysqli_fetch_assoc($available)): ?>

                    <option
                        value="<?= (int) $p["Plant_ID"] ?>"
                    >

                        <?= htmlspecialchars($p["Plant_name"]) ?>

                        —
                        ৳<?= number_format(
                            (float) $p["Unit_price"],
                            2
                        ) ?>

                        (Stock:
                        <?= (int) $p["Stock_quantity"] ?>)

                    </option>

                <?php endwhile; ?>

            <?php endif; ?>

        </select>


        <button
            type="submit"
            name="submit_exchange"
        >
            Submit Exchange Request
        </button>

    </form>


    <div class="note">

        <strong>Price adjustment:</strong>

        <br>

        • If the new plant costs more, the extra amount will be
        payable by <strong>Cash on Delivery</strong> after approval.

        <br>

        • If the new plant costs less, the difference will be added
        to your <strong>Store Wallet</strong> after the exchange
        is completed.

        <br>

        • No payment, wallet credit, or stock change happens when
        the request is submitted.

        <br>

        • An employee must approve the request first.

    </div>

</div>

</body>

</html>