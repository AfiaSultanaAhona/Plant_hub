<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

// 1. Ensure services table exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_type VARCHAR(255),
    customer_name VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Assigned',
    assigned_employee VARCHAR(100) DEFAULT 'Ahona'
)");

// 2. Seed default data if table is empty
$check_srv = @mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
if ($check_srv && ($c_row = mysqli_fetch_assoc($check_srv)) && (int)$c_row['total'] === 0) {
    @mysqli_query($conn, "INSERT INTO services (service_type, customer_name, status, assigned_employee) VALUES 
        ('Plant Care Consultation', 'Afia Sultana', 'Assigned', 'Ahona'),
        ('Garden Installation & Setup', 'John Doe', 'In Progress', 'Ahona')");
}

$msg = "";
// 3. Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service_status'])) {
    $sid = (int)$_POST['service_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $up_q = @mysqli_query($conn, "UPDATE services SET status = '$new_status' WHERE service_id = '$sid'");
    if ($up_q) {
        $msg = "✅ Service #SRV-$sid status updated to <strong>$new_status</strong>!";
    }
}

// 4. Fetch Services
$services_res = @mysqli_query($conn, "SELECT * FROM services ORDER BY service_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assigned Services - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1050px; margin: 25px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .card h2 { margin-top: 0; color: #064e3b; font-size: 22px; font-weight: 800; border-bottom: 2px solid #ecfdf5; padding-bottom: 12px; }
        .alert-box { padding: 12px 18px; border-radius: 8px; font-weight: 600; margin-bottom: 18px; background-color: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; color: #4b5563; font-size: 14px; border-bottom: 2px solid #e5e7eb; }
        td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .badge-assigned { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .badge-in-progress { background: #e0f2fe; color: #0284c7; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .badge-completed { background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .btn-action { background: #059669; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="container">
    <div class="card">
        <h2>Assigned Customer Service Tasks 🛠️</h2>

        <?php if (!empty($msg)): ?>
            <div class="alert-box"><?php echo $msg; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Service Type</th>
                    <th>Customer Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($services_res && mysqli_num_rows($services_res) > 0):
                    while ($s_row = mysqli_fetch_assoc($services_res)):
                        $sid = $s_row['service_id'];
                        $stype = $s_row['service_type'];
                        $cname = $s_row['customer_name'];
                        $status = $s_row['status'];

                        $badge_class = 'badge-assigned';
                        if ($status === 'In Progress') $badge_class = 'badge-in-progress';
                        if ($status === 'Completed') $badge_class = 'badge-completed';
                ?>
                    <tr>
                        <td style="font-weight:700;">#SRV-<?php echo $sid; ?></td>
                        <td style="font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($stype); ?></td>
                        <td><?php echo htmlspecialchars($cname); ?></td>
                        <td><span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                        <td>
                            <form method="POST" style="display:inline-flex; gap:6px;">
                                <input type="hidden" name="service_id" value="<?php echo $sid; ?>">
                                <?php if ($status === 'Assigned'): ?>
                                    <input type="hidden" name="new_status" value="In Progress">
                                    <button type="submit" name="update_service_status" class="btn-action" style="background:#0284c7;">Start Service ⏱️</button>
                                <?php elseif ($status === 'In Progress'): ?>
                                    <input type="hidden" name="new_status" value="Completed">
                                    <button type="submit" name="update_service_status" class="btn-action">Mark Completed ✅</button>
                                <?php else: ?>
                                    <span style="color:#059669; font-weight:700; font-size:13px;">Resolved ✓</span>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr><td colspan="5" style="text-align:center; padding:20px;">No assigned services found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>