<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Resolve Customer ID from session (Extract numeric portion if prefix exists, e.g. "C1" -> 1)
$raw_cid = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? null;
$customer_id = (int) preg_replace('/[^0-9]/', '', $raw_cid);

// Fallback check if session ID is missing
if ($customer_id <= 0) {
    die("<div style='font-family:sans-serif; padding:20px; color:red;'>
            <strong>Session Error:</strong> No valid logged-in customer ID found. 
            Please <a href='login.php'>log in here</a> first.
         </div>");
}

// 2. Fetch current user points balance
$cust_query = "SELECT points, Loyalty_points, wallet_balance FROM customer WHERE Customer_ID = '$customer_id'";
$cust_res = mysqli_query($conn, $cust_query);

if (!$cust_res || mysqli_num_num_rows($cust_res) == 0) {
    $current_points = 0;
} else {
    $customer = mysqli_fetch_assoc($cust_res);
    // Use 'points' column if populated, otherwise fallback to 'Loyalty_points'
    $current_points = (int)($customer['points'] ?? $customer['Loyalty_points'] ?? 0);
}

// 3. Calculate Cart Total
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$msg = "";
$error_msg = "";

// 4. Process Checkout & Points Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $points_to_redeem = (int)($_POST['points_redeemed'] ?? 0);
    
    if ($points_to_redeem > $current_points) {
        $points_to_redeem = $current_points;
    }

    $discount = $points_to_redeem; // 1 point = ৳1
    $final_amount = max(0, $subtotal - $discount);

    // Points earned: 10 points per ৳500 spent (only if NO points were redeemed)
    $earned_points = 0;
    if ($points_to_redeem == 0) {
        $earned_points = floor($final_amount / 500) * 10;
    }

    // Insert active items into order table
    foreach ($cart as $item) {
        $pid = (int)$item['id'];
        $item_amt = (float)($item['price'] * $item['quantity']);
        
        $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Exchange_status, points_redeemed) 
                      VALUES ('$customer_id', '$pid', '$item_amt', 'None', '$points_to_redeem')";
        mysqli_query($conn, $order_sql);
    }

    // Calculate final point balance
    $new_points = max(0, $current_points - $points_to_redeem + $earned_points);

    // Update BOTH points columns in customer table to cover database variations
    $update_query = "UPDATE customer 
                     SET points = '$new_points', 
                         Loyalty_points = '$new_points' 
                     WHERE Customer_ID = '$customer_id'";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['points'] = $new_points;
        $_SESSION['cart'] = [];
        $msg = "🎉 Order successfully placed! You earned +$earned_points pts. Total Points: $new_points pts.";
    } else {
        // Displays exact database error directly on screen if execution fails
        $error_msg = "❌ Database Update Failed: " . mysqli_error($conn);
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
        .alert-success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; font-weight: bold; margin-bottom: 15px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="card">
    <h2>Checkout 🛒</h2>
    
    <?php if ($msg): ?>
        <div class="alert-success"><?php echo $msg; ?></div>
        <a href="shop.php">Return to Shop</a>
    <?php else: ?>
        <?php if ($error_msg): ?>
            <div class="alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <p><strong>Logged in Customer ID:</strong> #<?php echo $customer_id; ?></p>
        <p><strong>Subtotal:</strong> ৳<?php echo number_format($subtotal, 2); ?></p>
        <p><strong>Available Points Balance:</strong> <?php echo $current_points; ?> pts</p>

        <form method="POST">
            <label style="font-size: 14px; font-weight: bold;">Points to Redeem (1 pt = ৳1):</label>
            <input type="number" name="points_redeemed" max="<?php echo $current_points; ?>" min="0" value="0" style="width: 100%; padding: 10px; margin: 8px 0 20px 0; border: 1px solid #ccc; border-radius: 6px;">
            <button type="submit" name="place_order" class="btn">Place Order & Update Points</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>