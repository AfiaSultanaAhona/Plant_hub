<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Detect login state across possible session keys
$is_logged_in = isset($_SESSION['customer_id']) || isset($_SESSION['user_id']) || isset($_SESSION['Employee_id']) || isset($_SESSION['employee_id']);

// 2. Identify role (Employee vs Customer)
$is_employee = isset($_SESSION['Employee_id']) || isset($_SESSION['employee_id']) || ($_SESSION['role'] ?? $_SESSION['user_type'] ?? '') === 'employee';
$role_label = $is_employee ? 'Employee' : 'Customer';
$profile_link = $is_employee ? 'employee_dashboard.php' : 'profile.php';

// Wallet balance is shown only for logged-in customers.
$wallet_balance = 0.00;
if ($is_logged_in && !$is_employee) {
    require_once "DBconnect.php";

    $customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;
    $customer_id = (int) preg_replace('/[^0-9]/', '', (string)$customer_id);

    if ($customer_id > 0) {
        $wallet_result = mysqli_query(
            $conn,
            "SELECT wallet_balance FROM customer WHERE Customer_ID = '$customer_id' LIMIT 1"
        );

        if ($wallet_result && ($wallet_row = mysqli_fetch_assoc($wallet_result))) {
            $wallet_balance = (float)($wallet_row['wallet_balance'] ?? 0);
        }
    }
}
?>
<header style="background: #ffffff; padding: 15px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; font-family: 'Segoe UI', sans-serif;">
    <a href="index.php" style="font-size: 22px; font-weight: bold; color: #065f46; text-decoration: none; display: flex; align-items: center; gap: 8px;">
        🌿 Plant Hub
    </a>

    <nav style="display: flex; align-items: center; gap: 15px;">
        <a href="index.php" style="color: #334155; text-decoration: none; font-weight: 500;">Home 🏡</a>
        <a href="cart.php" style="color: #334155; text-decoration: none; font-weight: 500;">My Cart 🛒</a>
        <a href="my_orders.php" style="color: #334155; text-decoration: none; font-weight: 500;">My Orders 📦</a>

        <?php if ($is_logged_in): ?>
            <?php if ($is_employee): ?>
                <a href="employee_dashboard.php" style="color: #047857; text-decoration: none; font-weight: 600;">🛠️ Dashboard</a>
            <?php endif; ?>
            
            <?php if (!$is_employee): ?>
                <span style="background: #ecfdf5; color: #047857; padding: 8px 14px; border-radius: 20px; font-weight: 600; white-space: nowrap;">
                    💳 Wallet: ৳<?php echo number_format($wallet_balance, 2); ?>
                </span>
            <?php endif; ?>

            <a href="<?php echo $profile_link; ?>" style="background: #e0f2fe; color: #0369a1; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600;">
                👤 <?php echo $role_label; ?>
            </a>
            <a href="logout.php" style="background: #ffe4e6; color: #e11d48; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600;">Logout</a>
        <?php else: ?>
            <a href="login.php" style="background: #10b981; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600;">Login</a>
            <a href="signup.php" style="background: #f1f5f9; color: #334155; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600;">Sign Up</a>
        <?php endif; ?>
    </nav>
</header>