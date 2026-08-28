<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");
mysqli_report(MYSQLI_REPORT_OFF);

// Identify logged-in user
$user_id = $_SESSION['Customer_ID'] 
        ?? $_SESSION['user_id'] 
        ?? $_SESSION['customer_id'] 
        ?? $_SESSION['cid'] 
        ?? $_SESSION['id'] 
        ?? null;

$raw_numeric_id = $user_id ? (int)preg_replace('/[^0-9]/', '', (string)$user_id) : 0;
$u_id_esc = $user_id ? mysqli_real_escape_string($conn, (string)$user_id) : '';

// Fetch customer details
$customer = null;
$user_points = 0;
$wallet_balance = 0.00;

if ($raw_numeric_id > 0) {
    $cust_res = mysqli_query($conn, "SELECT * FROM customer WHERE Customer_ID = '$raw_numeric_id'");
    if (!$cust_res || mysqli_num_rows($cust_res) == 0) {
        $cust_res = mysqli_query($conn, "SELECT * FROM customer WHERE Customer_ID = '$u_id_esc'");
    }
    if ($cust_res && $cust_row = mysqli_fetch_assoc($cust_res)) {
        $customer = $cust_row;
        $user_points = (int)($cust_row['points'] ?? $cust_row['loyalty_points'] ?? 0);
        $wallet_balance = (float)($cust_row['wallet_balance'] ?? 0);
    }
}

// Fallback to session data if DB fetch failed
if (!$customer) {
    $user_points = (int)($_SESSION['user_points'] ?? 0);
    $wallet_balance = (float)($_SESSION['wallet_balance'] ?? 0);
}

$user_name = $customer['Customer_name'] ?? $_SESSION['user_name'] ?? 'Customer';
$user_email = $customer['Email'] ?? 'N/A';
$user_phone = $customer['Phone'] ?? 'N/A';
$user_address = $customer['Address'] ?? 'N/A';
$cust_display_id = $customer['Customer_ID'] ?? $raw_numeric_id;

// Fetch order history for points transaction breakdown
$orders_res = null;
if ($raw_numeric_id > 0) {
    $orders_res = @mysqli_query($conn, "SELECT * FROM orders WHERE Customer_ID = '$raw_numeric_id' OR Customer_ID = '$u_id_esc' ORDER BY order_date DESC LIMIT 50");
}
if (!$orders_res || mysqli_num_rows($orders_res) == 0) {
    $orders_res = @mysqli_query($conn, "SELECT * FROM orders ORDER BY order_date DESC LIMIT 50");
}

// Calculate total earned and redeemed
$total_earned = 0;
$total_redeemed = 0;
$order_rows = [];

if ($orders_res) {
    while ($o = mysqli_fetch_assoc($orders_res)) {
        $order_rows[] = $o;
        $amt = (float)($o['Amount'] ?? $o['amount'] ?? 0);
        $pay = $o['payment_method'] ?? $o['Payment_Method'] ?? '';
        $pts_redeemed = (int)($o['points_redeemed'] ?? 0);
        
        if (strcasecmp($pay, 'Loyalty Points') === 0) {
            $total_redeemed += (int)ceil($amt);
        } else {
            $total_earned += (int)floor($amt / 500) * 10;
        }
    }
}

// Calculate points value (each point = ৳1)
$points_value = $user_points;

// Determine tier
$tier = 'Bronze 🥉';
$tier_color = '#cd7f32';
$tier_bg = '#fef3e2';
if ($user_points >= 500) { $tier = 'Gold 🥇'; $tier_color = '#d97706'; $tier_bg = '#fefce8'; }
elseif ($user_points >= 100) { $tier = 'Silver 🥈'; $tier_color = '#6b7280'; $tier_bg = '#f3f4f6'; }

$next_tier = '';
$points_to_next = 0;
if ($user_points < 100) { $next_tier = 'Silver'; $points_to_next = 100 - $user_points; }
elseif ($user_points < 500) { $next_tier = 'Gold'; $points_to_next = 500 - $user_points; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; margin: 25px auto; padding: 0 20px; }

        /* Account Header */
        .account-hero {
            background: linear-gradient(135deg, #064e3b 0%, #0d7b5f 50%, #10b981 100%);
            border-radius: 20px; padding: 35px 40px; color: white; margin-bottom: 25px;
            position: relative; overflow: hidden;
        }
        .account-hero::after {
            content: '🌿'; position: absolute; right: 30px; top: 50%; transform: translateY(-50%);
            font-size: 80px; opacity: 0.15;
        }
        .account-hero .greeting { font-size: 14px; opacity: 0.85; margin: 0 0 6px; }
        .account-hero h1 { margin: 0 0 4px; font-size: 30px; font-weight: 800; }
        .account-hero .member-id { font-size: 13px; opacity: 0.7; }
        .tier-pill {
            display: inline-block; padding: 5px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 800; margin-top: 12px;
        }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 25px; }
        .stat-card {
            background: white; border-radius: 14px; padding: 24px; text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); position: relative; overflow: hidden;
        }
        .stat-card .icon { font-size: 28px; margin-bottom: 8px; }
        .stat-card .value { font-size: 32px; font-weight: 800; margin: 0; }
        .stat-card .label { font-size: 13px; color: #6b7280; font-weight: 600; margin-top: 4px; }
        .stat-card.points .value { color: #10b981; }
        .stat-card.wallet .value { color: #064e3b; }
        .stat-card.tier-card .value { font-size: 22px; }

        /* Two-column layout */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 25px; }

        /* Cards */
        .card {
            background: white; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .card-head {
            padding: 16px 22px; border-bottom: 2px solid #f1f5f9;
            font-size: 16px; font-weight: 800; color: #0a2318;
        }
        .card-body { padding: 22px; }

        /* Profile Info */
        .info-row {
            display: flex; justify-content: space-between; padding: 12px 0;
            border-bottom: 1px solid #f3f4f6; font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; font-weight: 600; }
        .info-value { color: #1f2937; font-weight: 700; }

        /* Points Rules */
        .rule-item {
            display: flex; align-items: flex-start; gap: 12px; padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .rule-item:last-child { border-bottom: none; }
        .rule-icon { font-size: 20px; flex-shrink: 0; margin-top: 2px; }
        .rule-text { font-size: 13px; color: #374151; line-height: 1.5; }
        .rule-text strong { color: #10b981; }

        /* Progress Bar */
        .progress-section { margin-top: 10px; }
        .progress-bar-bg {
            background: #e5e7eb; border-radius: 10px; height: 12px; overflow: hidden; margin: 8px 0;
        }
        .progress-bar-fill {
            height: 100%; border-radius: 10px; transition: width 0.5s ease;
            background: linear-gradient(90deg, #10b981, #059669);
        }
        .progress-text { font-size: 12px; color: #6b7280; display: flex; justify-content: space-between; }

        /* Points History Table */
        .full-card { margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 11px 20px; color: #6b7280; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
        }
        td { padding: 13px 20px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        tr:hover { background: #f0fdf4; }

        .pts-earned { color: #10b981; font-weight: 800; }
        .pts-redeemed { color: #ef4444; font-weight: 800; }
        .payment-badge {
            padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 700;
        }
        .pay-delivery { background: #dbeafe; color: #1d4ed8; }
        .pay-points { background: #fce7f3; color: #be185d; }
        .pay-card { background: #ede9fe; color: #7c3aed; }
        .pay-other { background: #f3f4f6; color: #6b7280; }

        .empty-msg { text-align: center; padding: 40px 20px; color: #9ca3af; font-weight: 600; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">

    <!-- Account Hero -->
    <div class="account-hero">
        <p class="greeting">Welcome back,</p>
        <h1><?php echo htmlspecialchars($user_name); ?></h1>
        <p class="member-id">Member ID: C<?php echo $cust_display_id; ?></p>
        <div class="tier-pill" style="background: <?php echo $tier_bg; ?>; color: <?php echo $tier_color; ?>;">
            <?php echo $tier; ?> Member
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card points">
            <div class="icon">⭐</div>
            <p class="value"><?php echo number_format($user_points); ?></p>
            <p class="label">Loyalty Points Balance</p>
        </div>
        <div class="stat-card wallet">
            <div class="icon">💳</div>
            <p class="value">৳<?php echo number_format($wallet_balance, 2); ?></p>
            <p class="label">Store Wallet Balance</p>
        </div>
        <div class="stat-card tier-card">
            <div class="icon">🏆</div>
            <p class="value" style="color: <?php echo $tier_color; ?>;"><?php echo $tier; ?></p>
            <p class="label">
                <?php if ($next_tier): ?>
                    <?php echo $points_to_next; ?> pts to <?php echo $next_tier; ?>
                <?php else: ?>
                    Highest Tier Achieved!
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Two Column: Profile + Rules -->
    <div class="two-col">
        <!-- Profile Info -->
        <div class="card">
            <div class="card-head">👤 Account Information</div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_email); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_phone); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_address); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Earned</span>
                    <span class="info-value pts-earned">+<?php echo number_format($total_earned); ?> PTS</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Redeemed</span>
                    <span class="info-value pts-redeemed">-<?php echo number_format($total_redeemed); ?> PTS</span>
                </div>
            </div>
        </div>

        <!-- Loyalty Rules + Tier Progress -->
        <div class="card">
            <div class="card-head">🌿 Loyalty Rewards Program</div>
            <div class="card-body">
                <div class="rule-item">
                    <span class="rule-icon">🛒</span>
                    <div class="rule-text">Earn <strong>10 points</strong> for every <strong>৳500</strong> spent on plant purchases.</div>
                </div>
                <div class="rule-item">
                    <span class="rule-icon">💰</span>
                    <div class="rule-text">Redeem points as payment at checkout. <strong>1 point = ৳1</strong> value.</div>
                </div>
                <div class="rule-item">
                    <span class="rule-icon">🥉</span>
                    <div class="rule-text"><strong>Bronze:</strong> 0–99 pts &nbsp; <strong>Silver:</strong> 100–499 pts &nbsp; <strong>Gold:</strong> 500+ pts</div>
                </div>
                <div class="rule-item">
                    <span class="rule-icon">🔄</span>
                    <div class="rule-text">Points are <strong>not earned</strong> when paying with Loyalty Points.</div>
                </div>

                <?php if ($next_tier): ?>
                <div class="progress-section">
                    <div style="font-size: 13px; font-weight: 700; color: #374151; margin-top: 8px;">
                        Progress to <?php echo $next_tier; ?> Tier
                    </div>
                    <?php 
                        $tier_max = ($next_tier === 'Silver') ? 100 : 500;
                        $progress_pct = min(100, round(($user_points / $tier_max) * 100));
                    ?>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?php echo $progress_pct; ?>%;"></div>
                    </div>
                    <div class="progress-text">
                        <span><?php echo number_format($user_points); ?> PTS</span>
                        <span><?php echo number_format($tier_max); ?> PTS</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Points Transaction History -->
    <div class="card full-card">
        <div class="card-head">📊 Points Transaction History</div>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Points Change</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($order_rows)): ?>
                    <?php foreach ($order_rows as $o):
                        $oid = $o['id'] ?? $o['Order_ID'] ?? $o['order_id'] ?? '—';
                        $odate = $o['order_date'] ?? $o['Order_date'] ?? '';
                        $amt = (float)($o['Amount'] ?? $o['amount'] ?? 0);
                        $pay = $o['payment_method'] ?? $o['Payment_Method'] ?? 'N/A';
                        $is_pts = (strcasecmp($pay, 'Loyalty Points') === 0);
                        
                        // Determine badge class
                        $badge_cls = 'pay-other';
                        if (stripos($pay, 'Delivery') !== false || stripos($pay, 'Pickup') !== false) $badge_cls = 'pay-delivery';
                        elseif ($is_pts) $badge_cls = 'pay-points';
                        elseif (stripos($pay, 'Credit') !== false || stripos($pay, 'Card') !== false) $badge_cls = 'pay-card';
                        
                        // Points change
                        if ($is_pts) {
                            $pts_change = '-' . number_format((int)ceil($amt));
                            $pts_cls = 'pts-redeemed';
                        } else {
                            $earned = (int)floor($amt / 500) * 10;
                            $pts_change = '+' . $earned;
                            $pts_cls = 'pts-earned';
                        }
                    ?>
                        <tr>
                            <td style="font-weight: 700; color: #6b7280;">#<?php echo $oid; ?></td>
                            <td><?php echo $odate ? date('M d, Y', strtotime($odate)) : '—'; ?></td>
                            <td style="font-weight: 700; color: #10b981;">৳<?php echo number_format($amt, 2); ?></td>
                            <td><span class="payment-badge <?php echo $badge_cls; ?>"><?php echo htmlspecialchars($pay); ?></span></td>
                            <td><span class="<?php echo $pts_cls; ?>"><?php echo $pts_change; ?> PTS</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty-msg">No order history found. <a href="shop.php" style="color: #10b981; font-weight: 700;">Start Shopping!</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>
