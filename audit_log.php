<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Resolve logged-in Employee ID
$employee_id = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
$clean_emp_id = (int) preg_replace('/[^0-9]/', '', (string)$employee_id);

// 2. Handle Search & Filtering
$search_emp = mysqli_real_escape_string($conn, $_GET['search_emp'] ?? '');
$filter_action = mysqli_real_escape_string($conn, $_GET['filter_action'] ?? '');

$where = [];
if (!empty($search_emp)) {
    $where[] = "(a.Employee_id LIKE '%$search_emp%' OR e.Name LIKE '%$search_emp%')";
}
if (!empty($filter_action)) {
    $where[] = "a.Action_type = '$filter_action'";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// 3. Fetch Audit Logs joined with Employee info
$query = "SELECT a.*, e.Name AS Employee_Name 
          FROM audit_log a 
          LEFT JOIN employee e ON a.Employee_id = e.Employee_ID 
          $where_sql 
          ORDER BY a.Log_id DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Log & Employee Tracking - Plant Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .navbar { background: #0f172a; padding: 15px 30px; color: white; display: flex; justify-content: space-between; }
        .navbar a { color: #38bdf8; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .filter-grid { display: flex; gap: 10px; margin-bottom: 20px; }
        .form-control { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; flex: 1; }
        .btn { background: #0284c7; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .table th { background: #f1f5f9; color: #334155; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>📋 Audit Trail & Employee Log</h2>
    <div>
        <a href="employee_dashboard.php">Dashboard</a>
        <a href="insert_sale.php">Record Sale</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2 style="margin-top:0; color: #0f172a;">Employee Activity & Transaction Logs</h2>

        <form method="GET" class="filter-grid">
            <input type="text" name="search_emp" class="form-control" placeholder="Search by Employee ID or Name..." value="<?php echo htmlspecialchars($search_emp); ?>">
            <select name="filter_action" class="form-control" style="flex:0.5;">
                <option value="">All Action Types</option>
                <option value="SALE" <?php if ($filter_action === 'SALE') echo 'selected'; ?>>SALE</option>
                <option value="EXCHANGE" <?php if ($filter_action === 'EXCHANGE') echo 'selected'; ?>>EXCHANGE</option>
                <option value="PURCHASE" <?php if ($filter_action === 'PURCHASE') echo 'selected'; ?>>PURCHASE</option>
            </select>
            <button type="submit" class="btn">Filter Logs</button>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>Log #</th>
                    <th>Employee</th>
                    <th>Action Type</th>
                    <th>Details / Notes</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong>#<?php echo $row['Log_id'] ?? $row['log_id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['Employee_Name'] ?? ('Emp #' . ($row['Employee_id'] ?? 'N/A'))); ?></strong>
                            </td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['Action_type'] ?? 'GENERAL'); ?></span></td>
                            <td><?php echo htmlspecialchars($row['Details'] ?? $row['description'] ?? 'No description'); ?></td>
                            <td style="color:#64748b;"><?php echo $row['Timestamp'] ?? $row['created_at'] ?? 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No audit entries recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>