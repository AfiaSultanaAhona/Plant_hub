<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("DBconnect.php");

// 1. Unified session check across all possible session key names
$user_id = $_SESSION['Customer_ID'] 
        ?? $_SESSION['user_id'] 
        ?? $_SESSION['customer_id'] 
        ?? $_SESSION['cid'] 
        ?? $_SESSION['id'] 
        ?? null;

$header_user_points = 0;

// 2. Fetch live points balance directly from database
if ($user_id) {
    $clean_id = mysqli_real_escape_string($conn, $user_id);
    $pts_res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = '$clean_id'");
    if ($pts_res && $p_row = mysqli_fetch_assoc($pts_res)) {
        $header_user_points = (int)($p_row['points'] ?? 0);
    }
}

// 3. Calculate total items currently in cart
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
        .nav-link:hover { color: #15803d; }
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
        }
    </style>
</head>
<body>

<header class="navbar">
    <a href="shop.php" class="navbar-brand">🌿 Plant Hub</a>

    <ul class="navbar-nav">
        <li><a href="shop.php" class="nav-link">Home 🏠</a></li>
        <li><a href="cart.php" class="nav-link">My Cart 🛒 (<?php echo $cart_count; ?>)</a></li>
        <li><a href="my_orders.php" class="nav-link">My Orders 📦</a></li>
        
        <!-- Live Synchronized Points Badge -->
        <li>
            <div class="points-badge">
                🌿 Points: <?php echo number_format($header_user_points); ?>
            </div>
        </li>

        <?php if ($user_id): ?>
            <li><div class="user-badge">👤 Customer</div></li>
            <li><a href="logout.php" class="btn-logout">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php" class="nav-link">Login</a></li>
            <li><a href="signup.php" class="nav-link">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</header>

</body>
</html>