<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$customer_id = $_SESSION['customer_id'] ?? 1; // Fallback for testing
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exchange'])) {
    $offered_plant_id = (int)$_POST['offered_plant_id'];
    $received_plant_id = (int)$_POST['received_plant_id'];

    // Get prices
    $p1_res = mysqli_query($conn, "SELECT Unit_price FROM plant WHERE Plant_ID = '$offered_plant_id'");
    $p2_res = mysqli_query($conn, "SELECT Unit_price FROM plant WHERE Plant_ID = '$received_plant_id'");
    
    $p1 = mysqli_fetch_assoc($p1_res);
    $p2 = mysqli_fetch_assoc($p2_res);

    $old_price = (float)($p1['Unit_price'] ?? 0);
    $new_price = (float)($p2['Unit_price'] ?? 0);

    // Calculate value difference
    $exchange_value = $new_price - $old_price;

    if ($exchange_value < 0) {
        // Refund difference to Wallet
        $refund_amount = abs($exchange_value);
        $payment_method = "Store Wallet Credit";
        $payment_status = "Refunded to Wallet";

        mysqli_query($conn, "UPDATE customer SET wallet_balance = wallet_balance + $refund_amount WHERE Customer_ID = '$customer_id'");
        $msg = "✅ Exchange submitted! ৳" . number_format($refund_amount, 2) . " refunded to your store wallet.";
    } else {
        $payment_method = "Cash on Delivery";
        $payment_status = "Pending Balance: ৳" . number_format($exchange_value, 2);
        $msg = "✅ Exchange submitted! Additional balance due on delivery: ৳" . number_format($exchange_value, 2);
    }

    $today = date('Y-m-d');
    $sql = "INSERT INTO exchange (Exchange_date, Exchange_value, Received_plant_ID, Customer_ID, Offered_plant_ID, status, payment_method, payment_status)
            VALUES ('$today', '$exchange_value', '$received_plant_id', '$customer_id', '$offered_plant_id', 'Pending', '$payment_method', '$payment_status')";
    
    mysqli_query($conn, $sql);
}

$plants = mysqli_query($conn, "SELECT * FROM plant");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exchange Plant - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef7f2; padding: 30px; }
        .form-card { background: white; max-width: 500px; margin: auto; padding: 25px; border-radius: 12px; }
        select, button { width: 100%; padding: 10px; margin-top: 10px; border-radius: 6px; }
        button { background: #10b981; color: white; border: none; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="form-card">
    <h2>Request Plant Exchange 🔄</h2>
    <?php if ($msg) echo "<p style='color:#065f46;'>$msg</p>"; ?>
    
    <form method="POST">
        <label>Current Plant You Own:</label>
        <select name="offered_plant_id" required>
            <?php 
            mysqli_data_seek($plants, 0);
            while($row = mysqli_fetch_assoc($plants)): ?>
                <option value="<?php echo $row['Plant_ID']; ?>"><?php echo $row['Plant_name']; ?> (৳<?php echo $row['Unit_price']; ?>)</option>
            <?php endwhile; ?>
        </select>

        <label style="margin-top:15px; display:block;">Target Plant to Receive:</label>
        <select name="received_plant_id" required>
            <?php 
            mysqli_data_seek($plants, 0);
            while($row = mysqli_fetch_assoc($plants)): ?>
                <option value="<?php echo $row['Plant_ID']; ?>"><?php echo $row['Plant_name']; ?> (৳<?php echo $row['Unit_price']; ?>)</option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="submit_exchange">Submit Request</button>
    </form>
</div>
</body>
</html>