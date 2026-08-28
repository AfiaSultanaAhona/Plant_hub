<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        <a href="profile.php" style="background: #e0f2fe; color: #0369a1; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600;">👤 Customer</a>
        <a href="logout.php" style="background: #ffe4e6; color: #e11d48; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600;">Logout</a>
    </nav>
</header>