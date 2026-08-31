<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

/* =========================================================
   1. GET CUSTOMER ID FROM SESSION
   ========================================================= */

// Login.php stores the numeric Customer_ID here.
// Example: Customer_ID = 3
if (
    !isset($_SESSION['customer_id']) ||
    !is_numeric($_SESSION['customer_id']) ||
    (int)$_SESSION['customer_id'] <= 0
) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];


/* =========================================================
   2. VERIFY CUSTOMER EXISTS
   ========================================================= */

$customer_sql = "
    SELECT Customer_ID, Customer_name, points, wallet_balance
    FROM customer
    WHERE Customer_ID = ?
    LIMIT 1
";

$customer_stmt = mysqli_prepare($conn, $customer_sql);

if (!$customer_stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($customer_stmt, "i", $customer_id);
mysqli_stmt_execute($customer_stmt);

$customer_result = mysqli_stmt_get_result($customer_stmt);

if (!$customer_result || mysqli_num_rows($customer_result) === 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$customer = mysqli_fetch_assoc($customer_result);

$current_points = (int)($customer['points'] ?? 0);
$customer_name  = $customer['Customer_name'] ?? 'Customer';

mysqli_stmt_close($customer_stmt);


/* =========================================================
   3. GET CART
   ========================================================= */

$cart = $_SESSION['cart'] ?? [];

$subtotal = 0;

foreach ($cart as $item) {

    $price = (float)($item['price'] ?? 0);
    $quantity = (int)($item['quantity'] ?? 0);

    if ($quantity > 0 && $price >= 0) {
        $subtotal += $price * $quantity;
    }
}


/* =========================================================
   4. CHECK EMPTY CART
   ========================================================= */

$msg = "";
$error = "";

if (empty($cart)) {
    $error = "Your cart is empty.";
}


/* =========================================================
   5. PLACE ORDER
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    if (empty($cart)) {

        $error = "Your cart is empty. Please add a plant first.";

    } else {

        /* ---------------------------------------------
           Get points to redeem
        --------------------------------------------- */

        $points_to_redeem = (int)($_POST['points_redeemed'] ?? 0);

        // Prevent negative points
        if ($points_to_redeem < 0) {
            $points_to_redeem = 0;
        }

        // Prevent redeeming more points than customer owns
        if ($points_to_redeem > $current_points) {
            $points_to_redeem = $current_points;
        }

        // Prevent redeeming more than subtotal
        if ($points_to_redeem > $subtotal) {
            $points_to_redeem = (int)$subtotal;
        }


        /* ---------------------------------------------
           Calculate discount and final amount
        --------------------------------------------- */

        // 1 point = ৳1
        $discount = $points_to_redeem;

        $final_amount = max(0, $subtotal - $discount);


        /* ---------------------------------------------
           Loyalty points

           Rule:
           Earn 10 points for every complete ৳500 spent
           ONLY when no points are redeemed.
        --------------------------------------------- */

        $earned_points = 0;

        if ($points_to_redeem == 0) {
            $earned_points = (int)(floor($final_amount / 500) * 10);
        }


        /* =================================================
           6. START DATABASE TRANSACTION
           ================================================= */

        mysqli_begin_transaction($conn);

        try {

            /* ---------------------------------------------
               Insert every cart item into orders
            --------------------------------------------- */

            $order_sql = "
                INSERT INTO orders
                (
                    Customer_id,
                    Plant_id,
                    Amount,
                    Exchange_status,
                    points_redeemed
                )
                VALUES (?, ?, ?, 'None', ?)
            ";

            $order_stmt = mysqli_prepare($conn, $order_sql);

            if (!$order_stmt) {
                throw new Exception(
                    "Could not prepare order query: " . mysqli_error($conn)
                );
            }


            foreach ($cart as $item) {

                $pid = (int)($item['id'] ?? 0);

                $price = (float)($item['price'] ?? 0);

                $quantity = (int)($item['quantity'] ?? 0);

                $item_amt = $price * $quantity;


                // Validate plant ID
                if ($pid <= 0) {
                    throw new Exception("Invalid plant ID.");
                }

                // Validate quantity
                if ($quantity <= 0) {
                    throw new Exception("Invalid quantity.");
                }


                /*
                 * IMPORTANT:
                 *
                 * $customer_id comes directly from
                 * $_SESSION['customer_id'].
                 *
                 * Therefore the order is stored against
                 * the actual logged-in customer.
                 */

                mysqli_stmt_bind_param(
                    $order_stmt,
                    "iidi",
                    $customer_id,
                    $pid,
                    $item_amt,
                    $points_to_redeem
                );


                if (!mysqli_stmt_execute($order_stmt)) {

                    throw new Exception(
                        "Could not insert order: " .
                        mysqli_stmt_error($order_stmt)
                    );
                }
            }

            mysqli_stmt_close($order_stmt);


            /* =================================================
               7. UPDATE CUSTOMER POINTS
               ================================================= */

            $new_points =
                $current_points
                - $points_to_redeem
                + $earned_points;


            if ($new_points < 0) {
                $new_points = 0;
            }


            $update_sql = "
                UPDATE customer
                SET points = ?
                WHERE Customer_ID = ?
            ";

            $update_stmt = mysqli_prepare($conn, $update_sql);

            if (!$update_stmt) {
                throw new Exception(
                    "Could not prepare customer update: " .
                    mysqli_error($conn)
                );
            }


            mysqli_stmt_bind_param(
                $update_stmt,
                "ii",
                $new_points,
                $customer_id
            );


            if (!mysqli_stmt_execute($update_stmt)) {

                throw new Exception(
                    "Could not update customer points: " .
                    mysqli_stmt_error($update_stmt)
                );
            }


            mysqli_stmt_close($update_stmt);


            /* =================================================
               8. COMMIT TRANSACTION
               ================================================= */

            mysqli_commit($conn);


            /* ---------------------------------------------
               Sync session
            --------------------------------------------- */

            $_SESSION['points'] = $new_points;

            $_SESSION['cart'] = [];


            /* ---------------------------------------------
               Success message
            --------------------------------------------- */

            $msg =
                "🎉 Order placed successfully! " .
                "You earned " .
                $earned_points .
                " points. " .
                "New balance: " .
                $new_points .
                " pts.";

        } catch (Exception $e) {

            // Undo all database changes if anything failed
            mysqli_rollback($conn);

            $error = "❌ Order failed: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout - Plant Hub</title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef7f2;
            margin: 0;
            padding: 30px;
            color: #1e293b;
        }

        .card {
            background: white;
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-top: 0;
            color: #065f46;
        }

        .customer {
            background: #f0fdf4;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #166534;
        }

        .summary {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .summary p {
            margin: 8px 0;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            color: #065f46;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input[type="number"] {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 13px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }

        .btn:hover {
            background: #059669;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .links {
            margin-top: 20px;
            text-align: center;
        }

        .links a {
            color: #047857;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .points-note {
            font-size: 13px;
            color: #64748b;
            margin-top: -8px;
            margin-bottom: 15px;
        }

    </style>

</head>


<body>


<div class="card">

    <h2>Checkout 🛒</h2>


    <?php if ($msg): ?>

        <div class="success">
            <?php echo htmlspecialchars($msg); ?>
        </div>

        <div class="links">

            <a href="my_orders.php">
                View My Orders 📦
            </a>

            &nbsp; | &nbsp;

            <a href="shop.php">
                Continue Shopping 🌿
            </a>

        </div>


    <?php else: ?>


        <?php if ($error): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($cart)): ?>


            <div class="customer">

                👤 <strong>
                    <?php echo htmlspecialchars($customer_name); ?>
                </strong>

                <br>

                Customer ID:
                <strong>
                    <?php echo $customer_id; ?>
                </strong>

            </div>


            <div class="summary">

                <p>
                    <strong>Subtotal:</strong>
                    ৳<?php echo number_format($subtotal, 2); ?>
                </p>

                <p>
                    <strong>Available Points:</strong>
                    <?php echo $current_points; ?> pts
                </p>

                <p class="points-note">
                    1 point = ৳1 discount
                </p>

            </div>


            <form method="POST">


                <label for="points_redeemed">
                    Points to Redeem
                </label>


                <input
                    type="number"
                    id="points_redeemed"
                    name="points_redeemed"
                    min="0"
                    max="<?php echo min($current_points, (int)$subtotal); ?>"
                    value="0"
                >


                <p class="points-note">
                    Maximum redeemable:
                    <?php echo min($current_points, (int)$subtotal); ?>
                    points
                </p>


                <button
                    type="submit"
                    name="place_order"
                    class="btn"
                >
                    Place Order
                </button>


            </form>


            <div class="links">

                <a href="cart.php">
                    ← Back to Cart
                </a>

            </div>


        <?php endif; ?>


    <?php endif; ?>


</div>


</body>

</html>