<?php
// shop.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

// 1. Identify active logged-in user from session
$user_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$total_points = 0;

if ($conn && $user_id) {
    // 2. Fetch all completed order amounts for this customer
    $order_sql = "SELECT amount, total_amount, price, total 
                  FROM orders 
                  WHERE customer_id = '$user_id' OR user_id = '$user_id'";
    $order_query = mysqli_query($conn, $order_sql);

    $calculated_points = 0;
    if ($order_query && mysqli_num_rows($order_query) > 0) {
        while ($order = mysqli_fetch_assoc($order_query)) {
            // Find the active price column in your orders table
            $total_val = (float)($order['amount'] ?? $order['total_amount'] ?? $order['price'] ?? $order['total'] ?? 0);
            
            // 10 points per complete ৳500 spent
            $calculated_points += (int)floor($total_val / 500) * 10;
        }
    }

    // 3. Update customer table balance so it stays synced
    $update_sql = "UPDATE customer 
                   SET loyalty_points = '$calculated_points' 
                   WHERE customer_id = '$user_id' OR id = '$user_id'";
    mysqli_query($conn, $update_sql);

    // 4. Fetch the final clean value to display
    $pts_sql = "SELECT loyalty_points 
                FROM customer 
                WHERE customer_id = '$user_id' OR id = '$user_id'";
    $pts_query = mysqli_query($conn, $pts_sql);
    
    if ($pts_query && $row = mysqli_fetch_assoc($pts_query)) {
        $total_points = (int)($row['loyalty_points'] ?? 0);
    } else {
        $total_points = $calculated_points;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Hub - Available Plants</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f5; margin: 0; padding: 0; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ffffff; padding: 15px 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .logo { font-size: 22px; font-weight: bold; color: #15803d; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-links { display: flex; align-items: center; gap: 20px; list-style: none; margin: 0; padding: 0; }
        .nav-links a { text-decoration: none; color: #374151; font-weight: 500; font-size: 15px; }
        .pts-pill { background-color: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 5px; }
        .btn-logout { background-color: #fecdd3; color: #9f1239; padding: 6px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .badge-cat { background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .price { font-size: 20px; font-weight: bold; color: #059669; margin: 10px 0; }
        .btn-add { width: 100%; background: #10b981; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="shop.php" class="logo">🪴 Plant Hub</a>
    <ul class="nav-links">
        <li><a href="shop.php">Home 🏠</a></li>
        <li><a href="cart.php">My Cart 🛒 (0)</a></li>
        <li><a href="my_orders.php">My Orders 📦</a></li>
        <li>
            <span class="pts-pill">
                🌿 Points: <?php echo number_format($total_points); ?>
            </span>
        </li>
        <li><a href="logout.php" class="btn-logout">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="section-header">
        <h2>Available Plants 🌿</h2>
    </div>

    <div class="grid">
        <div class="card">
            <span class="badge-cat">Indoor Plants</span>
            <h3>Monstera Deliciosa</h3>
            <div class="price">৳650.00</div>
            <p><strong>Stock:</strong> 50 available</p>
            <button class="btn-add">Add to Cart 🛒</button>
        </div>
        <div class="card">
            <span class="badge-cat">Indoor Plants</span>
            <h3>Snake Plant</h3>
            <div class="price">৳500.00</div>
            <p><strong>Stock:</strong> 20 available</p>
            <button class="btn-add">Add to Cart 🛒</button>
        </div>
        <div class="card">
            <span class="badge-cat">Indoor Plants</span>
            <h3>Money Plant</h3>
            <div class="price">৳300.00</div>
            <p><strong>Stock:</strong> 15 available</p>
            <button class="btn-add">Add to Cart 🛒</button>
        </div>
    </div>
</div>

</body>
</html>