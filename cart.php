<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Resolve customer ID correctly from session
$raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? null;
$customer_id = (int) preg_replace('/[^0-9]/', '', (string)$raw_id);
if ($customer_id <= 0 || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: login.php");
    exit;
}

// 2. Handle Cart Quantity Adjustments (+ / -)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $item_id = $_GET['id'];
    if ($_GET['action'] === 'add') {
        $_SESSION['cart'][$item_id]['quantity'] += 1;
    } elseif ($_GET['action'] === 'remove') {
        $_SESSION['cart'][$item_id]['quantity'] -= 1;
        if ($_SESSION['cart'][$item_id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$item_id]);
        }
    }
    header("Location: cart.php");
    exit();
}

// 3. Fetch current points and wallet balance from DB
$cust_res = mysqli_query($conn, "SELECT points, Loyalty_points, wallet_balance FROM customer WHERE Customer_ID = '$customer_id'");
$customer = mysqli_fetch_assoc($cust_res) ?? [];

$points_balance = (int)($customer['points'] ?? $customer['Loyalty_points'] ?? 0);
$wallet_balance = (float)($customer['wallet_balance'] ?? 0.00);

// Sync header points
$_SESSION['points'] = $points_balance;

// 4. Calculate Subtotal
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$msg = "";
$error = "";

// 5. Handle Purchase Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $payment_method = $_POST['payment_method'] ?? 'COD';
    $points_used = 0;

    if ($payment_method === 'points') {
        if ($points_balance < $subtotal) {
            $error = "❌ Insufficient loyalty points balance!";
        } else {
            $points_used = (int)$subtotal;
        }
    } elseif ($payment_method === 'wallet') {
        if ($wallet_balance < $subtotal) {
            $error = "❌ Insufficient store wallet balance!";
        } else {
            $new_wallet = $wallet_balance - $subtotal;
            mysqli_query($conn, "UPDATE customer SET wallet_balance = '$new_wallet' WHERE Customer_ID = '$customer_id'");
        }
    }

    if (!$error && !empty($cart)) {
        // Points earned logic: 10 pts per ৳500 spent (only if NO points were redeemed)
        $earned_points = 0;
        if ($points_used == 0) {
            $earned_points = floor($subtotal / 500) * 10;
        }

        // Save order entries
        foreach ($cart as $key => $item) {
            $pid = (int)($item['id'] ?? $key);
            $amt = (float)($item['price'] * $item['quantity']);
            
            $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Exchange_status, points_redeemed) 
                          VALUES ('$customer_id', '$pid', '$amt', 'None', '$points_used')";
            mysqli_query($conn, $order_sql);

            // Deduct stock
            $qty = (int)$item['quantity'];
            mysqli_query($conn, "UPDATE plant SET Stock_quantity = Stock_quantity - $qty WHERE Plant_ID = '$pid'");
        }

        // Update customer points
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
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .cart-table th, .cart-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .qty-btn { background: #e2e8f0; border: none; padding: 4px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; color: black; }
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

            <!-- Itemized Invoice Table -->
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Plant Name</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cart)): ?>
                        <tr><td colspan="4" style="text-align:center;">Your cart is empty. <a href="shop.php">Go Shopping</a></td></tr>
                    <?php else: ?>
                        <?php foreach ($cart as $id => $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td style="color:#10b981; font-weight:bold;">৳<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="qty-btn">-</a>
                                    <span style="margin: 0 8px; font-weight:bold;"><?php echo $item['quantity']; ?></span>
                                    <a href="cart.php?action=add&id=<?php echo $id; ?>" class="qty-btn">+</a>
                                </td>
                                <td style="color:#10b981; font-weight:bold;">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($cart)): ?>
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
        <?php endif; ?>
    </div>
</div>

</body>
</html>