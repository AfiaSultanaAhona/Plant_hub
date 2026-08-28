<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Session validation
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

// Determine user role badge display
$is_employee = isset($_SESSION['Employee_id']) || isset($_SESSION['employee_id']) || isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
$role_label = $is_employee ? '👤 Employee' : '👤 Customer';

$emp_username = $_SESSION['username'] ?? $_SESSION['Employee_name'] ?? ('emp' . preg_replace('/[^0-9]/', '', (string)$employee_id));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Operations Dashboard - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 0; color: #1e293b; }
        .navbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .navbar .logo { font-size: 22px; font-weight: bold; color: #15803d; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #334155; text-decoration: none; font-weight: 600; font-size: 14px; }
        .user-badge { background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .nav-links .btn-logout { background: #fee2e2; color: #ef4444; padding: 6px 16px; border-radius: 20px; transition: background 0.2s; }
        .nav-links .btn-logout:hover { background: #fca5a5; }
        
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .hero-banner { background: #064e3b; color: white; padding: 35px; border-radius: 16px; margin-bottom: 25px; }
        .hero-banner h1 { margin: 0 0 8px; font-size: 26px; }
        .hero-banner p { margin: 0; opacity: 0.85; font-size: 14px; }

        .section-title { font-size: 18px; font-weight: bold; margin: 25px 0 15px; color: #064e3b; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .action-card { background: white; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; text-decoration: none; color: inherit; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s; }
        .action-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); border-color: #10b981; }
        .action-card .icon { font-size: 28px; margin-bottom: 8px; }
        .action-card h3 { margin: 0 0 4px; font-size: 16px; color: #0f172a; }
        .action-card p { margin: 0; font-size: 12px; color: #64748b; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
        .stat-card .num { font-size: 36px; font-weight: bold; color: #059669; margin-bottom: 5px; }
        .stat-card .label { font-size: 13px; color: #64748b; font-weight: 600; }
    </style>
</head>
<body>

<!-- Header Bar showing Logged In Role Status -->
<div class="navbar">
    <a href="employee_dashboard.php" class="logo">🌱 Plant Hub</a>
    <div class="nav-links">
        <a href="employee_dashboard.php">Home 🏠</a>
        <div class="user-badge"><?php echo $role_label; ?></div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="container">
    <div class="hero-banner">
        <h1>Employee Operations Dashboard 📊</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($emp_username); ?></strong>. Here is your operational overview for today.</p>
    </div>

    <!-- Quick Management Action Cards -->
    <div class="section-title">⚡ Quick Management Actions</div>
    <div class="quick-actions">
        <a href="manage_plants.php" class="action-card">
            <div class="icon">➕</div>
            <h3>Add New Plant</h3>
            <p>Insert new plant inventory & stock</p>
        </a>
        
        <a href="manage_plants.php" class="action-card">
            <div class="icon">🌿</div>
            <h3>Manage Plants</h3>
            <p>View, edit, or remove plant stock</p>
        </a>

        <a href="manage_plants.php" class="action-card">
            <div class="icon">📁</div>
            <h3>Plant Categories</h3>
            <p>Add and manage plant categories</p>
        </a>

        <a href="audit_log.php" class="action-card">
            <div class="icon">📋</div>
            <h3>Audit Trail</h3>
            <p>Track all logged employee actions</p>
        </a>
    </div>

    <!-- System Overview Counters -->
    <div class="section-title">📈 System Overview</div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="num">2</div>
            <div class="label">Assigned Services Pending</div>
        </div>
        <div class="stat-card">
            <div class="num">77</div>
            <div class="label">Exchange Requests Awaiting Inspection</div>
        </div>
        <div class="stat-card">
            <div class="num">0%</div>
            <div class="label">Service Resolution Rate</div>
        </div>
    </div>
</div>

</body>
</html>