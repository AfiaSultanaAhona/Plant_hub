<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    header("Location: login.php");
    exit();
}

// Fetch current customer details
$cust_res = mysqli_query($conn, "SELECT points, wallet_balance FROM customer WHERE Customer_ID = '$customer_id'");
$customer = mysqli_fetch_assoc($cust_res);
$current_points = (int)($customer['points'] ?? 0);
$wallet_balance = (float)($customer['wallet_balance'] ?? 0.0);

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $points_to_redeem = (int)($_POST['points_redeemed'] ?? 0);
    if ($points_to_redeem > $current_points) {
        $points_to_redeem = $current_points;
    }

    // 1 point = 1 TK discount
    $discount = $points_to_redeem;
    $final_amount = max(0, $subtotal - $discount);

    // Points earned: 10 points per 500 spent (only if NO points were redeemed)
    $earned_points = 0;
    if ($points_to_redeem == 0) {
        $earned_points = floor($final_amount / 500) * 10;
    }

    // Begin database order inserts
    foreach ($cart as $item) {
        $pid = $item['id'];
        $qty = $item['quantity'];
        $item_total = $item['price'] * $qty;

        $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Exchange_status, points_redeemed) 
                      VALUES ('$customer_id', '$pid', '$item_total', 'None', '$points_to_redeem')";
        mysqli_query($conn, $order_sql);

        // Deduct inventory stock
        mysqli_query($conn, "UPDATE plant SET Stock_quantity = Stock_quantity - $qty WHERE Plant_ID = '$pid'");
    }

    // Update customer points
    $new_points = $current_points - $points_to_redeem + $earned_points;
    mysqli_query($conn, "UPDATE customer SET points = '$new_points' WHERE Customer_ID = '$customer_id'");

    $_SESSION['cart'] = [];
    $msg = "🎉 Order placed successfully! You earned $earned_points points.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef7f2; padding: 30px; }
        .card { background: white; max-width: 500px; margin: auto; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn { background: #10b981; color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2>Checkout 🛒</h2>
    <?php if ($msg): ?>
        <p style="color: #059669; font-weight: bold;"><?php echo $msg; ?></p>
        <a href="shop.php">Back to Shop</a>
    <?php else: ?>
        <p><strong>Subtotal:</strong> ৳<?php echo number_format($subtotal, 2); ?></p>
        <p><strong>Your Loyalty Points:</strong> <?php echo $current_points; ?> pts</p>

        <form method="POST">
            <label>Points to Redeem (1 pt = ৳1):</label>
            <input type="number" name="points_redeemed" max="<?php echo $current_points; ?>" min="0" value="0" style="width: 100%; padding: 8px; margin: 10px 0 20px 0;">
            <button type="submit" name="place_order" class="btn">Confirm & Pay</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>