<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$emp_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Ahona';

// 1. Ensure services table exists safely
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_type VARCHAR(255),
    customer_name VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Assigned',
    assigned_employee VARCHAR(100) DEFAULT 'Ahona'
)");

// 2. Seed initial sample service rows if empty
$check_srv = mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
if ($check_srv && ($c_row = mysqli_fetch_assoc($check_srv)) && (int)$c_row['total'] === 0) {
    mysqli_query($conn, "INSERT INTO services (service_type, customer_name, status, assigned_employee) VALUES 
        ('Plant Care Consultation', 'Afia Sultana', 'Assigned', 'Ahona'),
        ('Garden Installation & Setup', 'John Doe', 'In Progress', 'Ahona')");
}

// 3. Fetch Live Counts for Services
$pending_services = 0;
$total_srv = 1;
$completed_srv = 0;

$srv_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM services WHERE status != 'Completed'");
if ($srv_count_q && $r = mysqli_fetch_assoc($srv_count_q)) {
    $pending_services = (int)$r['total'];
}

$total_srv_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
if ($total_srv_q && $r = mysqli_fetch_assoc($total_srv_q)) {
    $total_srv = max(1, (int)$r['total']);
}

$completed_srv_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM services WHERE status = 'Completed'");
if ($completed_srv_q && $r = mysqli_fetch_assoc($completed_srv_q)) {
    $completed_srv = (int)$r['total'];
}

$resolution_rate = round(($completed_srv / $total_srv) * 100);

// 4. Fetch Live Counts for Orders/Exchanges (Safe fallback)
$pending_exchanges = 0;
$ex_count_q = @mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
if ($ex_count_q && $r = mysqli_fetch_assoc($ex_count_q)) {
    $pending_exchanges = (int)$r['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1050px; margin: 25px auto; padding: 0 20px; }
        .welcome-banner { background: #064e3b; color: white; padding: 30px; border-radius: 16px; margin-bottom: 25px; }
        .welcome-banner h2 { margin: 0 0 8px; font-size: 26px; font-weight: 800; }
        .welcome-banner p { margin: 0; opacity: 0.9; font-size: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; }
        .stat-card h3 { margin: 0; font-size: 34px; color: #059669; font-weight: 800; }
        .stat-card p { margin: 6px 0 0; color: #4b5563; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="container">
    <div class="welcome-banner">
        <h2>Employee Operations Dashboard 📊</h2>
        <p>Welcome back, <strong><?php echo htmlspecialchars($emp_name); ?></strong>. Here is your operational overview for today.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $pending_services; ?></h3>
            <p>Assigned Services Pending</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $pending_exchanges; ?></h3>
            <p>Exchange Requests Awaiting Inspection</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $resolution_rate; ?>%</h3>
            <p>Service Resolution Rate</p>
        </div>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>