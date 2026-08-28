<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");
mysqli_report(MYSQLI_REPORT_OFF);

$emp_name = $_SESSION['user_name'] ?? 'Staff';

// Filter by action type
$filter = $_GET['filter'] ?? 'ALL';
$filter_esc = mysqli_real_escape_string($conn, $filter);

// Fetch audit logs with employee names
$where_clause = ($filter !== 'ALL') ? "WHERE a.action_type = '$filter_esc'" : "";
$logs_sql = "SELECT a.*, e.Employee_name 
             FROM audit_logs a 
             LEFT JOIN employee e ON a.employee_id = e.Employee_ID 
             $where_clause
             ORDER BY a.created_at DESC 
             LIMIT 200";
$logs_res = mysqli_query($conn, $logs_sql);

// Fetch summary counts
$total_logs = 0;
$sale_count = 0;
$purchase_count = 0;
$exchange_count = 0;

$cnt_res = mysqli_query($conn, "SELECT action_type, COUNT(*) as cnt FROM audit_logs GROUP BY action_type");
if ($cnt_res) {
    while ($cr = mysqli_fetch_assoc($cnt_res)) {
        $total_logs += (int)$cr['cnt'];
        if ($cr['action_type'] === 'SALE') $sale_count = (int)$cr['cnt'];
        if ($cr['action_type'] === 'PURCHASE') $purchase_count = (int)$cr['cnt'];
        if ($cr['action_type'] === 'EXCHANGE') $exchange_count = (int)$cr['cnt'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1100px; margin: 25px auto; padding: 0 20px; }

        .page-header { 
            background: linear-gradient(135deg, #064e3b 0%, #0d7b5f 100%); 
            color: white; padding: 30px 35px; border-radius: 16px; 
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;
        }
        .page-header h2 { margin: 0 0 6px; font-size: 24px; font-weight: 800; }
        .page-header p { margin: 0; opacity: 0.85; font-size: 14px; }

        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 25px; }
        .stat-box { 
            background: white; padding: 20px; border-radius: 12px; text-align: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-left: 4px solid #10b981;
        }
        .stat-box.sale { border-left-color: #3b82f6; }
        .stat-box.purchase { border-left-color: #f59e0b; }
        .stat-box.exchange { border-left-color: #8b5cf6; }
        .stat-box h3 { margin: 0; font-size: 28px; font-weight: 800; color: #064e3b; }
        .stat-box p { margin: 5px 0 0; font-size: 13px; color: #6b7280; font-weight: 600; }

        .filter-bar { 
            display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 18px; border-radius: 20px; border: 2px solid #e5e7eb; 
            background: white; font-weight: 700; font-size: 13px; cursor: pointer;
            color: #4b5563; text-decoration: none; transition: all 0.2s;
        }
        .filter-btn:hover { border-color: #10b981; color: #10b981; }
        .filter-btn.active { background: #10b981; color: white; border-color: #10b981; }

        .card { 
            background: white; border-radius: 14px; padding: 0; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .card-header-bar {
            padding: 18px 25px; border-bottom: 2px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-header-bar h3 { margin: 0; font-size: 18px; font-weight: 800; color: #0a2318; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 20px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; background: #f9fafb; }
        td { padding: 14px 20px; border-bottom: 1px solid #f3f4f6; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: #f0fdf4; }

        .badge { 
            padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 800; 
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        .badge-sale { background: #dbeafe; color: #1d4ed8; }
        .badge-purchase { background: #fef3c7; color: #d97706; }
        .badge-exchange { background: #ede9fe; color: #7c3aed; }
        .badge-other { background: #f3f4f6; color: #4b5563; }

        .emp-name { font-weight: 700; color: #1f2937; }
        .ref-id { font-weight: 700; color: #10b981; }
        .timestamp { color: #9ca3af; font-size: 13px; }
        .desc-text { color: #374151; max-width: 350px; }
        .empty-state { text-align: center; padding: 50px 20px; color: #9ca3af; }
        .empty-state p { font-size: 16px; font-weight: 600; }

        .back-link { 
            color: white; text-decoration: none; background: rgba(255,255,255,0.15); 
            padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px;
        }
        .back-link:hover { background: rgba(255,255,255,0.25); }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="container">
    <div class="page-header">
        <div>
            <h2>🔍 Employee Audit Trail</h2>
            <p>Complete activity log for all employee transactions and operations</p>
        </div>
        <a href="employee_dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <h3><?php echo $total_logs; ?></h3>
            <p>Total Actions Logged</p>
        </div>
        <div class="stat-box sale">
            <h3><?php echo $sale_count; ?></h3>
            <p>Sales Recorded</p>
        </div>
        <div class="stat-box purchase">
            <h3><?php echo $purchase_count; ?></h3>
            <p>Purchases Logged</p>
        </div>
        <div class="stat-box exchange">
            <h3><?php echo $exchange_count; ?></h3>
            <p>Exchanges Processed</p>
        </div>
    </div>

    <div class="filter-bar">
        <span style="font-weight: 700; color: #374151; font-size: 14px;">Filter:</span>
        <a href="audit_log.php" class="filter-btn <?php echo $filter === 'ALL' ? 'active' : ''; ?>">All Actions</a>
        <a href="audit_log.php?filter=SALE" class="filter-btn <?php echo $filter === 'SALE' ? 'active' : ''; ?>">🛒 Sales</a>
        <a href="audit_log.php?filter=PURCHASE" class="filter-btn <?php echo $filter === 'PURCHASE' ? 'active' : ''; ?>">📦 Purchases</a>
        <a href="audit_log.php?filter=EXCHANGE" class="filter-btn <?php echo $filter === 'EXCHANGE' ? 'active' : ''; ?>">🔄 Exchanges</a>
    </div>

    <div class="card">
        <div class="card-header-bar">
            <h3>Activity Log <?php echo $filter !== 'ALL' ? "— $filter" : ''; ?></h3>
            <span style="color: #9ca3af; font-size: 13px;">Showing latest 200 entries</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Employee</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Ref #</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs_res && mysqli_num_rows($logs_res) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs_res)): 
                        $badge_class = 'badge-other';
                        if ($log['action_type'] === 'SALE') $badge_class = 'badge-sale';
                        elseif ($log['action_type'] === 'PURCHASE') $badge_class = 'badge-purchase';
                        elseif ($log['action_type'] === 'EXCHANGE') $badge_class = 'badge-exchange';
                    ?>
                        <tr>
                            <td style="font-weight: 700; color: #6b7280;">#<?php echo $log['log_id']; ?></td>
                            <td><span class="emp-name"><?php echo htmlspecialchars($log['Employee_name'] ?? 'Employee #' . $log['employee_id']); ?></span></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($log['action_type']); ?></span></td>
                            <td><span class="desc-text"><?php echo htmlspecialchars($log['description'] ?? ''); ?></span></td>
                            <td>
                                <?php if ($log['reference_id']): ?>
                                    <span class="ref-id">#<?php echo $log['reference_id']; ?></span>
                                <?php else: ?>
                                    <span style="color: #d1d5db;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="timestamp"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <p>📋 No audit log entries found.</p>
                                <span style="font-size: 13px;">Employee actions will appear here as sales, purchases, and exchanges are processed.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>
