<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Turn off fatal SQL exceptions for safe execution
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = "";
$message_type = "";

// Handle Cart Actions: Increase, Decrease, Clear
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $plant_id = $_POST['plant_id'] ?? '';

    if ($action === 'increase' && isset($_SESSION['cart'][$plant_id])) {
        $_SESSION['cart'][$plant_id]['quantity'] += 1;
    } elseif ($action === 'decrease' && isset($_SESSION['cart'][$plant_id])) {
        $_SESSION['cart'][$plant_id]['quantity'] -= 1;
        if ($_SESSION['cart'][$plant_id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$plant_id]);
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }
}

// Calculate Cart Total Amount
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += (float)$item['price'] * (int)$item['quantity'];
}

// Handle Purchase Order Confirmation, Points Earning & Redemption
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

    if (empty($_SESSION['cart'])) {
        $message = "Your cart is empty!";
        $message_type = "error";
    } elseif (!$raw_id) {
        $message = "Please log in to complete your purchase.";
        $message_type = "error";
    } else {
        $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
        $numeric_id = (int)preg_replace('/[^0-9]/', '', $clean_id);

        // Fetch current points balance for redemption validation
        $current_points = 0;
        $bal_res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = '$numeric_id' LIMIT 1");
        if ($bal_res && $bal_row = mysqli_fetch_assoc($bal_res)) {
            $current_points = (int)($bal_row['points'] ?? 0);
        }

        // Get points to redeem from form (capped at balance AND order total)
        $redeem_points = isset($_POST['redeem_points']) ? (int)$_POST['redeem_points'] : 0;
        $redeem_points = max(0, min($redeem_points, $current_points, (int)$total_amount));

        // Calculate final paid amount after points discount (1 point = ৳1)
        $discount = $redeem_points;
        $paid_amount = $total_amount - $discount;

        // 1. Insert order records for each cart item & deduct stock
        $first_order_id = null;
        foreach ($_SESSION['cart'] as $plant_id => $item) {
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $item_total = $price * $qty;

            $pts_on_row = ($first_order_id === null) ? $redeem_points : 0;
            $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Order_date, points_redeemed) 
                          VALUES ('$numeric_id', '$plant_id', '$item_total', NOW(), '$pts_on_row')";
            mysqli_query($conn, $order_sql);

            if ($first_order_id === null) {
                $first_order_id = mysqli_insert_id($conn);
            }

            // Deduct stock
            mysqli_query($conn, "UPDATE plant SET Stock_quantity = GREATEST(0, Stock_quantity - $qty) WHERE Plant_ID = '$plant_id'");
        }

        // 2. Award loyalty points (only on the PAID amount, not the redeemed portion)
        $points_earned = processOrderLoyaltyPoints($conn, $numeric_id, $first_order_id, $paid_amount);

        // 3. Redeem points — deducts from customer balance & logs to loyalty_logs
        $actual_redeemed = 0;
        if ($redeem_points > 0) {
            $actual_redeemed = redeemLoyaltyPoints($conn, $numeric_id, $first_order_id, $redeem_points);
        }

        // 4. Refresh session points from DB
        $pts_res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = '$numeric_id' LIMIT 1");
        if ($pts_res && $prow = mysqli_fetch_assoc($pts_res)) {
            $user_points = (int)$prow['points'];
            $_SESSION['points'] = $user_points;
        }

        // 5. Clear cart
        $_SESSION['cart'] = [];
        $total_amount = 0;

        // 6. Build success message
        $msg_parts = [];
        if ($points_earned > 0) $msg_parts[] = "Earned <strong>+$points_earned points</strong>";
        if ($actual_redeemed > 0) $msg_parts[] = "Redeemed <strong>$actual_redeemed points (৳$actual_redeemed discount)</strong>";
        $message = "🎉 Order confirmed! " . (!empty($msg_parts) ? implode(' &nbsp;|&nbsp; ', $msg_parts) : "Thank you for your purchase!");
        $message_type = "success";
    }
}

// Fetch display points for store wallet header component
if (!isset($user_points)) {
    $user_points = 0;
    if (isset($_SESSION['customer_id']) || isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
        $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'];
        $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
        $numeric_id = (int)preg_replace('/[^0-9]/', '', $clean_id);
        
        $pts_res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = '$clean_id' OR Customer_ID = '$numeric_id' OR Customer_ID LIKE '%$numeric_id%' LIMIT 1");
        if ($pts_res && $prow = mysqli_fetch_assoc($pts_res)) {
            $user_points = (int)($prow['points'] ?? 0);
            $_SESSION['points'] = $user_points;
        } else {
            $user_points = (int)($_SESSION['points'] ?? 0);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Plant Hub</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        .dashboard-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .card-box { border-radius: 12px; padding: 20px; color: white; }
        .wallet-card { background: #064e3b; }
        .points-card { background: #10b981; }
        .card-title { font-size: 13px; font-weight: bold; letter-spacing: 0.5px; opacity: 0.9; margin-bottom: 8px; }
        .card-value { font-size: 32px; font-weight: 800; }

        .cart-wrapper { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e1e8ed; }
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .cart-table th { text-align: left; padding: 12px; color: #4b5563; font-size: 14px; border-bottom: 2px solid #e5e7eb; }
        .cart-table td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; color: #1f2937; }
        
        .qty-controls { display: flex; align-items: center; gap: 6px; }
        .btn-qty { background: #e5e7eb; border: none; width: 28px; height: 28px; border-radius: 4px; font-weight: bold; cursor: pointer; color: #374151; }
        .btn-qty:hover { background: #d1d5db; }

        .checkout-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-top: 25px; }
        .payment-select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; margin-top: 8px; margin-bottom: 20px; font-size: 14px; }
        
        .final-row { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px; }
        .final-price { font-size: 26px; font-weight: 800; color: #059669; }

        .btn-checkout { width: 100%; background: #10b981; color: white; border: none; padding: 16px; border-radius: 8px; font-size: 17px; font-weight: bold; cursor: pointer; }
        .btn-checkout:hover { background: #059669; }

        .alert-box { padding: 14px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-size: 15px; }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background-color: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; }
        .empty-cart { text-align: center; padding: 40px 0; color: #6b7280; }

        .redeem-section { background: #f0fdf4; border: 2px solid #bbf7d0; border-radius: 10px; padding: 16px 20px; margin: 16px 0; }
        .redeem-header { display: flex; align-items: center; }
        .redeem-toggle { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 600; color: #065f46; }
        .redeem-toggle input[type="checkbox"] { width: 18px; height: 18px; accent-color: #10b981; cursor: pointer; }
        .redeem-input-row { display: flex; align-items: center; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
        .redeem-input-row label { font-size: 13px; font-weight: 700; color: #374151; white-space: nowrap; }
        .redeem-input-row input[type="number"] { width: 120px; padding: 8px 12px; border: 2px solid #a7f3d0; border-radius: 8px; font-size: 14px; font-weight: 700; }
        .redeem-input-row input[type="number"]:focus { outline: none; border-color: #10b981; }
        .redeem-hint { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">

    <!-- Top Dashboard Cards -->
    <div class="dashboard-cards">
        <div class="card-box wallet-card">
            <div class="card-title">STORE WALLET BALANCE 💳</div>
            <div class="card-value">৳0.00</div>
        </div>
        <div class="card-box points-card">
            <div class="card-title">LOYALTY POINTS BALANCE ⭐</div>
            <div class="card-value"><?php echo number_format($user_points); ?> PTS</div>
        </div>
    </div>

    <!-- Main Shopping Cart Card -->
    <div class="cart-wrapper">
        <h2 style="margin: 0 0 20px 0; color: #0a2318;">Your Shopping Cart 🛒</h2>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo ($message_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['cart'])): ?>
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
                    <?php foreach ($_SESSION['cart'] as $id => $item): 
                        $subtotal = (float)$item['price'] * (int)$item['quantity'];
                    ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td style="color: #10b981; font-weight: bold;">৳<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <div class="qty-controls">
                                    <form method="POST" action="cart.php" style="display:inline;">
                                        <input type="hidden" name="action" value="decrease">
                                        <input type="hidden" name="plant_id" value="<?php echo htmlspecialchars($id); ?>">
                                        <button type="submit" class="btn-qty">-</button>
                                    </form>
                                    <span style="font-weight: bold; width: 24px; text-align: center; display: inline-block;"><?php echo $item['quantity']; ?></span>
                                    <form method="POST" action="cart.php" style="display:inline;">
                                        <input type="hidden" name="action" value="increase">
                                        <input type="hidden" name="plant_id" value="<?php echo htmlspecialchars($id); ?>">
                                        <button type="submit" class="btn-qty">+</button>
                                    </form>
                                </div>
                            </td>
                            <td style="color: #059669; font-weight: 800;">৳<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="checkout-box">
                <form method="POST" action="cart.php" id="checkoutForm">
                    <input type="hidden" name="action" value="checkout">

                    <label style="font-weight: bold; color: #334155;">Select Payment Method:</label>
                    <select class="payment-select" name="payment_method">
                        <option>Pay on Delivery / Pickup</option>
                    </select>

                    <!-- Loyalty Points Redemption Section -->
                    <?php if ($user_points > 0): ?>
                    <div class="redeem-section">
                        <div class="redeem-header">
                            <label class="redeem-toggle">
                                <input type="checkbox" id="usePointsToggle" onchange="toggleRedeem()">
                                <span>⭐ Use Loyalty Points (Available: <strong><?php echo number_format($user_points); ?> PTS</strong> = ৳<?php echo number_format($user_points); ?>)</span>
                            </label>
                        </div>
                        <div class="redeem-input-row" id="redeemInputRow" style="display: none;">
                            <label>Points to redeem:</label>
                            <input type="number" name="redeem_points" id="redeemInput" min="0" max="<?php echo min($user_points, (int)$total_amount); ?>" value="0" oninput="updateFinal()">
                            <span class="redeem-hint">Max: <?php echo min($user_points, (int)$total_amount); ?> points (৳<?php echo min($user_points, (int)$total_amount); ?> off)</span>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="redeem_points" value="0">
                    <?php endif; ?>

                    <div class="final-row">
                        <div>
                            <div id="discountLine" style="display:none; font-size: 14px; color: #ef4444; font-weight: 700; margin-bottom: 6px;">
                                Points Discount: -৳<span id="discountDisplay">0</span>
                            </div>
                            <div style="font-size: 22px; font-weight: bold; color: #1e293b;">
                                Final Total: <span class="final-price" id="finalTotal">৳<?php echo number_format($total_amount, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-checkout">Confirm & Complete Purchase Order ↗</button>
                </form>
            </div>

        <?php else: ?>
            <div class="empty-cart">
                <p style="font-size: 18px; margin-bottom: 20px;">Your cart is currently empty.</p>
                <a href="shop.php" style="background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;">Browse Available Plants</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

<script>
var cartTotal = <?php echo json_encode((float)$total_amount); ?>;
var userPoints = <?php echo json_encode((int)$user_points); ?>;

function toggleRedeem() {
    var checked = document.getElementById('usePointsToggle');
    var row = document.getElementById('redeemInputRow');
    if (!checked || !row) return;
    row.style.display = checked.checked ? 'flex' : 'none';
    if (!checked.checked) {
        document.getElementById('redeemInput').value = 0;
        updateFinal();
    }
}

function updateFinal() {
    var input = document.getElementById('redeemInput');
    if (!input) return;
    var redeem = parseInt(input.value) || 0;
    var maxPts = Math.min(userPoints, Math.floor(cartTotal));
    redeem = Math.max(0, Math.min(redeem, maxPts));

    var finalTotal = cartTotal - redeem;
    document.getElementById('finalTotal').textContent = '৳' + finalTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    var discountLine = document.getElementById('discountLine');
    if (discountLine) {
        discountLine.style.display = redeem > 0 ? 'block' : 'none';
        document.getElementById('discountDisplay').textContent = redeem.toLocaleString();
    }
}
</script>

</body>
</html>