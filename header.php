<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("DBconnect.php");

// Fetch live Loyalty Points balance for the current user
$header_user_points = 0;
$user_id = $_SESSION['user_id'] ?? $_SESSION['Customer_ID'] ?? $_SESSION['customer_id'] ?? null;
$clean_user_id = preg_replace('/[^0-9]/', '', (string)$user_id);

if (!empty($clean_user_id)) {
    $pts_res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = '$clean_user_id'");
    if ($pts_res && $p_row = mysqli_fetch_assoc($pts_res)) {
        $header_user_points = (int)($p_row['points'] ?? 0);
    }
}

// Calculate total cart items count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)($item['quantity'] ?? 1);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        .navbar {
            background-color: #ffffff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: 800;
            color: #15803d;
            text-decoration: none;
        }
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-link {
            text-decoration: none;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nav-link:hover {
            color: #15803d;
        }
        .points-badge {
            background-color: #dcfce7;
            color: #15803d;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #bbf7d0;
        }
        .user-badge {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-logout {
            background-color: #ffe4e6;
            color: #e11d48;
            padding: 6px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background-color: #fecdd3;
        }
    </style>
</head>
<body>

<header class="navbar">
    <a href="shop.php" class="navbar-brand">
        🌿 Plant Hub
    </a>

    <ul class="navbar-nav">
        <li><a href="shop.php" class="nav-link">Home 🏠</a></li>
        <li><a href="cart.php" class="nav-link">My Cart 🛒 (<?php echo $cart_count; ?>)</a></li>
        <li><a href="my_orders.php" class="nav-link">My Orders 📦</a></li>
        
        <!-- Dynamically synchronized Points Badge -->
        <li>
            <div class="points-badge">
                🌿 Points: <?php echo number_format($header_user_points); ?>
            </div>
        </li>

        <?php if (isset($_SESSION['user_id']) || isset($_SESSION['customer_id']) || isset($_SESSION['Customer_ID'])): ?>
            <li>
                <div class="user-badge">
                    👤 Customer
                </div>
            </li>
            <li><a href="logout.php" class="btn-logout">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php" class="nav-link">Login</a></li>
            <li><a href="signup.php" class="nav-link">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</header>

</body>
</html>