<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Turn off fatal SQL exceptions for safety
mysqli_report(MYSQLI_REPORT_OFF);

$message = "";
$message_type = "";

// Ensure user is logged in and cart is not empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit();
}

// Calculate Order Total
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Handle Order Placement & Points Addition
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

    if (!$raw_id) {
        $message = "Please log in as a customer to complete your purchase.";
        $message_type = "error";
    } else {
        // Clean ID: Handles both 'C12' and '12' format safely
        $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
        $numeric_id = (int)preg_replace('/[^0-9]/', '', $clean_id);

        // 1. Insert Order Record
        $order_sql = "INSERT INTO orders (customer_id, total_amount, order_date) VALUES ('$numeric_id', '$total_amount', NOW())";
        $order_query = mysqli_query($conn, $order_sql);

        if ($order_query) {
            $order_id = mysqli_insert_id($conn);

            // 2. Insert Order Items & Deduct Stock
            foreach ($_SESSION['cart'] as $plant_id => $item) {
                $qty = (int)$item['quantity'];
                $price = (float)$item['price'];

                // Record item in order details
                mysqli_query($conn, "INSERT INTO order_items (order_id, plant_id, quantity, price) VALUES ('$order_id', '$plant_id', '$qty', '$price')");

                // Update stock quantity in plant table
                mysqli_query($conn, "UPDATE plant SET Stock_quantity = GREATEST(0, Stock_quantity - $qty) WHERE Plant_ID = '$plant_id'");
            }

            // 3. Calculate Earned Points (10 points per ৳500 spent)
            $earned_points = floor($total_amount / 500) * 10;

            if ($earned_points > 0) {
                // Update customer points using both possible ID patterns
                $update_points_sql = "UPDATE customer 
                                      SET points = COALESCE(points, 0) + $earned_points 
                                      WHERE Customer_ID = '$clean_id' OR Customer_ID = '$numeric_id'";
                mysqli_query($conn, $update_points_sql);
            }

            // Clear Cart after successful checkout
            $_SESSION['cart'] = [];
            $message_type = "success";
        } else {
            $message = "Failed to place order: " . mysqli_error($conn);
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
        .order-summary th, .order-summary td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; }
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