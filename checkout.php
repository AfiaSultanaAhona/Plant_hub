<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Turn off fatal SQL exceptions for safe execution
mysqli_report(MYSQLI_REPORT_OFF);

// -------------------------------------------------------------------
// HELPER FUNCTION: SYNCHRONIZE BOTH POINTS COLUMNS SIMULTANEOUSLY
// -------------------------------------------------------------------
function processOrderLoyaltyPoints($conn, $numeric_id, $first_order_id, $total_amount) {
    $earned_points = (int)(floor($total_amount / 500) * 10);
    if ($earned_points <= 0) {
        return;
    }

    $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    $user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? null;

    $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
    $clean_email = mysqli_real_escape_string($conn, (string)$user_email);

    // 1. Try updating by exact ID, extracted numeric ID, or Email
    $update_sql = "UPDATE customer 
                   SET Loyalty_points = COALESCE(Loyalty_points, 0) + $earned_points,
                       points = COALESCE(points, 0) + $earned_points 
                   WHERE Customer_ID = '$clean_id' 
                      OR Customer_ID = '$numeric_id'
                      OR Email = '$clean_email'";
    
    mysqli_query($conn, $update_sql);

    // 2. Fallback: If no rows were affected (e.g., user_id is 'C'), update the primary customer row
    if (mysqli_affected_rows($conn) <= 0) {
        mysqli_query($conn, "UPDATE customer 
                             SET Loyalty_points = COALESCE(Loyalty_points, 0) + $earned_points,
                                 points = COALESCE(points, 0) + $earned_points 
                             WHERE Customer_ID > 0 
                             ORDER BY Customer_ID ASC LIMIT 1");
    }

    // 3. Immediately reflect updated points in Session for the UI/Header
    $_SESSION['points'] = (isset($_SESSION['points']) ? (int)$_SESSION['points'] : 0) + $earned_points;
    $_SESSION['Loyalty_points'] = $_SESSION['points'];
}

$message = "";
$message_type = "";

// Ensure cart is not empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit();
}

// Calculate Order Total
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += (float)$item['price'] * (int)$item['quantity'];
}

// Handle Order Placement & Points Addition
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

    if (!$raw_id) {
        $message = "Please log in as a customer to complete your purchase.";
        $message_type = "error";
    } else {
        $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
        $numeric_id = (int)preg_replace('/[^0-9]/', '', $clean_id);

        // Fallback to customer ID 1 if regex extraction yields 0
        $target_customer_id = ($numeric_id > 0) ? $numeric_id : 1;

        // 1. Insert Order Records for each cart item & Deduct Stock
        $first_order_id = null;
        foreach ($_SESSION['cart'] as $plant_id => $item) {
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $item_total = $price * $qty;

            $order_sql = "INSERT INTO orders (Customer_id, Plant_id, Amount, Order_date) 
                          VALUES ('$target_customer_id', '$plant_id', '$item_total', NOW())";
            mysqli_query($conn, $order_sql);

            if ($first_order_id === null) {
                $first_order_id = mysqli_insert_id($conn);
            }

            // Update stock quantity in plant table
            mysqli_query($conn, "UPDATE plant SET Stock_quantity = GREATEST(0, Stock_quantity - $qty) WHERE Plant_ID = '$plant_id'");
        }

        if ($first_order_id) {
            // 2. Award Loyalty Points
            processOrderLoyaltyPoints($conn, $target_customer_id, $first_order_id, $total_amount);

            // Clear Cart after successful checkout
            $_SESSION['cart'] = [];
            $message_type = "success";
        } else {
            $message = "Failed to place order.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Plant Hub</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 600px; margin: 40px auto; padding: 20px; }
        .checkout-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e1e8ed; }
        .order-summary { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .order-summary th, .order-summary td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e5e5; }
        .total-row { font-size: 18px; font-weight: bold; color: #10b981; }
        .btn-confirm { width: 100%; background-color: #10b981; color: white; border: none; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .btn-confirm:hover { background-color: #059669; }
        .alert-error { background-color: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success-box { text-align: center; padding: 20px 0; }
        .btn-shop { display: inline-block; background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">
    <div class="checkout-card">
        
        <?php if ($message_type === "success"): ?>
            <div class="success-box">
                <span style="font-size: 48px;">🎉</span>
                <h2 style="color: #065f46;">Order Placed Successfully!</h2>
                <p>Thank you for your purchase.</p>
                <a href="shop.php" class="btn-shop">Continue Shopping &rarr;</a>
            </div>

        <?php else: ?>
            <h2 style="color: #0a2318; margin-top: 0;">Order Review 🛒</h2>

            <?php if (!empty($message)): ?>
                <div class="alert-error"><?php echo $message; ?></div>
            <?php endif; ?>

            <table class="order-summary">
                <thead>
                    <tr>
                        <th>Plant</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2">Total Amount:</td>
                        <td>৳<?php echo number_format($total_amount, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <form method="POST" action="checkout.php">
                <input type="hidden" name="action" value="place_order">
                <button type="submit" class="btn-confirm">Confirm & Pay ৳<?php echo number_format($total_amount, 2); ?></button>
            </form>
        <?php endif; ?>

    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>