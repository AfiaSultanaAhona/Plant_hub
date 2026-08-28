<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Session Validation
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

// 2. User & Role Resolution
$is_employee = isset($_SESSION['Employee_id']) || isset($_SESSION['employee_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'employee');
$role_label = $is_employee ? '👤 Employee' : '👤 Customer';
$emp_username = $_SESSION['username'] ?? $_SESSION['Employee_name'] ?? ('emp' . preg_replace('/[^0-9]/', '', (string)$employee_id));

// 3. Handle In-Page Plant Deletion
$msg = "";
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    if (mysqli_query($conn, "DELETE FROM plant WHERE Plant_ID = $del_id")) {
        $msg = "Plant #$del_id successfully removed.";
    }
}

// 4. View Switcher State
$view = $_GET['view'] ?? 'dashboard';

// 5. Fetch Inventory Data if Inventory view is active
$plants_query = null;
if ($view === 'inventory') {
    $plants_query = mysqli_query($conn, "SELECT * FROM plant ORDER BY Plant_ID DESC");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 0; color: #1e293b; }
        .navbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .navbar .logo { font-size: 22px; font-weight: bold; color: #15803d; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #334155; text-decoration: none; font-weight: 600; font-size: 14px; }
        .nav-links a.active { color: #15803d; border-bottom: 2px solid #15803d; padding-bottom: 4px; }
        .user-badge { background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; }
        .nav-links .btn-logout { background: #fee2e2; color: #ef4444; padding: 6px 16px; border-radius: 20px; text-decoration: none; }
        .nav-links .btn-logout:hover { background: #fca5a5; }
        
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .hero-banner { background: #064e3b; color: white; padding: 35px; border-radius: 16px; margin-bottom: 25px; }
        .hero-banner h1 { margin: 0 0 8px; font-size: 26px; }
        .hero-banner p { margin: 0; opacity: 0.85; font-size: 14px; }

        .section-title { font-size: 18px; font-weight: bold; margin: 25px 0 15px; color: #064e3b; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .action-card { background: white; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; text-decoration: none; color: inherit; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: transform 0.2s; }
        .action-card:hover { transform: translateY(-3px); border-color: #10b981; }
        .action-card .icon { font-size: 28px; margin-bottom: 8px; }
        .action-card h3 { margin: 0 0 4px; font-size: 16px; color: #0f172a; }
        .action-card p { margin: 0; font-size: 12px; color: #64748b; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
        .stat-card .num { font-size: 36px; font-weight: bold; color: #059669; margin-bottom: 5px; }
        .stat-card .label { font-size: 13px; color: #64748b; font-weight: 600; }

        /* Inventory Table Styles */
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px; }
        .table th { background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 14px; }
        .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #334155; }
        .btn-del { background: #ef4444; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; }
        .alert-info { background: #dbeafe; color: #1e40af; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>

<!-- Header Navigation -->
<div class="navbar">
    <a href="employee_dashboard.php" class="logo">🌱 Plant Hub</a>
    <div class="nav-links">
        <a href="employee_dashboard.php" class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>">Home 🏠</a>
        <a href="employee_dashboard.php?view=inventory" class="<?php echo $view === 'inventory' ? 'active' : ''; ?>">Inventory 🌿</a>
        <div class="user-badge"><?php echo $role_label; ?></div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="container">

    <?php if ($view === 'inventory'): ?>
        <!-- INVENTORY VIEW SECTION -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>🌿 Plant Inventory Management</h2>
                <a href="employee_dashboard.php" style="color:#0284c7; text-decoration:none; font-weight:bold;">⬅ Back to Dashboard</a>
            </div>

            <?php if ($msg): ?>
                <div class="alert-info"><?php echo $msg; ?></div>
            <?php endif; ?>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plant Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($plants_query && mysqli_num_rows($plants_query) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($plants_query)): ?>
                        <tr>
                            <td>#<?php echo $p['Plant_ID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['Plant_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['Category'] ?? '-'); ?></td>
                            <td>৳<?php echo number_format($p['Price'], 2); ?></td>
                            <td><?php echo $p['Stock_quantity']; ?></td>
                            <td>
                                <a href="employee_dashboard.php?view=inventory&action=delete&id=<?php echo $p['Plant_ID']; ?>" class="btn-del" onclick="return confirm('Remove plant?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No plant records found in the database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- DASHBOARD MAIN VIEW SECTION -->
        <div class="hero-banner">
            <h1>Employee Operations Dashboard 📊</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($emp_username); ?></strong>. Here is your operational overview for today.</p>
        </div>

        <div class="section-title">⚡ Quick Management Actions</div>
        <div class="quick-actions">
            <a href="add_plant.php" class="action-card">
                <div class="icon">➕</div>
                <h3>Add New Plant</h3>
                <p>Insert new plant inventory & stock</p>
            </a>
            
            <a href="employee_dashboard.php?view=inventory" class="action-card">
                <div class="icon">🌿</div>
                <h3>Manage Plants</h3>
                <p>View, edit, or remove plant stock</p>
            </a>

            <a href="show_category.php" class="action-card">
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
    <?php endif; ?>

</div>

</body>
</html>