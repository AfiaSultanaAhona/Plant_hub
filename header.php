<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check logged in status & user role from session
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['user']) || isset($_SESSION['email']) || isset($_SESSION['role']);
$role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'customer'); 
?>

<header style="background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 30px; display: flex; align-items: center; justify-content: space-between;">
    <!-- Logo -->
    <a href="index.php" style="font-size: 22px; font-weight: 800; color: #15803d; text-decoration: none; display: flex; align-items: center; gap: 8px;">
        🌿 Plant Hub
    </a>

    <!-- Navigation Links -->
    <nav style="display: flex; gap: 20px; align-items: center;">
        <?php if ($is_logged_in && ($role === 'staff' || $role === 'admin' || $role === 'employee')): ?>
            <!-- Staff Navigation -->
            <a href="sales_dashboard.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Sales Tracking 📈</a>
            <a href="inventory.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Inventory 🌿</a>
            <a href="dashboard.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Dashboard 📊</a>
            <a href="services.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Assigned Services 🛠️</a>
            <a href="exchanges.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Process Exchanges 🔄</a>

            <span style="background: #10b981; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                👨‍🌾 Staff Account
            </span>
            <a href="logout.php" style="background: #ffe4e6; color: #e11d48; text-decoration: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                Logout
            </a>

        <?php elseif ($is_logged_in): ?>
            <!-- Logged-in Customer Navigation -->
            <a href="index.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Home 🏡</a>
            <a href="cart.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">My Cart 🛒</a>
            <a href="orders.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">My Orders 📦</a>

            <span style="background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                👤 Customer
            </span>
            <a href="logout.php" style="background: #ffe4e6; color: #e11d48; text-decoration: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                Logout
            </a>

        <?php else: ?>
            <!-- Guest Navigation (Not Logged In) -->
            <a href="index.php" style="text-decoration: none; color: #475569; font-weight: 600; font-size: 14px;">Home 🏡</a>
            <a href="login.php" style="background: #f1f5f9; color: #0f172a; text-decoration: none; padding: 8px 18px; border-radius: 20px; font-size: 14px; font-weight: 700;">
                Log In
            </a>
            <a href="signup.php" style="background: #10b981; color: white; text-decoration: none; padding: 8px 18px; border-radius: 20px; font-size: 14px; font-weight: 700;">
                Sign Up
            </a>
        <?php endif; ?>
    </nav>
</header>