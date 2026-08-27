<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. Identify active customer session
$user_id = $_SESSION['Customer_ID'] 
        ?? $_SESSION['user_id'] 
        ?? $_SESSION['customer_id'] 
        ?? $_SESSION['cid'] 
        ?? $_SESSION['id'] 
        ?? null;

$clean_id = $user_id ? mysqli_real_escape_string($conn, $user_id) : null;

// 2. Default points balance to 0
$user_points = 0;

if ($clean_id) {
    $pts_res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = '$clean_id'");
    if ($pts_res && $p_row = mysqli_fetch_assoc($pts_res)) {
        $user_points = (int)($p_row['points'] ?? 0);
    }
} else if (isset($_SESSION['user_points'])) {
    $user_points = (int)$_SESSION['user_points'];
}

// Store current state back into session
$_SESSION['user_points'] = $user_points;

// 3. Cart Quantity Adjustments (+ / - / remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $p_id = $_POST['plant_id'] ?? '';
    
    if ($_POST['action'] === 'increase' && isset($_SESSION['cart'][$p_id])) {
        $_SESSION['cart'][$p_id]['quantity'] += 1;
    } elseif ($_POST['action'] === 'decrease' && isset($_SESSION['cart'][$p_id])) {
        $_SESSION['cart'][$p_id]['quantity'] -= 1;
        if ($_SESSION['cart'][$p_id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$p_id]);
        }
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['cart'][$p_id]);
    }
}

// Calculate Grand Total
$total_amount = 0.0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += ((float)$item['price'] * (int)$item['quantity']);
}

$message = "";
$message_type = "";

// 4. Order Checkout and Earn/Spend Points Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (empty($_SESSION['cart'])) {
        $message = "Your cart is empty.";
        $message_type = "error";
    } else {
        $payment_method = $_POST['payment_method'] ?? 'Payment on Delivery';
        $required_points = (int)ceil($total_amount);

        if ($payment_method === 'Loyalty Points' && $user_points < $required_points) {
            $message = "Insufficient loyalty points balance! You need $required_points points.";
            $message_type = "error";
        } else {
            // A. Insert Orders into DB
            foreach ($_SESSION['cart'] as $item) {
                $pid = mysqli_real_escape_string($conn, $item['id']);
                $amt = (float)$item['price'] * (int)$item['quantity'];
                $pay_m = mysqli_real_escape_string($conn, $payment_method);
                $cust = $clean_id ?? '1';

                mysqli_query($conn, "INSERT INTO orders (Customer_ID, plant_id, amount, Payment_Method, order_date) 
                                     VALUES ('$cust', '$pid', '$amt', '$pay_m', NOW())");
            }

            // B. Earn +10 PTS per ৳500 spent OR Deduct Points on redemption
            if ($payment_method === 'Loyalty Points') {
                $new_points = max(0, $user_points - $required_points);
            } else {
                $earned_points = (int)floor($total_amount / 500) * 10;
                $new_points = $user_points + $earned_points;
            }

            // C. Persist updated points to SESSION & DATABASE simultaneously
            $_SESSION['user_points'] = $new_points;
            $user_points = $new_points;

            if ($clean_id) {
                mysqli_query($conn, "UPDATE customer SET points = $new_points WHERE Customer_ID = '$clean_id'");
            }

            $_SESSION['cart'] = [];
            $total_amount = 0.0;
            $message = "Order placed successfully! 🌿 Points updated.";
            $message_type = "success";
        }
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
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .grid-stats { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-card { flex: 1; background: #064e3b; color: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-card.points-card { background: #10b981; }
        .stat-card h4 { margin: 0; font-size: 14px; text-transform: uppercase; opacity: 0.9; }
        .stat-card .val { font-size: 28px; font-weight: 800; margin-top: 8px; display: block; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; padding: 12px; color: #475569; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .btn-qty { background: #cbd5e1; border: none; width: 28px; height: 28px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-qty:hover { background: #94a3b8; color: white; }
        .select-payment { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; margin-top: 8px; }
        .btn-checkout { width: 100%; background: #10b981; color: white; border: none; padding: 16px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .btn-checkout:hover { background: #059669; }
        .alert-success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">
    <div class="grid-stats">
        <div class="stat-card">
            <h4>Store Wallet Balance 💳</h4>
            <span class="val">৳0.00</span>
        </div>
        <div class="stat-card points-card">
            <h4>Loyalty Points Balance ⭐️</h4>
            <span class="val"><?php echo number_format($user_points); ?> PTS</span>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="<?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 style="margin-top:0;">Your Shopping Cart 🛒</h2>

        <?php if (!empty($_SESSION['cart'])): ?>
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
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td style="color:#10b981; font-weight:bold;">৳<?php echo number_format((float)$item['price'], 2); ?></td>
                            <td>
                                <form method="POST" style="display:inline-flex; align-items:center; gap:8px;">
                                    <input type="hidden" name="plant_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                    <button type="submit" name="action" value="decrease" class="btn-qty">-</button>
                                    <span><strong><?php echo $item['quantity']; ?></strong></span>
                                    <button type="submit" name="action" value="increase" class="btn-qty">+</button>
                                </form>
                            </td>
                            <td style="color:#10b981; font-weight:bold;">৳<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="POST" style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label style="font-weight:bold; color: #334155;">Select Payment Method:</label>
                <select name="payment_method" class="select-payment">
                    <option value="Payment on Delivery">Pay on Delivery / Pickup</option>
                    <option value="Loyalty Points">⭐️ Loyalty Points (Available: <?php echo number_format($user_points); ?> PTS)</option>
                    <option value="Credit Card">Credit Card</option>
                </select>

                <div style="text-align: right; margin-top: 25px;">
                    <span style="font-size: 24px; font-weight: 800; color: #0f172a;">
                        Final Total: <span style="color:#10b981;">৳<?php echo number_format($total_amount, 2); ?></span>
                    </span>
                </div>

                <input type="hidden" name="action" value="checkout">
                <button type="submit" class="btn-checkout">Confirm & Complete Purchase Order ↗</button>
            </form>

        <?php else: ?>
            <p style="text-align:center; color:#64748b; margin: 40px 0;">Your cart is currently empty. <a href="shop.php" style="color:#10b981; font-weight:bold;">Shop Plants</a></p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>