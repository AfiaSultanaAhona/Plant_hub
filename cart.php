<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

mysqli_report(MYSQLI_REPORT_OFF);

// Ensure orders table exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Customer_ID VARCHAR(50),
    plant_id INT,
    plant_name VARCHAR(255),
    Amount DECIMAL(10,2),
    payment_method VARCHAR(100),
    order_date DATETIME
)");
@mysqli_query($conn, "ALTER TABLE customer ADD wallet_balance DECIMAL(10,2) DEFAULT 0.00");

// Normalize Cart Session Structure
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $k => $v) {
        $q = (int)($v['qty'] ?? $v['quantity'] ?? $v['count'] ?? 1);
        if ($q <= 0) $q = 1;
        $_SESSION['cart'][$k]['qty'] = $q;
    }
}

// Handle Quantity Updates (+ / - Buttons)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $plant_id = $_POST['plant_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (isset($_SESSION['cart'][$plant_id])) {
        $current_qty = (int)($_SESSION['cart'][$plant_id]['qty'] ?? 1);
        
        if ($action === 'increase') {
            $current_qty += 1;
        } elseif ($action === 'decrease') {
            $current_qty -= 1;
        }

        if ($current_qty <= 0) {
            unset($_SESSION['cart'][$plant_id]);
        } else {
            $_SESSION['cart'][$plant_id]['qty'] = $current_qty;
        }
    }
    header("Location: cart.php");
    exit();
}

// User & Wallet & Points Setup
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? $_SESSION['id'] ?? $_SESSION['Customer_ID'] ?? 'C1';
$u_id_esc = mysqli_real_escape_string($conn, (string)$user_id);
$raw_numeric_id = (int)preg_replace('/[^0-9]/', '', (string)$user_id);

@mysqli_query($conn, "ALTER TABLE customer ADD loyalty_points INT DEFAULT 2512");
@mysqli_query($conn, "ALTER TABLE users ADD loyalty_points INT DEFAULT 2512");

$user_wallet = (float)($_SESSION['wallet_balance'] ?? 0.00);
$w_res = @mysqli_query($conn, "SELECT wallet_balance FROM customer WHERE Customer_id = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
if ($w_res && $w_row = mysqli_fetch_assoc($w_res)) {
    $user_wallet = max($user_wallet, (float)($w_row['wallet_balance'] ?? 0));
}
$_SESSION['wallet_balance'] = $user_wallet;

$user_points = 2512;
$pts_res = @mysqli_query($conn, "SELECT loyalty_points FROM customer WHERE Customer_id = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
if ($pts_res && $pts_row = mysqli_fetch_assoc($pts_res)) {
    $user_points = (int)($pts_row['loyalty_points'] ?? 2512);
} else {
    $pts_res2 = @mysqli_query($conn, "SELECT loyalty_points FROM users WHERE id = '$u_id_esc' OR user_id = '$u_id_esc'");
    if ($pts_res2 && $pts_row2 = mysqli_fetch_assoc($pts_res2)) {
        $user_points = (int)($pts_row2['loyalty_points'] ?? 2512);
    }
}
$_SESSION['user_points'] = $user_points;

// Calculate Total Cost
$cart_total = 0.00;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $price = (float)($item['price'] ?? $item['unit_price'] ?? $item['Price'] ?? 0.00);
        $qty = (int)($item['qty'] ?? 1);
        $cart_total += $price * $qty;
    }
}

// Handle Order Submission
$order_msg = "";
$order_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $payment_method = $_POST['payment_method'] ?? 'Pay on Delivery / Pickup';

    if (empty($_SESSION['cart'])) {
        $order_msg = "❌ Your cart is empty!";
    } elseif ($payment_method === 'Store Wallet' && $user_wallet <= 0) {
        $order_msg = "❌ Insufficient Store Wallet credit!";
    } elseif ($payment_method === 'Loyalty Points' && $user_points < $cart_total) {
        $order_msg = "❌ Insufficient Loyalty Points!";
    } else {
        $wallet_used = 0.00;
        if ($payment_method === 'Store Wallet') {
            $wallet_used = min($user_wallet, $cart_total);
            $new_bal = $user_wallet - $wallet_used;
            $_SESSION['wallet_balance'] = $new_bal;
            $user_wallet = $new_bal;
            @mysqli_query($conn, "UPDATE customer SET wallet_balance = '$new_bal' WHERE Customer_id = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
            @mysqli_query($conn, "UPDATE users SET wallet_balance = '$new_bal' WHERE id = '$u_id_esc' OR user_id = '$u_id_esc'");
            
            if ($wallet_used < $cart_total) {
                $payment_method = 'Store Wallet + Pay on Delivery';
            }
        } elseif ($payment_method === 'Loyalty Points') {
            $new_pts = max(0, $user_points - (int)$cart_total);
            $_SESSION['user_points'] = $new_pts;
            $user_points = $new_pts;
            @mysqli_query($conn, "UPDATE customer SET loyalty_points = '$new_pts' WHERE Customer_id = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
            @mysqli_query($conn, "UPDATE users SET loyalty_points = '$new_pts' WHERE id = '$u_id_esc' OR user_id = '$u_id_esc'");
        }

        // Insert order record tied directly to user ID & update inventory
        foreach ($_SESSION['cart'] as $item) {
            $pname = mysqli_real_escape_string($conn, $item['name'] ?? $item['Plant_name'] ?? $item['plant_name'] ?? 'Plant');
            $pid = (int)($item['id'] ?? $item['Plant_ID'] ?? $item['plant_id'] ?? 1);
            $qty = (int)($item['qty'] ?? 1);
            $price = (float)($item['price'] ?? $item['unit_price'] ?? $item['Price'] ?? 0.00);
            $pamt = $price * $qty;

            @mysqli_query($conn, "INSERT INTO orders (Customer_ID, plant_id, plant_name, Amount, payment_method, order_date) 
                                  VALUES ('$u_id_esc', '$pid', '$pname', '$pamt', '$payment_method', NOW())");

            // Deduct purchased quantity from database stock
            @mysqli_query($conn, "UPDATE plant SET stock = GREATEST(0, stock - $qty) WHERE plant_id = '$pid' OR id = '$pid'");
        }

        if ($payment_method === 'Loyalty Points') {
            $order_msg = "🎉 Order placed successfully using <strong>Loyalty Points</strong>! Deducted <strong>" . number_format($cart_total) . " PTS</strong>.";
        } else {
            $earned_pts = floor($cart_total / 500) * 10;
            if ($earned_pts > 0) {
                @mysqli_query($conn, "UPDATE customer SET loyalty_points = loyalty_points + $earned_pts WHERE Customer_id = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
                @mysqli_query($conn, "UPDATE users SET loyalty_points = loyalty_points + $earned_pts WHERE id = '$u_id_esc' OR user_id = '$u_id_esc'");
                $user_points += $earned_pts;
                $_SESSION['user_points'] = $user_points;
            }
            if ($payment_method === 'Store Wallet + Pay on Delivery') {
                $order_msg = "🎉 Order placed successfully! Used <strong>$" . number_format($wallet_used, 2) . "</strong> from Store Wallet, remaining <strong>$" . number_format($cart_total - $wallet_used, 2) . "</strong> to pay on Delivery. You earned <strong>+$earned_pts PTS</strong>!";
            } else {
                $order_msg = "🎉 Order placed successfully via <strong>" . htmlspecialchars($payment_method) . "</strong>! You earned <strong>+$earned_pts PTS</strong>!";
            }
        }

        $_SESSION['cart'] = [];
        $cart_total = 0.00;
        $order_success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Plant Hub</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 850px; margin: 30px auto; padding: 0 20px; }
        .wallet-banner { background-color: #064e3b; border-radius: 12px; padding: 18px 25px; color: white; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .wallet-banner h3 { margin: 0; font-size: 18px; font-weight: 700; }
        .wallet-amount { font-size: 22px; font-weight: 800; color: #6ee7b7; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .card-title { font-size: 22px; font-weight: 800; color: #0a2318; margin-top: 0; margin-bottom: 25px; }
        .alert-box { padding: 14px 20px; border-radius: 8px; text-align: center; font-weight: 600; margin-bottom: 20px; }
        .alert-success { background-color: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; padding: 12px; color: #374151; border-bottom: 2px solid #e5e7eb; font-size: 14px; }
        td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; font-size: 15px; vertical-align: middle; }
        .price-text { color: #10b981; font-weight: 700; }
        .subtotal-text { color: #10b981; font-weight: 800; }
        .qty-controls { display: flex; align-items: center; gap: 8px; }
        .btn-qty { background-color: #e5e7eb; border: none; width: 28px; height: 28px; border-radius: 6px; font-weight: 800; font-size: 16px; cursor: pointer; color: #374151; }
        .btn-qty:hover { background-color: #10b981; color: white; }
        .qty-number { font-weight: 700; width: 24px; text-align: center; }
        .summary-section { text-align: right; border-top: 1px solid #f3f4f6; padding-top: 20px; }
        .final-total-line { font-size: 24px; font-weight: 800; color: #065f46; margin-bottom: 20px; }
        .payment-selection { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: left; }
        .payment-selection label { font-size: 14px; font-weight: 700; color: #374151; display: block; margin-bottom: 8px; }
        .payment-selection select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; }
        .btn-confirm { width: 100%; background-color: #10b981; color: white; border: none; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; }
        .btn-confirm:hover { background-color: #059669; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">

    <div class="wallet-banner" style="display: flex; gap: 20px; background: none; padding: 0; box-shadow: none; margin-bottom: 25px;">
        <div style="flex: 1; background: #064e3b; border-radius: 12px; padding: 18px 25px; color: white;">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Store Wallet Balance 💳</h3>
            <span class="wallet-amount" style="font-size: 24px; font-weight: 800; color: #6ee7b7; margin-top: 5px; display: block;">$<?php echo number_format($user_wallet, 2); ?></span>
        </div>
        <div style="flex: 1; background: #10b981; border-radius: 12px; padding: 18px 25px; color: white;">
            <h3 style="margin: 0; font-size: 14px; opacity: 0.9;">Loyalty Points Balance ⭐</h3>
            <span style="font-size: 24px; font-weight: 800; color: #fef08a; margin-top: 5px; display: block;"><?php echo number_format($user_points); ?> PTS</span>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">Your Shopping Cart 🛒</h2>

        <?php if (!empty($order_msg)): ?>
            <div class="alert-box <?php echo $order_success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $order_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>Plant Name</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $id => $item): 
                        $itemName = $item['name'] ?? $item['Plant_name'] ?? $item['plant_name'] ?? 'Plant';
                        $itemPrice = (float)($item['price'] ?? $item['unit_price'] ?? $item['Price'] ?? 0.00);
                        $itemQty = (int)($item['qty'] ?? 1);
                        $subtotal = $itemPrice * $itemQty;
                    ?>
                        <tr>
                            <td style="font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($itemName); ?></td>
                            <td class="price-text">$<?php echo number_format($itemPrice, 2); ?></td>
                            <td>
                                <div class="qty-controls">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="update_qty" value="1">
                                        <input type="hidden" name="plant_id" value="<?php echo $id; ?>">
                                        <input type="hidden" name="action" value="decrease">
                                        <button type="submit" class="btn-qty">-</button>
                                    </form>

                                    <span class="qty-number"><?php echo $itemQty; ?></span>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="update_qty" value="1">
                                        <input type="hidden" name="plant_id" value="<?php echo $id; ?>">
                                        <input type="hidden" name="action" value="increase">
                                        <button type="submit" class="btn-qty">+</button>
                                    </form>
                                </div>
                            </td>
                            <td class="subtotal-text">$<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="POST" action="cart.php">
                <div class="payment-selection">
                    <label for="payment_method">Select Payment Method:</label>
                    <select name="payment_method" id="payment_method">
                        <?php if ($user_wallet >= $cart_total && $cart_total > 0): ?>
                            <option value="Store Wallet" selected>
                                💳 Store Wallet Credit (Available: $<?php echo number_format($user_wallet, 2); ?>)
                            </option>
                        <?php elseif ($user_wallet > 0): ?>
                            <option value="Store Wallet" selected>
                                💳 Store Wallet + Pay on Delivery (Use $<?php echo number_format($user_wallet, 2); ?>, pay remaining $<?php echo number_format($cart_total - $user_wallet, 2); ?> on delivery)
                            </option>
                        <?php else: ?>
                            <option value="Store Wallet" disabled>
                                💳 Store Wallet Credit (Available: $0.00 - Empty)
                            </option>
                        <?php endif; ?>

                        <?php if ($user_points >= $cart_total && $cart_total > 0): ?>
                            <option value="Loyalty Points" <?php echo ($user_wallet <= 0) ? 'selected' : ''; ?>>
                                ⭐ Loyalty Points (Available: <?php echo number_format($user_points); ?> PTS)
                            </option>
                        <?php else: ?>
                            <option value="Loyalty Points" disabled>
                                ⭐ Loyalty Points (Available: <?php echo number_format($user_points); ?> PTS - Insufficient)
                            </option>
                        <?php endif; ?>

                        <option value="Pay on Delivery / Pickup" <?php echo ($user_wallet <= 0 && $user_points < $cart_total) ? 'selected' : ''; ?>>
                            🚚 Pay on Delivery / Pickup
                        </option>
                    </select>
                </div>

                <div class="summary-section">
                    <div class="final-total-line">Final Total: $<?php echo number_format($cart_total, 2); ?></div>
                    <button type="submit" name="confirm_order" class="btn-confirm">Confirm & Complete Purchase Order ✅</button>
                </div>
            </form>

        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <p style="font-size: 16px;">Your cart is currently empty.</p>
                <a href="shop.php" style="color: #10b981; font-weight: 700; text-decoration: none;">Continue Shopping →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>