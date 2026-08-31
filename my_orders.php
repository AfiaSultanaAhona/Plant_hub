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

/*
|--------------------------------------------------------------------------
| GET THIS CUSTOMER'S ORDERS ONLY
|--------------------------------------------------------------------------
*/
$query = "
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

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
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
            padding: 0;
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
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
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
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .orders-table th {
            background: #f8fafc;
            color: #334155;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-none {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .exchange-btn {
            display: inline-block;
            background: #10b981;
            color: white;
            text-decoration: none;
            padding: 8px 13px;
            border-radius: 7px;
            font-weight: bold;
            font-size: 13px;
        }

        .exchange-btn:hover {
            background: #059669;
        }

        .disabled-btn {
            display: inline-block;
            background: #cbd5e1;
            color: #475569;
            padding: 8px 13px;
            border-radius: 7px;
            font-weight: bold;
            font-size: 13px;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 30px;
        }

        @media (max-width: 700px) {
            .orders-table {
                font-size: 13px;
            }

            .orders-table th,
            .orders-table td {
                padding: 8px;
            }

            .container {
                padding: 0 10px;
            }
        }
    </style>
</head>

<body>

<?php include("header.php"); ?>

<div class="container">

    <div class="card">

        <h2>My Orders 📦</h2>

        <table class="orders-table">

            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Plant Name</th>
                    <th>Amount Paid</th>
                    <th>Points Redeemed</th>
                    <th>Exchange Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>

                    <?php
                    $order_id = (int)$row['Order_id'];
                    $plant_id = (int)$row['Plant_id'];

                    $plant_name = $row['Plant_name'] ?? 'Unknown Plant';

                    $amount = (float)($row['Amount'] ?? 0);

                    $points = (int)($row['points_redeemed'] ?? 0);

                    $exchange_status = $row['Exchange_status'] ?? 'None';

                    $status_class = 'status-none';

                    if (strcasecmp($exchange_status, 'Pending') === 0) {
                        $status_class = 'status-pending';
                    }

                    if (strcasecmp($exchange_status, 'Completed') === 0) {
                        $status_class = 'status-completed';
                    }
                    ?>

                    <tr>

                        <td>
                            <strong>#<?= $order_id ?></strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($plant_name) ?>
                        </td>

                        <td style="color:#10b981;font-weight:bold;">
                            ৳<?= number_format($amount, 2) ?>
                        </td>

                        <td>
                            <?= $points ?> PTS
                        </td>

                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <?= htmlspecialchars($exchange_status) ?>
                            </span>
                        </td>

                        <td>

                            <?php if (
                                strcasecmp($exchange_status, 'None') === 0 ||
                                strcasecmp($exchange_status, '') === 0
                            ): ?>

                                <a
                                    href="process_exchanges.php?order_id=<?= $order_id ?>"
                                    class="exchange-btn"
                                >
                                    🔄 Exchange
                                </a>

                            <?php else: ?>

                                <span class="disabled-btn">
                                    Exchange <?= htmlspecialchars($exchange_status) ?>
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
                        <a href="shop.php">Start shopping 🌿</a>
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>