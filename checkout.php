<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Resolve Customer ID from session
$raw_cid = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? null;
$customer_id = (int) preg_replace('/[^0-9]/', '', $raw_cid);

if ($customer_id <= 0) {
    die("<div style='font-family:sans-serif; padding:20px; color:red;'>
            <strong>Session Error:</strong> No valid logged-in customer ID found. 
            Please <a href='login.php'>log in here</a> first.
         </div>");
}

// Calculate Cart Total
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$msg = "";
$error_msg = "";

// Process Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    
    // Insert active items into order table
    $order_success = true;
    foreach ($cart as $item) {
        $pid = (int)$item['id'];
        $item_amt = (float)($item['price'] * $item['quantity']);
        
        $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Exchange_status) 
                      VALUES ('$customer_id', '$pid', '$item_amt', 'None')";
        
        if (!mysqli_query($conn, $order_sql)) {
            $order_success = false;
            $error_msg = "❌ Database Insert Failed: " . mysqli_error($conn);
            break;
        }

        // Deduct inventory stock
        $qty = (int)$item['quantity'];
        mysqli_query($conn, "UPDATE plant SET Stock_quantity = Stock_quantity - $qty WHERE Plant_ID = '$pid'");
    }

    if ($order_success) {
        $_SESSION['cart'] = [];
        $msg = "🎉 Order placed successfully!";
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

        <p><strong>Customer ID:</strong> #<?php echo $customer_id; ?></p>
        <p><strong>Total Amount:</strong> ৳<?php echo number_format($subtotal, 2); ?></p>

        <form method="POST">
            <button type="submit" name="place_order" class="btn">Confirm Order</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>