<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    header("Location: login.php");
    exit();
}

$query = mysqli_query($conn, "SELECT * FROM customer WHERE Customer_ID = '$customer_id'");
$user = mysqli_fetch_assoc($query);

$points = (int)($user['points'] ?? 0);
$wallet = (float)($user['wallet_balance'] ?? 0.00);

// Calculate Loyalty Tier
$tier = "Bronze";
$badge_color = "#cd7f32";
if ($points >= 500) {
    $tier = "Gold";
    $badge_color = "#ffd700";
} elseif ($points >= 100) {
    $tier = "Silver";
    $badge_color = "#c0c0c0";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Account - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef7f2; padding: 40px; }
        .profile-card { background: white; max-width: 500px; margin: auto; padding: 25px; border-radius: 12px; }
        .tier-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; color: white; font-weight: bold; }
    </style>
</head>
<body>
<div class="profile-card">
    <h2>Welcome, <?php echo htmlspecialchars($user['Customer_name'] ?? 'Customer'); ?> 👋</h2>
    <hr>
    <p><strong>Loyalty Tier:</strong> 
        <span class="tier-badge" style="background-color: <?php echo $badge_color; ?>;">
            <?php echo $tier; ?> Member
        </span>
    </p>
    <p><strong>Available Reward Points:</strong> <?php echo $points; ?> pts</p>
    <p><strong>Store Wallet Balance:</strong> ৳<?php echo number_format($wallet, 2); ?></p>
</div>
</body>
</html>