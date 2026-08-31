<?php
/*
=========================================================
    PLANT HUB - MY ORDERS
    Shows all orders belonging to the logged-in customer
=========================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "DBconnect.php";

/* -------------------------------------------------------
   1. GET CUSTOMER ID FROM SESSION
------------------------------------------------------- */

$raw_customer_id = null;

if (isset($_SESSION['customer_id'])) {
    $raw_customer_id = $_SESSION['customer_id'];
} elseif (isset($_SESSION['Customer_id'])) {
    $raw_customer_id = $_SESSION['Customer_id'];
} elseif (isset($_SESSION['Customer_ID'])) {
    $raw_customer_id = $_SESSION['Customer_ID'];
} elseif (isset($_SESSION['user_id'])) {
    $raw_customer_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['id'])) {
    $raw_customer_id = $_SESSION['id'];
}

/*
   Convert values such as:
   11
   C11
   CUST-11
   customer11
   into 11
*/
$customer_id = (int) preg_replace('/[^0-9]/', '', (string)$raw_customer_id);

if ($customer_id <= 0) {
    header("Location: login.php");
    exit;
}

/* -------------------------------------------------------
   2. GET CUSTOMER NAME
------------------------------------------------------- */

$customer_name = "";

$customer_sql = "
    SELECT Customer_name
    FROM customer
    WHERE Customer_ID = $customer_id
    LIMIT 1
";

$customer_result = mysqli_query($conn, $customer_sql);

if ($customer_result && mysqli_num_rows($customer_result) > 0) {
    $customer_row = mysqli_fetch_assoc($customer_result);
    $customer_name = $customer_row['Customer_name'] ?? "";
}

/* -------------------------------------------------------
   3. GET ALL ORDERS FOR THIS CUSTOMER
------------------------------------------------------- */

$orders_sql = "
    SELECT
        o.Order_id,
        o.Customer_id,
        o.Plant_id,
        o.Amount,
        o.Order_date,
        o.Exchange_status,
        o.points_redeemed,
        p.Plant_name,
        p.Unit_price
    FROM orders o
    LEFT JOIN plant p
        ON o.Plant_id = p.Plant_ID
    WHERE o.Customer_id = $customer_id
    ORDER BY o.Order_id DESC
";

$result = mysqli_query($conn, $orders_sql);

$query_error = "";

if (!$result) {
    $query_error = mysqli_error($conn);
}

/* -------------------------------------------------------
   4. HELPER FOR ESCAPING OUTPUT
------------------------------------------------------- */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders - Plant Hub</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef7f2;
            color: #1e293b;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 35px auto;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.06);
        }

        .title {
            margin: 0;
            color: #065f46;
            font-size: 28px;
        }

        .subtitle {
            color: #64748b;
            margin-top: 8px;
            margin-bottom: 25px;
        }

        .customer-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 600;
        }

        .error-box {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 14px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .orders-table th {
            background: #f1f5f9;
            color: #334155;
            text-align: left;
            padding: 14px 12px;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }

        .orders-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .orders-table tr:hover {
            background: #f8fffb;
        }

        .order-id {
            font-weight: 800;
            color: #334155;
        }

        .plant-name {
            font-weight: 700;
            color: #1e293b;
        }

        .amount {
            font-weight: 800;
            color: #059669;
        }

        .points {
            color: #475569;
            font-weight: 600;
        }

        /* ------------------------------------------------
           STATUS BADGES
        ------------------------------------------------ */

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-none {
            background: #f1f5f9;
            color: #475569;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ------------------------------------------------
           EXCHANGE BUTTON
        ------------------------------------------------ */

        .exchange-btn {
            display: inline-block;
            background: #0284c7;
            color: white;
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
            white-space: nowrap;
        }

        .exchange-btn:hover {
            background: #0369a1;
        }

        .disabled-btn {
            display: inline-block;
            background: #e2e8f0;
            color: #64748b;
            padding: 9px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }

        .shop-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 18px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
        }

        .shop-btn:hover {
            background: #059669;
        }

        @media (max-width: 700px) {

            .container {
                width: 98%;
                margin: 15px auto;
            }

            .card {
                padding: 18px;
            }

            .title {
                font-size: 23px;
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

    <div class="card">

        <h1 class="title">My Orders 📦</h1>

        <p class="subtitle">
            View all your purchases and manage plant exchanges.
        </p>

        <div class="customer-box">
            👤 Customer:
            <?php echo e($customer_name); ?>
            &nbsp; | &nbsp;
            Customer ID: <?php echo e($customer_id); ?>
        </div>

        <?php if ($query_error !== ""): ?>

            <div class="error-box">
                ❌ Unable to load your orders.
                <br>
                <?php echo e($query_error); ?>
            </div>

        <?php endif; ?>

        <div class="table-wrapper">

            <table class="orders-table">

                <thead>

                    <tr>
                        <th>Order #</th>
                        <th>Plant</th>
                        <th>Amount</th>
                        <th>Order Date</th>
                        <th>Points</th>
                        <th>Exchange Status</th>
                        <th>Exchange</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <?php

                        $order_id = (int)($row['Order_id'] ?? 0);

                        $plant_id = (int)($row['Plant_id'] ?? 0);

                        $plant_name = $row['Plant_name'] ?? "Unknown Plant";

                        $amount = (float)($row['Amount'] ?? 0);

                        $order_date = $row['Order_date'] ?? "";

                        $points = (int)($row['points_redeemed'] ?? 0);

                        $exchange_status =
                            $row['Exchange_status'] ??
                            "None";

                        $status_lower = strtolower(
                            trim((string)$exchange_status)
                        );

                        ?>

                        <tr>

                            <!-- ORDER ID -->

                            <td class="order-id">
                                #<?php echo $order_id; ?>
                            </td>

                            <!-- PLANT -->

                            <td class="plant-name">
                                🌱 <?php echo e($plant_name); ?>
                            </td>

                            <!-- AMOUNT -->

                            <td class="amount">
                                ৳<?php echo number_format($amount, 2); ?>
                            </td>

                            <!-- DATE -->

                            <td>
                                <?php
                                if (!empty($order_date)) {
                                    echo e(date("d M Y, h:i A", strtotime($order_date)));
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <!-- POINTS -->

                            <td class="points">
                                <?php echo $points; ?> PTS
                            </td>

                            <!-- EXCHANGE STATUS -->

                            <td>

                                <?php if ($status_lower === "pending"): ?>

                                    <span class="status status-pending">
                                        ⏳ Exchange Pending
                                    </span>

                                <?php elseif ($status_lower === "approved"): ?>

                                    <span class="status status-approved">
                                        ✓ Exchange Approved
                                    </span>

                                <?php elseif (
                                    $status_lower === "completed" ||
                                    $status_lower === "complete"
                                ): ?>

                                    <span class="status status-completed">
                                        ✓ Exchange Completed
                                    </span>

                                <?php elseif ($status_lower === "rejected"): ?>

                                    <span class="status status-rejected">
                                        ✕ Exchange Rejected
                                    </span>

                                <?php else: ?>

                                    <span class="status status-none">
                                        No Exchange
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- EXCHANGE ACTION -->

                            <td>

                                <?php

                                /*
                                 * Customer can request an exchange only
                                 * when there isn't already an active/completed
                                 * exchange for this order.
                                 */

                                if (
                                    $plant_id > 0 &&
                                    $status_lower !== "pending" &&
                                    $status_lower !== "approved" &&
                                    $status_lower !== "completed" &&
                                    $status_lower !== "complete"
                                ):

                                ?>

                                    <a
                                        class="exchange-btn"
                                        href="process_exchanges.php?order_id=<?php echo $order_id; ?>&offered_plant_id=<?php echo $plant_id; ?>"
                                    >
                                        🔄 Request Exchange
                                    </a>

                                <?php elseif ($status_lower === "pending"): ?>

                                    <span class="disabled-btn">
                                        ⏳ Waiting for Approval
                                    </span>

                                <?php elseif ($status_lower === "approved"): ?>

                                    <span class="disabled-btn">
                                        ✓ Approved
                                    </span>

                                <?php elseif (
                                    $status_lower === "completed" ||
                                    $status_lower === "complete"
                                ): ?>

                                    <span class="disabled-btn">
                                        ✓ Completed
                                    </span>

                                <?php else: ?>

                                    <span class="disabled-btn">
                                        Not Available
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" class="empty">

                            <div style="font-size: 40px;">
                                📦
                            </div>

                            <h3>
                                No Order History Found
                            </h3>

                            <p>
                                Your completed purchases will appear here.
                            </p>

                            <a
                                href="shop.php"
                                class="shop-btn"
                            >
                                🌱 Start Shopping
                            </a>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php

if (file_exists("footer.php")) {
    include "footer.php";
}

?>

</body>

</html>