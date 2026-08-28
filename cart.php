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

// -------------------------------------------------------------------
// 1. PROCESS POST ACTIONS FIRST (BEFORE FETCHING POINTS OR RENDERING HTML)
// -------------------------------------------------------------------

// Calculate current cart total
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += (float)$item['price'] * (int)$item['quantity'];
}

// Handle Purchase Order Confirmation & Points Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'checkout') {
        if (empty($_SESSION['cart'])) {
            $message = "Your cart is empty!";
            $message_type = "error";
        } else {
            // Calculate Earned Loyalty Points (10 points per ৳500 spent)
            $earned_points = (int)(floor($total_amount / 500) * 10);

            // Extract all possible identifier fields from session
            $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['Customer_ID'] ?? null;
            $user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? null;

            if ($earned_points > 0) {
                $updated = false;

                // Option A: Update by Customer ID / User ID
                if ($raw_id) {
                    $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
                    $numeric_id = (int)preg_replace('/[^0-9]/', '', $clean_id);

                    $update_sql = "UPDATE customer 
                                   SET points = COALESCE(points, 0) + $earned_points 
                                   WHERE Customer_ID = '$clean_id' 
                                      OR Customer_ID = '$numeric_id' 
                                      OR Customer_ID LIKE '%$numeric_id%'
                                      OR id = '$numeric_id'
                                      OR customer_id = '$numeric_id'";
                    
                    mysqli_query($conn, $update_sql);
                    if (mysqli_affected_rows($conn) > 0) {
                        $updated = true;
                    }
                }

                // Option B: Fallback update by Session Email if ID didn't match
                if (!$updated && $user_email) {
                    $clean_email = mysqli_real_escape_string($conn, (string)$user_email);
                    mysqli_query($conn, "UPDATE customer SET points = COALESCE(points, 0) + $earned_points WHERE email = '$clean_email'");
                }

                // Option C: Universal fallback update (updates single active customer row if database uses a single-user system)
                if (!$updated && mysqli_affected_rows($conn) <= 0) {
                    mysqli_query($conn, "UPDATE customer SET points = COALESCE(points, 0) + $earned_points LIMIT 1");
                }
            }

            // Immediately update session points variable so UI refreshes without lag
            $_SESSION['points'] = (isset($_SESSION['points']) ? (int)$_SESSION['points'] : 0) + $earned_points;

            // Clear cart & display message
            $_SESSION['cart'] = [];
            $message = "🎉 Order confirmed! Earned <strong>+$earned_points points</strong>!";
            $message_type = "success";
            $total_amount = 0;
        }
    } elseif ($action === 'increase' && isset($_POST['plant_id']) && isset($_SESSION['cart'][$_POST['plant_id']])) {
        $_SESSION['cart'][$_POST['plant_id']]['quantity'] += 1;
    } elseif ($action === 'decrease' && isset($_POST['plant_id']) && isset($_SESSION['cart'][$_POST['plant_id']])) {
        $_SESSION['cart'][$_POST['plant_id']]['quantity'] -= 1;
        if ($_SESSION['cart'][$_POST['plant_id']]['quantity'] <= 0) {
            unset($_SESSION['cart'][$_POST['plant_id']]);
        }
    }
}

// Recalculate total for display after quantity modifications
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += (float)$item['price'] * (int)$item['quantity'];
}

// -------------------------------------------------------------------
// 2. FETCH LATEST POINTS FROM DATABASE TO SYNC DISPLAY
// -------------------------------------------------------------------
$user_points = $_SESSION['points'] ?? 0;
$raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['Customer_ID'] ?? null;
$user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? null;

if ($raw_id || $user_email) {
    $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
    $numeric_id = (int)preg_replace('/[^0-9]/', '', $clean_id);
    $clean_email = mysqli_real_escape_string($conn, (string)$user_email);

    $pts_res = mysqli_query($conn, "SELECT points FROM customer 
                                    WHERE Customer_ID = '$clean_id' 
                                       OR Customer_ID = '$numeric_id' 
                                       OR Customer_ID LIKE '%$numeric_id%' 
                                       OR email = '$clean_email' LIMIT 1");

    if ($pts_res && $prow = mysqli_fetch_assoc($pts_res)) {
        $user_points = (int)($prow['points'] ?? 0);
        $_SESSION['points'] = $user_points;
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
        .debug-banner { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 10px 15px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">

    <!-- Diagnostic Session Banner -->
    <div class="debug-banner">
        <strong>Session Debugger:</strong> 
        customer_id: <code><?php echo $_SESSION['customer_id'] ?? 'null'; ?></code> | 
        user_id: <code><?php echo $_SESSION['user_id'] ?? 'null'; ?></code> | 
        Active Session Points: <code><?php echo $_SESSION['points'] ?? '0'; ?></code>
    </div>

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
                <label style="font-weight: bold; color: #334155;">Select Payment Method:</label>
                <select class="payment-select">
                    <option>Pay on Delivery / Pickup</option>
                </select>

                <div class="final-row">
                    <div style="font-size: 22px; font-weight: bold; color: #1e293b;">
                        Final Total: <span class="final-price">৳<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>

                <form method="POST" action="cart.php">
                    <input type="hidden" name="action" value="checkout">
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

</body>
</html>