<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "DBconnect.php";

mysqli_report(MYSQLI_REPORT_OFF);


/* =========================================================
   1. GET LOGGED-IN CUSTOMER ID
   ========================================================= */

$raw_id = null;

/*
 * login.php stores:
 *
 * $_SESSION['customer_id'] = numeric Customer ID
 * $_SESSION['user_id']     = C + Customer ID
 *
 * Check all possible session keys.
 */

if (isset($_SESSION['customer_id'])) {

    $raw_id = $_SESSION['customer_id'];

} elseif (isset($_SESSION['Customer_id'])) {

    $raw_id = $_SESSION['Customer_id'];

} elseif (isset($_SESSION['Customer_ID'])) {

    $raw_id = $_SESSION['Customer_ID'];

} elseif (isset($_SESSION['user_id'])) {

    $raw_id = $_SESSION['user_id'];
}


/*
 * Convert:
 *
 * C5 -> 5
 * c5 -> 5
 * 5  -> 5
 */

$customer_id = (int)preg_replace(
    '/[^0-9]/',
    '',
    (string)$raw_id
);


/* =========================================================
   2. DO NOT USE A FAKE CUSTOMER ID
   ========================================================= */

if ($customer_id <= 0) {

    header("Location: login.php");
    exit();
}


/* =========================================================
   3. VERIFY CUSTOMER EXISTS
   ========================================================= */

$customer_sql = "
    SELECT
        Customer_ID,
        Customer_name,
        points,
        Loyalty_points,
        wallet_balance
    FROM customer
    WHERE Customer_ID = ?
    LIMIT 1
";

$customer_stmt = mysqli_prepare(
    $conn,
    $customer_sql
);

if (!$customer_stmt) {

    die(
        "Customer query failed: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $customer_stmt,
    "i",
    $customer_id
);

mysqli_stmt_execute(
    $customer_stmt
);

$customer_result = mysqli_stmt_get_result(
    $customer_stmt
);


if (
    !$customer_result ||
    mysqli_num_rows($customer_result) === 0
) {

    mysqli_stmt_close($customer_stmt);

    session_unset();

    header("Location: login.php");
    exit();
}


$customer = mysqli_fetch_assoc(
    $customer_result
);

mysqli_stmt_close($customer_stmt);


/* =========================================================
   4. CUSTOMER BALANCES
   ========================================================= */

$points_balance = (int)(
    $customer['points']
    ?? $customer['Loyalty_points']
    ?? 0
);

$wallet_balance = (float)(
    $customer['wallet_balance']
    ?? 0
);


/*
 * Keep header/session points synchronized.
 */

$_SESSION['points'] = $points_balance;


/* =========================================================
   5. INITIALIZE CART
   ========================================================= */

if (!isset($_SESSION['cart'])) {

    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];


/* =========================================================
   6. CART QUANTITY ADJUSTMENTS
   ========================================================= */

if (
    isset($_GET['action']) &&
    isset($_GET['id'])
) {

    $action = $_GET['action'];
    $item_id = (string)$_GET['id'];


    /*
     * Only modify an item that actually exists
     * in the customer's current session cart.
     */

    if (isset($_SESSION['cart'][$item_id])) {

        if ($action === 'add') {

            $_SESSION['cart'][$item_id]['quantity']++;

        } elseif ($action === 'remove') {

            $_SESSION['cart'][$item_id]['quantity']--;

            if (
                $_SESSION['cart'][$item_id]['quantity'] <= 0
            ) {

                unset(
                    $_SESSION['cart'][$item_id]
                );
            }
        }
    }


    header("Location: cart.php");
    exit();
}


/* =========================================================
   7. REFRESH CART
   ========================================================= */

$cart = $_SESSION['cart'];


/* =========================================================
   8. CALCULATE SUBTOTAL
   ========================================================= */

$subtotal = 0.00;

foreach ($cart as $item) {

    $price = (float)(
        $item['price'] ?? 0
    );

    $quantity = (int)(
        $item['quantity'] ?? 0
    );

    if (
        $price > 0 &&
        $quantity > 0
    ) {

        $subtotal +=
            $price * $quantity;
    }
}


$subtotal = round(
    $subtotal,
    2
);


/* =========================================================
   9. MESSAGES
   ========================================================= */

$msg = "";
$error = "";


/* =========================================================
   10. COMPLETE ORDER
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['complete_order'])
) {


    /* -----------------------------------------------------
       Check cart
       ----------------------------------------------------- */

    if (empty($cart)) {

        $error =
            "❌ Your cart is empty.";

    } else {


        /* -------------------------------------------------
           Payment method
           ------------------------------------------------- */

        $payment_method =
            $_POST['payment_method'] ?? 'COD';


        $allowed_methods = [
            'COD',
            'points',
            'wallet'
        ];


        if (
            !in_array(
                $payment_method,
                $allowed_methods,
                true
            )
        ) {

            $payment_method = 'COD';
        }


        $points_used = 0;


        /* -------------------------------------------------
           PAYMENT VALIDATION
           ------------------------------------------------- */

        if ($payment_method === 'points') {


            /*
             * Existing project logic:
             *
             * 1 point = 1 taka
             */

            if (
                $points_balance <
                $subtotal
            ) {

                $error =
                    "❌ Insufficient loyalty points balance!";

            } else {

                $points_used =
                    (int)$subtotal;
            }
        }


        elseif (
            $payment_method === 'wallet'
        ) {


            if (
                $wallet_balance <
                $subtotal
            ) {

                $error =
                    "❌ Insufficient store wallet balance!";

            }
        }


        /* -------------------------------------------------
           CHECK AGAIN BEFORE SAVING
           ------------------------------------------------- */

        if ($error === "") {


            /*
             * Re-check customer from DB.
             *
             * This prevents an order being saved
             * against an invalid customer.
             */

            $verify_sql = "
                SELECT
                    Customer_ID,
                    points,
                    Loyalty_points,
                    wallet_balance
                FROM customer
                WHERE Customer_ID = ?
                LIMIT 1
            ";

            $verify_stmt = mysqli_prepare(
                $conn,
                $verify_sql
            );


            if (!$verify_stmt) {

                $error =
                    "❌ Could not verify customer.";

            } else {


                mysqli_stmt_bind_param(
                    $verify_stmt,
                    "i",
                    $customer_id
                );

                mysqli_stmt_execute(
                    $verify_stmt
                );

                $verify_result =
                    mysqli_stmt_get_result(
                        $verify_stmt
                    );


                if (
                    !$verify_result ||
                    mysqli_num_rows(
                        $verify_result
                    ) === 0
                ) {

                    $error =
                        "❌ Customer account could not be verified.";

                }


                mysqli_stmt_close(
                    $verify_stmt
                );
            }
        }


        /* -------------------------------------------------
           SAVE ORDER
           ------------------------------------------------- */

        if ($error === "") {


            /*
             * Points earned:
             *
             * 10 points per ৳500 spent
             * ONLY when points were NOT used.
             */

            $earned_points = 0;


            if ($points_used === 0) {

                $earned_points =
                    (int)floor(
                        $subtotal / 500
                    ) * 10;
            }


            /*
             * Start transaction.
             *
             * This makes sure the order,
             * wallet/points and stock updates
             * happen together.
             */

            mysqli_begin_transaction(
                $conn
            );


            $transaction_ok = true;


            /* =================================================
               11. WALLET PAYMENT
               ================================================= */

            if (
                $payment_method === 'wallet'
            ) {

                $new_wallet =
                    $wallet_balance -
                    $subtotal;


                $wallet_sql = "
                    UPDATE customer
                    SET wallet_balance = ?
                    WHERE Customer_ID = ?
                ";


                $wallet_stmt =
                    mysqli_prepare(
                        $conn,
                        $wallet_sql
                    );


                if (!$wallet_stmt) {

                    $transaction_ok = false;

                } else {

                    mysqli_stmt_bind_param(
                        $wallet_stmt,
                        "di",
                        $new_wallet,
                        $customer_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $wallet_stmt
                        )
                    ) {

                        $transaction_ok = false;
                    }


                    mysqli_stmt_close(
                        $wallet_stmt
                    );
                }
            }


            /* =================================================
               12. INSERT ORDERS
               ================================================= */

            if ($transaction_ok) {


                $order_sql = "
                    INSERT INTO orders
                    (
                        Customer_id,
                        Plant_id,
                        Amount,
                        Exchange_status,
                        points_redeemed
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'None',
                        ?
                    )
                ";


                $order_stmt =
                    mysqli_prepare(
                        $conn,
                        $order_sql
                    );


                if (!$order_stmt) {

                    $transaction_ok = false;

                } else {


                    foreach (
                        $cart as $key => $item
                    ) {


                        /*
                         * Plant ID comes from shop.php:
                         *
                         * 'id'
                         */

                        $pid = (int)(
                            $item['id']
                            ?? $key
                        );


                        $quantity = (int)(
                            $item['quantity']
                            ?? 0
                        );


                        $price = (float)(
                            $item['price']
                            ?? 0
                        );


                        /*
                         * Basic validation.
                         */

                        if (
                            $pid <= 0 ||
                            $quantity <= 0 ||
                            $price < 0
                        ) {

                            $transaction_ok = false;
                            break;
                        }


                        /*
                         * IMPORTANT:
                         *
                         * Get the current plant from DB.
                         * Do not blindly trust the price
                         * coming from the browser/session.
                         */

                        $plant_sql = "
                            SELECT
                                Plant_ID,
                                Plant_name,
                                Unit_price,
                                Stock_quantity
                            FROM plant
                            WHERE Plant_ID = ?
                            LIMIT 1
                        ";


                        $plant_stmt =
                            mysqli_prepare(
                                $conn,
                                $plant_sql
                            );


                        if (!$plant_stmt) {

                            $transaction_ok = false;
                            break;
                        }


                        mysqli_stmt_bind_param(
                            $plant_stmt,
                            "i",
                            $pid
                        );


                        mysqli_stmt_execute(
                            $plant_stmt
                        );


                        $plant_result =
                            mysqli_stmt_get_result(
                                $plant_stmt
                            );


                        if (
                            !$plant_result ||
                            mysqli_num_rows(
                                $plant_result
                            ) === 0
                        ) {

                            mysqli_stmt_close(
                                $plant_stmt
                            );

                            $transaction_ok = false;
                            break;
                        }


                        $plant =
                            mysqli_fetch_assoc(
                                $plant_result
                            );


                        mysqli_stmt_close(
                            $plant_stmt
                        );


                        /*
                         * Check stock.
                         */

                        $stock_quantity =
                            (int)(
                                $plant[
                                    'Stock_quantity'
                                ] ?? 0
                            );


                        if (
                            $stock_quantity <
                            $quantity
                        ) {

                            $error =
                                "❌ Not enough stock for " .
                                (
                                    $plant[
                                        'Plant_name'
                                    ]
                                    ?? 'this plant'
                                ) .
                                ".";

                            $transaction_ok = false;
                            break;
                        }


                        /*
                         * Use database price.
                         */

                        $db_price =
                            (float)(
                                $plant[
                                    'Unit_price'
                                ] ?? $price
                            );


                        $amount =
                            $db_price *
                            $quantity;


                        $amount =
                            round(
                                $amount,
                                2
                            );


                        /*
                         * Insert order.
                         *
                         * THIS IS THE IMPORTANT PART:
                         *
                         * Customer_id = logged-in
                         * customer's REAL ID.
                         */

                        mysqli_stmt_bind_param(
                            $order_stmt,
                            "iidi",
                            $customer_id,
                            $pid,
                            $amount,
                            $points_used
                        );


                        if (
                            !mysqli_stmt_execute(
                                $order_stmt
                            )
                        ) {

                            $error =
                                "❌ Failed to save order: " .
                                mysqli_stmt_error(
                                    $order_stmt
                                );

                            $transaction_ok = false;
                            break;
                        }


                        /*
                         * Deduct stock.
                         */

                        $stock_sql = "
                            UPDATE plant
                            SET Stock_quantity =
                                Stock_quantity - ?
                            WHERE Plant_ID = ?
                              AND Stock_quantity >= ?
                        ";


                        $stock_stmt =
                            mysqli_prepare(
                                $conn,
                                $stock_sql
                            );


                        if (!$stock_stmt) {

                            $transaction_ok = false;
                            break;
                        }


                        mysqli_stmt_bind_param(
                            $stock_stmt,
                            "iii",
                            $quantity,
                            $pid,
                            $quantity
                        );


                        if (
                            !mysqli_stmt_execute(
                                $stock_stmt
                            ) ||
                            mysqli_stmt_affected_rows(
                                $stock_stmt
                            ) !== 1
                        ) {

                            mysqli_stmt_close(
                                $stock_stmt
                            );

                            $error =
                                "❌ Could not update plant stock.";

                            $transaction_ok = false;
                            break;
                        }


                        mysqli_stmt_close(
                            $stock_stmt
                        );
                    }


                    mysqli_stmt_close(
                        $order_stmt
                    );
                }
            }


            /* =================================================
               13. UPDATE POINTS
               ================================================= */

            if ($transaction_ok) {


                $new_points_balance =
                    max(
                        0,
                        $points_balance
                        - $points_used
                        + $earned_points
                    );


                $points_sql = "
                    UPDATE customer
                    SET
                        points = ?,
                        Loyalty_points = ?
                    WHERE Customer_ID = ?
                ";


                $points_stmt =
                    mysqli_prepare(
                        $conn,
                        $points_sql
                    );


                if (!$points_stmt) {

                    $transaction_ok = false;

                } else {


                    mysqli_stmt_bind_param(
                        $points_stmt,
                        "iii",
                        $new_points_balance,
                        $new_points_balance,
                        $customer_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $points_stmt
                        )
                    ) {

                        $transaction_ok = false;
                    }


                    mysqli_stmt_close(
                        $points_stmt
                    );
                }
            }


            /* =================================================
               14. COMMIT OR ROLLBACK
               ================================================= */

            if ($transaction_ok) {


                mysqli_commit(
                    $conn
                );


                /*
                 * Empty cart ONLY after
                 * successful database commit.
                 */

                $_SESSION['cart'] = [];

                $_SESSION['points'] =
                    $new_points_balance;


                /*
                 * Update displayed balances.
                 */

                $points_balance =
                    $new_points_balance;


                if (
                    $payment_method === 'wallet'
                ) {

                    $wallet_balance =
                        $wallet_balance -
                        $subtotal;
                }


                $cart = [];

                $subtotal = 0;


                $msg =
                    "🎉 Order completed successfully!";


                if ($points_used > 0) {

                    $msg .=
                        " Used: " .
                        $points_used .
                        " pts.";
                }


                if ($earned_points > 0) {

                    $msg .=
                        " Earned: +" .
                        $earned_points .
                        " pts.";
                }


            } else {


                /*
                 * Something failed.
                 */

                mysqli_rollback(
                    $conn
                );


                if ($error === "") {

                    $error =
                        "❌ Order could not be completed. No changes were saved.";
                }
            }
        }
    }
}


/* =========================================================
   15. FINAL CART DATA
   ========================================================= */

$cart = $_SESSION['cart'] ?? [];


$subtotal = 0.00;


foreach ($cart as $item) {

    $price = (float)(
        $item['price'] ?? 0
    );

    $quantity = (int)(
        $item['quantity'] ?? 0
    );


    if (
        $price > 0 &&
        $quantity > 0
    ) {

        $subtotal +=
            $price * $quantity;
    }
}


$subtotal =
    round(
        $subtotal,
        2
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

    <title>My Cart - Plant Hub</title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;

            font-family:
                'Segoe UI',
                Arial,
                sans-serif;

            background: #eef7f2;

            color: #1e293b;
        }


        .container {
            max-width: 1000px;

            margin: 0 auto;
        }


        .stats-grid {
            display: flex;

            gap: 20px;

            margin-bottom: 20px;
        }


        .stat-card {
            flex: 1;

            padding: 20px;

            border-radius: 12px;

            color: white;

            font-weight: bold;

            box-shadow:
                0 4px 10px
                rgba(0,0,0,0.08);
        }


        .wallet-card {
            background: #064e3b;
        }


        .points-card {
            background: #10b981;
        }


        .stat-card small {
            display: block;

            opacity: 0.9;

            margin-bottom: 7px;
        }


        .stat-card h2 {
            margin: 0;
        }


        .cart-card {
            background: white;

            padding: 28px;

            border-radius: 14px;

            box-shadow:
                0 4px 14px
                rgba(0,0,0,0.06);
        }


        .cart-card h2 {
            margin-top: 0;

            color: #065f46;
        }


        .customer-info {
            background: #f0fdf4;

            border: 1px solid #bbf7d0;

            color: #166534;

            padding: 12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        .cart-table {
            width: 100%;

            border-collapse: collapse;

            margin-bottom: 20px;
        }


        .cart-table th,
        .cart-table td {
            padding: 13px;

            text-align: left;

            border-bottom:
                1px solid #e2e8f0;
        }


        .cart-table th {
            background: #f8fafc;

            color: #334155;
        }


        .qty-btn {
            display: inline-block;

            background: #e2e8f0;

            border: none;

            padding: 4px 10px;

            border-radius: 5px;

            font-weight: bold;

            cursor: pointer;

            text-decoration: none;

            color: #0f172a;
        }


        .qty-btn:hover {
            background: #cbd5e1;
        }


        .form-control {
            width: 100%;

            padding: 12px;

            margin: 10px 0;

            border:
                1px solid #cbd5e1;

            border-radius: 7px;

            font-size: 15px;

            background: white;
        }


        .btn {
            background: #10b981;

            color: white;

            border: none;

            padding: 15px;

            width: 100%;

            border-radius: 8px;

            font-weight: bold;

            font-size: 16px;

            cursor: pointer;
        }


        .btn:hover {
            background: #059669;
        }


        .continue {
            display: inline-block;

            margin-top: 10px;

            color: #065f46;

            font-weight: 700;

            text-decoration: none;
        }


        .alert-error {
            background: #fee2e2;

            color: #991b1b;

            padding: 13px;

            border-radius: 7px;

            margin-bottom: 15px;

            border:
                1px solid #fecaca;
        }


        .alert-success {
            background: #d1fae5;

            color: #065f46;

            padding: 13px;

            border-radius: 7px;

            margin-bottom: 15px;

            border:
                1px solid #a7f3d0;
        }


        .empty-cart {
            text-align: center;

            padding: 45px 20px;

            color: #64748b;
        }


        .empty-cart-icon {
            font-size: 48px;

            margin-bottom: 10px;
        }


        .total {
            text-align: right;

            font-size: 20px;

            color: #065f46;

            margin: 20px 0;
        }


        .payment-label {
            font-weight: bold;

            display: block;

            margin-top: 15px;
        }


        @media (max-width: 700px) {

            .stats-grid {
                flex-direction: column;
            }


            .cart-card {
                padding: 18px;
            }


            .cart-table {
                min-width: 700px;
            }


            .table-wrapper {
                overflow-x: auto;
            }
        }

    </style>

</head>


<body>


<?php

if (file_exists("header.php")) {

    include "header.php";
}

?>


<div class="container">


    <!-- =================================================
         BALANCE CARDS
         ================================================= -->

    <div class="stats-grid">


        <div class="stat-card wallet-card">

            <small>
                STORE WALLET BALANCE 💳
            </small>

            <h2>
                ৳<?php
                echo number_format(
                    $wallet_balance,
                    2
                );
                ?>
            </h2>

        </div>


        <div class="stat-card points-card">

            <small>
                LOYALTY POINTS BALANCE ⭐
            </small>

            <h2>
                <?php
                echo number_format(
                    $points_balance
                );
                ?>
                PTS
            </h2>

        </div>


    </div>


    <!-- =================================================
         CART CARD
         ================================================= -->

    <div class="cart-card">


        <h2>
            Your Shopping Cart 🛒
        </h2>


        <div class="customer-info">

            👤 Logged in as Customer ID:

            <strong>
                <?php
                echo $customer_id;
                ?>
            </strong>

            &nbsp;—

            <strong>
                <?php
                echo htmlspecialchars(
                    $customer['Customer_name']
                    ?? 'Customer'
                );
                ?>
            </strong>

        </div>


        <!-- =================================================
             MESSAGES
             ================================================= -->

        <?php if ($msg): ?>

            <div class="alert-success">

                <?php
                echo htmlspecialchars(
                    $msg
                );
                ?>

            </div>

            <a
                href="shop.php"
                class="continue"
            >
                ← Continue Shopping
            </a>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert-error">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>


        <?php if (empty($cart)): ?>


            <!-- =================================================
                 EMPTY CART
                 ================================================= -->

            <div class="empty-cart">

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h3>
                    Your cart is empty
                </h3>

                <p>
                    Add some plants from the shop
                    to place an order.
                </p>

                <a
                    href="shop.php"
                    class="continue"
                >
                    🌱 Go Shopping
                </a>

            </div>


        <?php else: ?>


            <!-- =================================================
                 CART ITEMS
                 ================================================= -->

            <div
                style="overflow-x:auto;"
            >

                <table class="cart-table">


                    <thead>

                        <tr>

                            <th>
                                Plant Name
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Qty
                            </th>

                            <th>
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $cart as $id => $item
                    ): ?>


                        <?php

                        $item_price =
                            (float)(
                                $item['price']
                                ?? 0
                            );

                        $item_quantity =
                            (int)(
                                $item['quantity']
                                ?? 0
                            );

                        $item_subtotal =
                            $item_price *
                            $item_quantity;

                        ?>


                        <tr>


                            <!-- PLANT NAME -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $item['name']
                                        ?? 'Plant'
                                    );

                                    ?>

                                </strong>

                            </td>


                            <!-- PRICE -->

                            <td
                                style="
                                    color:#059669;
                                    font-weight:bold;
                                "
                            >

                                ৳<?php

                                echo number_format(
                                    $item_price,
                                    2
                                );

                                ?>

                            </td>


                            <!-- QUANTITY -->

                            <td>

                                <a
                                    href="cart.php?action=remove&id=<?php echo urlencode($id); ?>"
                                    class="qty-btn"
                                >
                                    −
                                </a>


                                <span
                                    style="
                                        margin:0 8px;
                                        font-weight:bold;
                                    "
                                >

                                    <?php
                                    echo $item_quantity;
                                    ?>

                                </span>


                                <a
                                    href="cart.php?action=add&id=<?php echo urlencode($id); ?>"
                                    class="qty-btn"
                                >
                                    +
                                </a>

                            </td>


                            <!-- SUBTOTAL -->

                            <td
                                style="
                                    color:#059669;
                                    font-weight:bold;
                                "
                            >

                                ৳<?php

                                echo number_format(
                                    $item_subtotal,
                                    2
                                );

                                ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>

            </div>


            <!-- =================================================
                 PAYMENT
                 ================================================= -->

            <form
                method="POST"
                action="cart.php"
            >


                <label class="payment-label">

                    Select Payment Method:

                </label>


                <select
                    name="payment_method"
                    class="form-control"
                    required
                >


                    <!-- COD -->

                    <option value="COD">

                        Pay on Delivery / Pickup

                    </option>


                    <!-- POINTS -->

                    <option
                        value="points"
                        <?php

                        if (
                            $points_balance <
                            $subtotal
                        ) {

                            echo 'disabled';
                        }

                        ?>
                    >

                        Pay with Loyalty Points

                        (Available:

                        <?php
                        echo $points_balance;
                        ?>

                        PTS)

                        <?php

                        if (
                            $points_balance <
                            $subtotal
                        ) {

                            echo ' - Insufficient Points';
                        }

                        ?>

                    </option>


                    <!-- WALLET -->

                    <option
                        value="wallet"
                        <?php

                        if (
                            $wallet_balance <
                            $subtotal
                        ) {

                            echo 'disabled';
                        }

                        ?>
                    >

                        Pay with Store Wallet

                        (Available:

                        ৳<?php

                        echo number_format(
                            $wallet_balance,
                            2
                        );

                        ?>)

                    </option>


                </select>


                <!-- TOTAL -->

                <div class="total">

                    <strong>

                        Final Total:

                        ৳<?php

                        echo number_format(
                            $subtotal,
                            2
                        );

                        ?>

                    </strong>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    name="complete_order"
                    class="btn"
                >

                    Confirm & Complete Purchase Order ↗

                </button>


            </form>


        <?php endif; ?>


    </div>


</div>


</body>

</html>