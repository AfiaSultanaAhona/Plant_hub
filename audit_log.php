<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Employee Session Security Check
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

// Fetch search and filter parameters
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$module = mysqli_real_escape_string($conn, $_GET['module'] ?? '');

$query = "SELECT * FROM audit_trail WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (employee_id LIKE '%$search%' OR action_type LIKE '%$search%' OR description LIKE '%$search%')";
}
if (!empty($module) && $module !== 'All') {
    $query .= " AND action_type LIKE '%$module%'";
}
$query .= " ORDER BY log_id DESC";

$audit_result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Trail & Employee Tracking - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        
        /* Dark Navbar matching Audit Trail design */
        .navbar { background: #0f172a; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .navbar .logo { font-size: 20px; font-weight: bold; color: #ffffff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 14px; }
        .nav-links a:hover { text-decoration: underline; }

        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .card-header { font-size: 22px; font-weight: bold; margin-bottom: 20px; color: #0f172a; display: flex; align-items: center; gap: 10px; }

        .filter-bar { display: flex; gap: 12px; margin-bottom: 25px; }
        .filter-bar input[type="text"] { flex: 2; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        .filter-bar select { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white; }
        .btn-filter { background: #0284c7; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-reset { background: #64748b; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; }

        .table { width: 100%; border-collapse: collapse; text-align: left; }
        .table th { background: #f1f5f9; padding: 12px; font-size: 13px; color: #334155; font-weight: 700; border-bottom: 1px solid #e2e8f0; }
        .table td { padding: 14px 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #475569; }
        .empty-state { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
    </style>
</head>
<body>

<!-- Updated Navbar with Inventory removed -->
<div class="navbar">
    <a href="audit_log.php" class="logo">📋 Audit Trail & Employee Tracking</a>
    <div class="nav-links">
        <a href="employee_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <div class="card-header">Transaction & Operation Audit Trail 🛠️</div>

        <!-- Filter Form -->
        <form method="GET" action="audit_log.php" class="filter-bar">
            <input type="text" name="search" placeholder="Search by Employee ID, Name, or Key Terms..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="module">
                <option value="All">All Module Operations</option>
                <option value="PLANT" <?php echo $module === 'PLANT' ? 'selected' : ''; ?>>Plant Operations</option>
                <option value="CATEGORY" <?php echo $module === 'CATEGORY' ? 'selected' : ''; ?>>Category Operations</option>
            </select>
            <button type="submit" class="btn-filter">Filter Audit Trail</button>
            <a href="audit_log.php" class="btn-reset">Reset</a>
        </form>

        <!-- Audit Records Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>Log #</th>
                    <th>Handling Employee</th>
                    <th>Operation Type</th>
                    <th>Ref ID</th>
                    <th>Details / Transaction Summary</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($audit_result && mysqli_num_rows($audit_result) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($audit_result)): ?>
                    <tr>
                        <td>#<?php echo $log['log_id'] ?? $log['Log_ID']; ?></td>
                        <td>emp<?php echo $log['employee_id'] ?? $log['Employee_ID']; ?></td>
                        <td><strong><?php echo htmlspecialchars($log['action_type'] ?? $log['Action_Type']); ?></strong></td>
                        <td><?php echo $log['reference_id'] ?? $log['Reference_ID'] ?? '-'; ?></td>
                        <td><?php echo htmlspecialchars($log['description'] ?? $log['Description']); ?></td>
                        <td><?php echo $log['timestamp'] ?? $log['Timestamp']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">No audit trail records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>