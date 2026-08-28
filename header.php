<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("DBconnect.php");

// Turn off fatal SQL exceptions for safety
mysqli_report(MYSQLI_REPORT_OFF);

// Default points to 0
$user_points = 0;
$tier_label = "Bronze Tier";

// Determine logged-in user and fetch clean points
if (isset($_SESSION['customer_id']) || isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
    $raw_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'];
    $clean_id = mysqli_real_escape_string($conn, (string)$raw_id);
    
    // Extract numbers only if prefixed (e.g., 'C101' -> '101')
    $numeric_id = preg_replace('/[^0-9]/', '', $clean_id);

    // Query points safely from database
    $query = "SELECT points FROM customer WHERE Customer_ID = '$clean_id' OR Customer_ID = '$numeric_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Explicitly parse points as an integer
        $user_points = isset($row['points']) ? (int)$row['points'] : 0;
    }
}

// Calculate Tier based on points
if ($user_points >= 1000) {
    $tier_label = "Gold Tier";
} elseif ($user_points >= 500) {
    $tier_label = "Silver Tier";
} else {
    $tier_label = "Bronze Tier";
}

$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>

<!-- Header HTML Component -->
<header style="background: #ffffff; border-bottom: 1px solid #e5e7eb; padding: 12px 24px;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
        
        <!-- Brand Logo -->
        <a href="shop.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
            <span style="font-size: 22px;">🌿</span>
            <span style="font-size: 22px; font-weight: 800; color: #065f46;">Plant Hub</span>
        </a>

        <!-- Navigation Links -->
        <nav style="display: flex; align-items: center; gap: 18px;">
            <a href="shop.php" style="color: #374151; text-decoration: none; font-weight: 600; font-size: 14px;">Home 🏠</a>
            <a href="cart.php" style="color: #374151; text-decoration: none; font-weight: 600; font-size: 14px;">
                My Cart 🛒 (<?php echo $cart_count; ?>)
            </a>
            <a href="my_orders.php" style="color: #374151; text-decoration: none; font-weight: 600; font-size: 14px;">My Orders 📦</a>

            <?php 
            $role = $_SESSION['role'] ?? '';
            if ($role === 'employee'): 
            ?>
                <!-- Employee-only links -->
                <a href="employee_dashboard.php" style="color: #374151; text-decoration: none; font-weight: 600; font-size: 14px;">Dashboard 📊</a>
                <a href="purchase/show_purchase.php" style="color: #374151; text-decoration: none; font-weight: 600; font-size: 14px;">Purchases 📦</a>
                <a href="audit_log.php" style="color: #374151; text-decoration: none; font-weight: 600; font-size: 14px;">Audit Log 🔍</a>
            <?php endif; ?>

            <!-- Customer Loyalty Points Badge -->
            <div style="background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 6px;">
                <span>🌿</span>
                <span>Points: <?php echo number_format($user_points); ?></span>
            </div>

            <!-- Profile & Action Buttons -->
            <?php if (isset($_SESSION['customer_id']) || isset($_SESSION['user_id'])): ?>
                <a href="my_account.php" style="background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                    👤 <?php echo $role === 'employee' ? 'Staff' : 'My Account'; ?>
                </a>
                <a href="logout.php" style="background: #ffe4e6; color: #e11d48; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                    Logout
                </a>
            <?php else: ?>
                <a href="login.php" style="background: #10b981; color: white; padding: 6px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                    Login
                </a>
            <?php endif; ?>
        </nav>

    </div>
</header>