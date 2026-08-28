<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

mysqli_report(MYSQLI_REPORT_OFF);

// Fetch user info
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? $_SESSION['id'] ?? $_SESSION['Customer_ID'] ?? '1';
$u_id_esc = mysqli_real_escape_string($conn, (string)$user_id);
$raw_numeric_id = (int)preg_replace('/[^0-9]/', '', (string)$user_id);

// Fetch User Name & Loyalty Points — read from `points` column (same as cart.php)
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['Customer_Name'] ?? 'AFIA SULTANA';
$loyalty_points = 0;

$user_q = @mysqli_query($conn, "SELECT * FROM customer WHERE Customer_id = '$raw_numeric_id' OR Customer_ID = '$u_id_esc'");
if ($user_q && $u_row = mysqli_fetch_assoc($user_q)) {
    $user_name = $u_row['Customer_Name'] ?? $u_row['name'] ?? $u_row['username'] ?? $user_name;
    $loyalty_points = (int)($u_row['points'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Plant Hub - Home</title>
    <style>
        .hero-banner {
            background-color: #064e3b;
            border-radius: 16px;
            padding: 45px;
            color: white;
            max-width: 1000px;
            margin: 0 auto 30px;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Pill Badge with Name + Points */
        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #a7f3d0;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .pts-count {
            background: #f59e0b;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 900;
            font-size: 12px;
            margin-left: 4px;
        }

        .hero-title { font-size: 38px; font-weight: 800; margin: 0 0 15px; }
        .hero-sub { font-size: 15px; opacity: 0.9; max-width: 500px; margin-bottom: 25px; line-height: 1.5; }
        .btn-explore { background: #10b981; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div style="padding: 0 20px;">
    <div class="hero-banner">
       <!-- Welcome Badge (Points Pill Removed) -->
<div style="display: inline-block; background: rgba(255, 255, 255, 0.15); padding: 8px 18px; border-radius: 30px; font-weight: 800; font-size: 13px; letter-spacing: 0.5px; margin-bottom: 20px;">
    🌿 WELCOME BACK, <?php echo htmlspecialchars(strtoupper($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'AFIA SULTANA')); ?>!
</div>

        <h1 class="hero-title">Bring Nature Fresh Into Your Living Space</h1>
        <p class="hero-sub">Discover our hand-picked collection of premium indoor, outdoor, and flowering plants delivered directly to your doorstep.</p>

        <a href="shop.php" class="btn-explore">Explore All Plants 🪴</a>
    </div>
</div>

</body>
</html>