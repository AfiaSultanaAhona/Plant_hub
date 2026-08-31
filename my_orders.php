<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

/* =========================================================
   1. GET CUSTOMER ID
========================================================= */

$customer_id =
    $_SESSION['customer_id'] ??
    $_SESSION['user_id'] ??
    $_SESSION['Customer_ID'] ??
    $_SESSION['id'] ??
    null;

$clean_customer_id = (int) preg_replace('/[^0-9]/', '', (string)$customer_id);

if ($clean_customer_id <= 0) {
    header("Location: login.php");
    exit;
}

/* =========================================================
   2. FETCH CUSTOMER ORDERS
========================================================= */

$query = "
    SELECT
        o.*,
        p.Plant_name,
        p.Unit_price
    FROM orders o
    LEFT JOIN plant p
        ON o.Plant_id = p.Plant_ID
    WHERE o.Customer_id = ?
    ORDER BY o.Order_id DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $clean_customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders - Plant Hub</title>

    <style>

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef7f2;
            margin: 0;
            color: #1e293b;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        h2 {
            margin-top: 0;
            color: #065f46;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th,
        .orders-table td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .orders-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }

        .orders-table tr:hover {
            background: #f8fffb;
        }

        .status-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-none {
            background: #f1f5f9;
            color: #475569;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .exchange-btn {
            display: inline-block;
            background: #0284c7;
            color: white;
            text-decoration: none;
            padding: 8px 13px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 13px;
            transition: 0.2s;
        }

        .exchange-btn:hover {
            background: #0369a1;
        }

        .disabled-btn {
            display: inline-block;
            background: #e2e8f0;
            color: #64748b;
            padding: 8px 13px;
            border-radius: 7px;
            font-weight: 600;
            font-size: 13px;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 30px;
        }

        .amount {
            color: #10b981;
            font-weight: bold;
        }

        @media (max-width: 800px) {

            .orders-table {
                font-size: 13px;
            }

            .orders-table th,
            .orders-table td {
                padding: 9px 7px;
            }

            .exchange-btn,
            .disabled-btn {
                padding: 7px 9px;
                font-size: 12px;
            }

        }

    </style>

</head>

<body>

<?php include("header.php"); ?>

<div class="container">

    <div class="card">

        <h2>My Orders 📦</h2>

        <p style="color:#64748b;">
            View your purchases and request a plant exchange when needed.
        </p>

        <table class="orders-table">

            <thead>

                <tr>

                    <th>Order #</th>

                    <th>Plant Name</th>

                    <th>Amount Paid</th>

                    <th>Points Redeemed</th>

                    <th>Status</th>

                    <th>Exchange</th>

                </tr>

            </thead>

            <tbody>

            <?php if ($result && mysqli_num_rows($result) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>

                    <?php

                    $order_id =
                        $row['Order_id']
                        ?? $row['order_id']
                        ?? $row['Order_ID']
                        ?? 0;

                    $plant_id =
                        $row['Plant_id']
                        ?? $row['Plant_ID']
                        ?? $row['plant_id']
                        ?? 0;

                    $plant_name =
                        $row['Plant_name']
                        ?? $row['plant_name']
                        ?? 'Unknown Plant';

                    $amount =
                        $row['Amount']
                        ?? $row['amount']
                        ?? 0;

                    $points =
                        $row['points_redeemed']
                        ?? $row['Points_redeemed']
                        ?? 0;

                    $exchange_status =
                        $row['Exchange_status']
                        ?? $row['exchange_status']
                        ?? 'None';

                    ?>

                    <tr>

                        <!-- ORDER ID -->

                        <td>
                            <strong>
                                #<?php echo htmlspecialchars($order_id); ?>
                            </strong>
                        </td>


                        <!-- PLANT -->

                        <td>

                            🌱
                            <?php echo htmlspecialchars($plant_name); ?>

                        </td>


                        <!-- AMOUNT -->

                        <td class="amount">

                            ৳<?php echo number_format((float)$amount, 2); ?>

                        </td>


                        <!-- POINTS -->

                        <td>

                            <?php echo (int)$points; ?> PTS

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php

                            $status_lower = strtolower(trim((string)$exchange_status));

                            if ($status_lower === 'pending'):

                            ?>

                                <span class="status-badge status-pending">
                                    Exchange Pending
                                </span>

                            <?php elseif ($status_lower === 'completed'): ?>

                                <span class="status-badge status-completed">
                                    Exchange Completed
                                </span>

                            <?php else: ?>

                                <span class="status-badge status-none">
                                    No Exchange
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- EXCHANGE BUTTON -->

                        <td>

                            <?php if (
                                $status_lower !== 'pending' &&
                                $status_lower !== 'completed' &&
                                (int)$plant_id > 0
                            ): ?>

                                <a
                                    href="process_exchanges.php?order_id=<?php echo (int)$order_id; ?>&offered_plant_id=<?php echo (int)$plant_id; ?>"
                                    class="exchange-btn"
                                >
                                    🔄 Exchange
                                </a>

                            <?php elseif ($status_lower === 'pending'): ?>

                                <span class="disabled-btn">
                                    ⏳ Pending
                                </span>

                            <?php elseif ($status_lower === 'completed'): ?>

                                <span class="disabled-btn">
                                    ✓ Done
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

                    <td colspan="6" class="empty">

                        No order history found.

                        <br><br>

                        <a href="shop.php">
                            Start Shopping 🌱
                        </a>

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>