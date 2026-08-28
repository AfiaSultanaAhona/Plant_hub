<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Sanitize Session ID: Convert "C1" -> 1 if needed
$raw_cid = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;
$customer_id = (int) preg_replace('/[^0-9]/', '', $raw_cid);

if (!$customer_id) {
    header("Location: login.php");
    exit();
}

// 2. Fetch active customer points & balance
$cust_res = mysqli_query($conn, "SELECT points, wallet_balance FROM customer WHERE Customer_ID = '$customer_id'");
$customer = mysqli_fetch_assoc($cust_res);
$current_points = (int)($customer['points'] ?? 0);

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $points_to_redeem = (int)($_POST['points_redeemed'] ?? 0);
    
    // Prevent redeeming more points than user owns
    if ($points_to_redeem > $current_points) {
        $points_to_redeem = $current_points;
    }

    $discount = $points_to_redeem; // 1 point = ৳1
    $final_amount = max(0, $subtotal - $discount);

    // Rule: Earn 10 points per ৳500 spent (ONLY if 0 points were redeemed)
    $earned_points = 0;
    if ($points_to_redeem == 0) {
        $earned_points = floor($final_amount / 500) * 10;
    }

    // Insert order items
    foreach ($cart as $item) {
        $pid = $item['id'];
        $item_amt = $item['price'] * $item['quantity'];
        
        $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Exchange_status, points_redeemed) 
                      VALUES ('$customer_id', '$pid', '$item_amt', 'None', '$points_to_redeem')";
        mysqli_query($conn, $order_sql);
    }

    // 3. Update customer table points balance
    $new_points = $current_points - $points_to_redeem + $earned_points;
    $update_query = "UPDATE customer SET points = '$new_points' WHERE Customer_ID = '$customer_id'";
    
    if (mysqli_query($conn, $update_query)) {
        // Sync active session points
        $_SESSION['points'] = $new_points;
        $_SESSION['cart'] = [];
        $msg = "🎉 Order placed! You earned $earned_points points. New balance: $new_points pts.";
    } else {
        $msg = "❌ Error updating points: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef7f2; padding: 30px; }
        .card { background: white; max-width: 480px; margin: auto; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn { background: #10b981; color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2>Checkout 🛒</h2>
    <?php if ($msg): ?>
        <p style="font-weight: bold; color: #059669;"><?php echo $msg; ?></p>
        <a href="shop.php">Return to Shop</a>
    <?php else: ?>
        <p><strong>Subtotal:</strong> ৳<?php echo number_format($subtotal, 2); ?></p>
        <p><strong>Available Points:</strong> <?php echo $current_points; ?> pts</p>

        <form method="POST">
            <label style="font-size: 14px; font-weight: bold;">Points to Redeem (1 pt = ৳1):</label>
            <input type="number" name="points_redeemed" max="<?php echo $current_points; ?>" min="0" value="0" style="width: 100%; padding: 10px; margin: 8px 0 20px 0; border: 1px solid #ccc; border-radius: 6px;">
            <button type="submit" name="place_order" class="btn">Place Order</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>