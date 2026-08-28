<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// Active Employee ID check
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
$clean_emp_id = (int) preg_replace('/[^0-9]/', '', (string)$employee_id);

// Filtering and Search Parameters
$search_emp = mysqli_real_escape_string($conn, $_GET['search_emp'] ?? '');
$filter_action = mysqli_real_escape_string($conn, $_GET['filter_action'] ?? '');

$where_clauses = [];
if (!empty($search_emp)) {
    $where_clauses[] = "(a.Employee_id LIKE '%$search_emp%' OR e.Name LIKE '%$search_emp%' OR a.Details LIKE '%$search_emp%')";
}
if (!empty($filter_action)) {
    $where_clauses[] = "a.Action_type = '$filter_action'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Retrieve Audit Logs with joined Employee information
$query = "SELECT a.*, e.Name AS Employee_Name 
          FROM audit_log a 
          LEFT JOIN employee e ON (a.Employee_id = e.Employee_ID OR a.Employee_id = e.Employee_id)
          $where_sql 
          ORDER BY a.Log_id DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Trail & Activity Logs - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .navbar { background: #0f172a; padding: 15px 30px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .navbar a { color: #38bdf8; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .filter-grid { display: flex; gap: 12px; margin-bottom: 20px; }
        .form-control { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; flex: 1; }
        .btn { background: #0284c7; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .table th { background: #f1f5f9; color: #334155; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-sale { background: #d1fae5; color: #065f46; }
        .badge-purchase { background: #e0f2fe; color: #0369a1; }
        .badge-exchange { background: #fef08a; color: #854d0e; }
        .badge-inventory { background: #f1f5f9; color: #475569; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>📋 Audit Trail & Employee Tracking</h2>
    <div>
        <a href="employee_dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2 style="margin-top:0; color: #0f172a;">Transaction & Operation Audit Trail 🛠️</h2>

        <form method="GET" class="filter-grid">
            <input type="text" name="search_emp" class="form-control" 
                   placeholder="Search by Employee ID, Name, or Key Terms..." 
                   value="<?php echo htmlspecialchars($search_emp); ?>">
                   
            <select name="filter_action" class="form-control" style="flex: 0.5;">
                <option value="">All Module Operations</option>
                <option value="SALE" <?php if ($filter_action === 'SALE') echo 'selected'; ?>>SALE (Customer Order)</option>
                <option value="PURCHASE" <?php if ($filter_action === 'PURCHASE') echo 'selected'; ?>>PURCHASE (Supplier Buy)</option>
                <option value="EXCHANGE" <?php if ($filter_action === 'EXCHANGE') echo 'selected'; ?>>EXCHANGE (Trade-In)</option>
                <option value="STOCK_UPDATE" <?php if ($filter_action === 'STOCK_UPDATE') echo 'selected'; ?>>STOCK UPDATE</option>
            </select>

            <button type="submit" class="btn">Filter Audit Trail</button>
            <a href="audit_log.php" class="btn" style="background:#64748b; text-decoration:none; text-align:center;">Reset</a>
        </form>

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
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                            $log_id = $row['Log_id'] ?? $row['log_id'];
                            $action = $row['Action_type'] ?? 'GENERAL';
                            $ref_id = $row['Reference_id'] ?? '-';
                            $emp_name = $row['Employee_Name'] ?? ('Emp #' . ($row['Employee_id'] ?? 'N/A'));

                            $badge_class = 'badge-inventory';
                            if ($action === 'SALE') $badge_class = 'badge-sale';
                            if ($action === 'PURCHASE') $badge_class = 'badge-purchase';
                            if ($action === 'EXCHANGE') $badge_class = 'badge-exchange';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $log_id; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($emp_name); ?></strong>
                                <br><small style="color:#64748b;">ID: #<?php echo $row['Employee_id']; ?></small>
                            </td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($action); ?></span></td>
                            <td><strong><?php echo $ref_id !== 'NULL' ? '#' . htmlspecialchars($ref_id) : '-'; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['Details']); ?></td>
                            <td style="color:#64748b; font-size:13px;"><?php echo $row['Timestamp']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:#64748b;">
                            No audit trail records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>