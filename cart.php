<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Resolve customer ID correctly from session (handles user_id or customer_id)
$raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? null;
$customer_id = (int) preg_replace('/[^0-9]/', '', (string)$raw_id);

// Fallback for testing/debugging if session ID resolves to 0
if ($customer_id <= 0) {
    $customer_id = 1; // Default test user
}

// 2. Fetch current points and wallet balance from DB
$cust_res = mysqli_query($conn, "SELECT points, Loyalty_points, wallet_balance FROM customer WHERE Customer_ID = '$customer_id'");
$customer = mysqli_fetch_assoc($cust_res) ?? [];

$points_balance = (int)($customer['points'] ?? $customer['Loyalty_points'] ?? 0);
$wallet_balance = (float)($customer['wallet_balance'] ?? 0.00);

// Sync header points button badge
$_SESSION['points'] = $points_balance;

// 3. Process Cart Calculations
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$msg = "";
$error = "";

// 4. Handle Purchase Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $payment_method = $_POST['payment_method'] ?? 'COD';
    $final_total = $subtotal;
    $points_used = 0;

    if ($payment_method === 'points') {
        // Check if customer has enough points (1 Point = ৳1)
        if ($points_balance < $subtotal) {
            $error = "❌ Insufficient loyalty points balance! You need $subtotal points.";
        } else {
            $points_used = (int)$subtotal;
            $final_total = 0; // Fully covered by points
        }
    } elseif ($payment_method === 'wallet') {
        if ($wallet_balance < $subtotal) {
            $error = "❌ Insufficient store wallet balance!";
        } else {
            // Deduct from wallet
            $new_wallet = $wallet_balance - $subtotal;
            mysqli_query($conn, "UPDATE customer SET wallet_balance = '$new_wallet' WHERE Customer_ID = '$customer_id'");
        }
    }

    if (!$error) {
        // Calculate newly earned points (10 points per ৳500 spent, only if points were NOT redeemed)
        $earned_points = 0;
        if ($points_used == 0) {
            $earned_points = floor($subtotal / 500) * 10;
        }

        // Save order entries
        foreach ($cart as $item) {
            $pid = (int)$item['id'];
            $amt = (float)($item['price'] * $item['quantity']);
            
            $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Exchange_status, points_redeemed) 
                          VALUES ('$customer_id', '$pid', '$amt', 'None', '$points_used')";
            mysqli_query($conn, $order_sql);

            // Deduct stock
            $qty = (int)$item['quantity'];
            mysqli_query($conn, "UPDATE plant SET Stock_quantity = Stock_quantity - $qty WHERE Plant_ID = '$pid'");
        }

        // Update customer points balance in database
        $new_points_balance = max(0, $points_balance - $points_used + $earned_points);
        mysqli_query($conn, "UPDATE customer SET points = '$new_points_balance', Loyalty_points = '$new_points_balance' WHERE Customer_ID = '$customer_id'");

        $_SESSION['cart'] = [];
        $msg = "🎉 Order completed successfully! Used: $points_used pts | Earned: +$earned_points pts.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Cart - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef7f2; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .stats-grid { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-card { flex: 1; padding: 20px; border-radius: 10px; color: white; font-weight: bold; }
        .wallet-card { background: #064e3b; }
        .points-card { background: #10b981; }
        .cart-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; }
        .btn { background: #10b981; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <!-- Stat Cards Header -->
    <div class="stats-grid">
        <div class="stat-card wallet-card">
            <small>STORE WALLET BALANCE 💳</small>
            <h2>৳<?php echo number_format($wallet_balance, 2); ?></h2>
        </div>
        <div class="stat-card points-card">
            <small>LOYALTY POINTS BALANCE ⭐</small>
            <h2><?php echo number_format($points_balance); ?> PTS</h2>
        </div>
    </div>

    <div class="cart-card">
        <h2>Your Shopping Cart 🛒</h2>

        <?php if ($msg): ?>
            <div class="alert-success"><?php echo $msg; ?></div>
            <a href="shop.php">Continue Shopping</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Payment Form -->
            <form method="POST">
                <label style="font-weight: bold;">Select Payment Method:</label>
                <select name="payment_method" class="form-control">
                    <option value="COD">Pay on Delivery / Pickup</option>
                    
                    <!-- Loyalty Points Option -->
                    <option value="points" <?php if ($points_balance < $subtotal) echo 'disabled'; ?>>
                        Pay with Loyalty Points (Available: <?php echo $points_balance; ?> PTS) 
                        <?php if ($points_balance < $subtotal) echo '- Insufficient Points'; ?>
                    </option>

                    <!-- Store Wallet Option -->
                    <option value="wallet" <?php if ($wallet_balance < $subtotal) echo 'disabled'; ?>>
                        Pay with Store Wallet Balance (Available: ৳<?php echo number_format($wallet_balance, 2); ?>)
                    </option>
                </select>

                <h3 style="text-align: right;">Final Total: ৳<?php echo number_format($subtotal, 2); ?></h3>

                <button type="submit" name="complete_order" class="btn">Confirm & Complete Purchase Order ↗</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>