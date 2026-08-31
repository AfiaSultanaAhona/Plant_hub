<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Detect the current login state and role.
 * Logged-out users see only the Plant Hub logo.
 * Customers see: Home, My Cart, My Orders, Customer, Logout.
 * Employees see: Home, Dashboard, Employee, Logout.
 */

$is_customer = isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0;
$is_employee = isset($_SESSION['Employee_id']) || isset($_SESSION['employee_id']);

if (!$is_customer && !$is_employee) {
    $role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
    $is_customer = ($role === 'customer');
    $is_employee = ($role === 'employee');
}

$is_logged_in = $is_customer || $is_employee;
?>

<header style="background:#ffffff; padding:15px 30px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; justify-content:space-between; align-items:center; font-family:'Segoe UI',sans-serif;">
    <a href="index.php" style="font-size:22px; font-weight:bold; color:#065f46; text-decoration:none; display:flex; align-items:center; gap:8px;">
        🌿 Plant Hub
    </a>

    <nav style="display:flex; align-items:center; gap:15px;">

        <?php if ($is_logged_in): ?>

            <!-- Home is available to both customers and employees -->
            <a href="index.php" style="color:#334155; text-decoration:none; font-weight:500;">
                Home 🏡
            </a>

            <?php if ($is_employee): ?>

                <!-- Employee-only navigation -->
                <a href="employee_dashboard.php" style="color:#047857; text-decoration:none; font-weight:600;">
                    🛠️ Dashboard
                </a>

                <a href="employee_dashboard.php" style="background:#e0f2fe; color:#0369a1; padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600;">
                    👤 Employee
                </a>

                <a href="logout.php" style="background:#ffe4e6; color:#e11d48; padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600;">
                    Logout
                </a>

            <?php elseif ($is_customer): ?>

                <!-- Customer-only navigation -->
                <a href="cart.php" style="color:#334155; text-decoration:none; font-weight:500;">
                    My Cart 🛒
                </a>

                <a href="my_orders.php" style="color:#334155; text-decoration:none; font-weight:500;">
                    My Orders 📦
                </a>

                <a href="my_account.php" style="background:#e0f2fe; color:#0369a1; padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600;">
                    👤 Customer
                </a>

                <a href="logout.php" style="background:#ffe4e6; color:#e11d48; padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600;">
                    Logout
                </a>

            <?php endif; ?>

        <?php else: ?>

            <!-- Logged-out users: NO Home / My Cart / My Orders buttons -->
            <a href="login.php" style="background:#10b981; color:white; padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600;">
                Login
            </a>

            <a href="signup.php" style="background:#f1f5f9; color:#334155; padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600;">
                Sign Up
            </a>

        <?php endif; ?>

    </nav>
</header>
