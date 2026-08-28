<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$msg = "";

// Handle inspection verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_exchange'])) {
    $oid = (int)$_POST['order_id'];
    $verify_employee_id = $_SESSION['employee_id'] ?? null;

    @mysqli_query($conn, "UPDATE orders SET payment_method = 'Exchange Completed' WHERE id = '$oid' OR Order_ID = '$oid'");

    // Update exchange table with Employee_ID if applicable
    if ($verify_employee_id) {
        @mysqli_query($conn, "UPDATE exchange SET Employee_ID = '$verify_employee_id' WHERE exchange_id = '$oid' OR Received_plant_ID = '$oid'");
        logEmployeeAction($conn, $verify_employee_id, 'EXCHANGE', "Verified and finalized plant exchange for Order #$oid", $oid);
    }

    $msg = "✅ Return verified and plant exchange finalized for Order #$oid!";
}

$orders_res = @mysqli_query($conn, "SELECT * FROM orders ORDER BY 1 DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Process Exchanges - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1050px; margin: 25px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .card h2 { margin-top: 0; color: #064e3b; font-size: 22px; font-weight: 800; border-bottom: 2px solid #ecfdf5; padding-bottom: 12px; }
        .alert-box { padding: 12px 18px; border-radius: 8px; font-weight: 600; margin-bottom: 18px; background-color: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; color: #4b5563; font-size: 14px; border-bottom: 2px solid #e5e7eb; }
        td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .badge-complete { background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .btn-action { background: #f59e0b; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="container">
    <div class="card">
        <h2>Customer Plant Exchange Verifications 🔄</h2>

        <?php if (!empty($msg)): ?>
            <div class="alert-box"><?php echo $msg; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Current Active Item</th>
                    <th>Current Value</th>
                    <th>Inspection Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($orders_res && mysqli_num_rows($orders_res) > 0):
                    while ($o_row = mysqli_fetch_assoc($orders_res)):
                        $oid = $o_row['id'] ?? $o_row['Order_ID'] ?? '1';
                        $pname = $o_row['plant_name'] ?? 'Plant Item';
                        $amt = (float)($o_row['Amount'] ?? 0);
                        $pmethod = $o_row['payment_method'] ?? '';
                        $is_done = ($pmethod === 'Exchange Completed');
                ?>
                    <tr>
                        <td style="font-weight:700;">#<?php echo $oid; ?></td>
                        <td style="font-weight:700; color:#1f2937;"><?php echo htmlspecialchars($pname); ?></td>
                        <td style="color:#10b981; font-weight:800;">$<?php echo number_format($amt, 2); ?></td>
                        <td>
                            <?php if ($is_done): ?>
                                <span class="badge-complete">Verified & Complete</span>
                            <?php else: ?>
                                <span class="badge-pending">Awaiting Return Condition</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$is_done): ?>
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                    <button type="submit" name="verify_exchange" class="btn-action">Verify Condition ✅</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#059669; font-weight:700; font-size:13px;">Processed ✓</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr><td colspan="5" style="text-align:center; padding:20px;">No exchange orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>