<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../DBconnect.php");
mysqli_report(MYSQLI_REPORT_OFF);

// Verify employee is logged in
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch purchase history with supplier, plant, and employee names
$sql = "SELECT pt.*, s.Supplier_name, p.Plant_name, e.Employee_name 
        FROM Purchase_Transaction pt
        LEFT JOIN Supplier s ON pt.Supplier_ID = s.Supplier_ID 
        LEFT JOIN Plant p ON pt.Plant_ID = p.Plant_ID
        LEFT JOIN Employee e ON pt.Employee_ID = e.Employee_ID
        ORDER BY pt.Purchase_date DESC, pt.Purchase_ID DESC";
$result = mysqli_query($conn, $sql);

// Summary stats
$total_purchases = 0;
$total_spent = 0.0;
$total_units = 0;

$stats_res = mysqli_query($conn, "SELECT COUNT(*) as cnt, COALESCE(SUM(Total_amount),0) as total_amt, COALESCE(SUM(Quantity),0) as total_qty FROM Purchase_Transaction");
if ($stats_res && $sr = mysqli_fetch_assoc($stats_res)) {
    $total_purchases = (int)$sr['cnt'];
    $total_spent = (float)$sr['total_amt'];
    $total_units = (int)$sr['total_qty'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase History - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1100px; margin: 25px auto; padding: 0 20px; }

        .page-header {
            background: linear-gradient(135deg, #064e3b 0%, #0d7b5f 100%);
            color: white; padding: 28px 32px; border-radius: 16px; margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .page-header h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; }
        .page-header p { margin: 0; opacity: 0.85; font-size: 13px; }
        .header-actions { display: flex; gap: 10px; }
        .btn-header {
            padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 13px;
            text-decoration: none; transition: all 0.2s;
        }
        .btn-add { background: #10b981; color: white; }
        .btn-add:hover { background: #059669; }
        .btn-back { background: rgba(255,255,255,0.15); color: white; }
        .btn-back:hover { background: rgba(255,255,255,0.25); }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 25px; }
        .stat-box {
            background: white; padding: 22px; border-radius: 12px; text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .stat-box h3 { margin: 0; font-size: 28px; font-weight: 800; color: #064e3b; }
        .stat-box.spent h3 { color: #10b981; }
        .stat-box.units h3 { color: #3b82f6; }
        .stat-box p { margin: 5px 0 0; font-size: 13px; color: #6b7280; font-weight: 600; }

        .card {
            background: white; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .card-header-bar {
            padding: 18px 25px; border-bottom: 2px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-header-bar h3 { margin: 0; font-size: 18px; font-weight: 800; color: #0a2318; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 12px 20px; color: #6b7280; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
        }
        td { padding: 14px 20px; border-bottom: 1px solid #f3f4f6; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: #f0fdf4; }

        .badge-qty {
            background: #dbeafe; color: #1d4ed8; padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 800;
        }
        .badge-amount {
            background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 800;
        }
        .emp-name { font-weight: 700; color: #1f2937; }
        .empty-state { text-align: center; padding: 50px 20px; color: #9ca3af; }
        .empty-state p { font-size: 16px; font-weight: 600; }
    </style>
</head>
<body>

<?php include("../header.php"); ?>

<div class="container">
    <div class="page-header">
        <div>
            <h2>📦 Supplier Purchase History</h2>
            <p>Record of all plants bought from suppliers and restocked into inventory</p>
        </div>
        <div class="header-actions">
            <a href="../employee_dashboard.php" class="btn-header btn-back">← Dashboard</a>
            <a href="add_purchase.php" class="btn-header btn-add">+ New Purchase</a>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <h3><?php echo $total_purchases; ?></h3>
            <p>Total Purchase Orders</p>
        </div>
        <div class="stat-box spent">
            <h3>৳<?php echo number_format($total_spent, 2); ?></h3>
            <p>Total Amount Spent</p>
        </div>
        <div class="stat-box units">
            <h3><?php echo number_format($total_units); ?></h3>
            <p>Total Units Received</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header-bar">
            <h3>All Purchase Transactions</h3>
            <span style="color: #9ca3af; font-size: 13px;"><?php echo $total_purchases; ?> records</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Purchase ID</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Plant Item</th>
                    <th>Qty Received</th>
                    <th>Total Amount</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td style="font-weight: 700; color: #6b7280;">#<?php echo $row['Purchase_ID']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['Purchase_date'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['Supplier_name'] ?? 'N/A'); ?></td>
                            <td style="font-weight: 700; color: #1f2937;"><?php echo htmlspecialchars($row['Plant_name'] ?? 'N/A'); ?></td>
                            <td><span class="badge-qty"><?php echo $row['Quantity'] ?? 1; ?> units</span></td>
                            <td><span class="badge-amount">৳<?php echo number_format($row['Total_amount'], 2); ?></span></td>
                            <td><span class="emp-name"><?php echo htmlspecialchars($row['Employee_name'] ?? '—'); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <p>📦 No purchase orders recorded yet.</p>
                                <span style="font-size: 13px;">Use the "New Purchase" button to buy plants from suppliers.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>