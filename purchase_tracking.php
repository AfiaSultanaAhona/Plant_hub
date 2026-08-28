<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "DBconnect.php";

$raw_employee = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
$employee_id = (int)preg_replace('/[^0-9]/', '', (string)$raw_employee);
if ($employee_id <= 0) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["make_purchase"])) {
    $supplier_id = (int)($_POST["supplier_id"] ?? 0);
    $plant_id = (int)($_POST["plant_id"] ?? -1);
    $quantity = (int)($_POST["quantity"] ?? 0);
    $unit_cost = (float)($_POST["unit_cost"] ?? 0);
    $purchase_date = $_POST["purchase_date"] ?? date("Y-m-d");

    if ($supplier_id <= 0 || $plant_id < 0 || $quantity <= 0 || $unit_cost <= 0) {
        $error = "Please enter valid supplier, plant, quantity and purchase price.";
    } else {
        $date_esc = mysqli_real_escape_string($conn, $purchase_date);
        $supplier_check = mysqli_query($conn, "SELECT Supplier_ID FROM supplier WHERE Supplier_ID=$supplier_id LIMIT 1");
        $plant_check = mysqli_query($conn, "SELECT Plant_ID, Plant_name, Stock_quantity FROM plant WHERE Plant_ID=$plant_id LIMIT 1");

        if (!$supplier_check || mysqli_num_rows($supplier_check) === 0) {
            $error = "Selected supplier does not exist.";
        } elseif (!$plant_check || mysqli_num_rows($plant_check) === 0) {
            $error = "Selected plant does not exist.";
        } else {
            $total = round($quantity * $unit_cost, 2);

            mysqli_begin_transaction($conn);
            try {
                $id_result = mysqli_query($conn, "SELECT COALESCE(MAX(Purchase_ID),0)+1 AS next_id FROM purchase FOR UPDATE");
                if (!$id_result) throw new Exception(mysqli_error($conn));
                $purchase_id = (int)mysqli_fetch_assoc($id_result)["next_id"];

                if (!mysqli_query($conn, "INSERT INTO purchase (Purchase_ID, Purchase_date, Supplier_ID, Employee_ID)
                    VALUES ($purchase_id, '$date_esc', $supplier_id, $employee_id)"))
                    throw new Exception(mysqli_error($conn));

                if (!mysqli_query($conn, "INSERT INTO purchase_contains_plant
                    (Purchase_ID, Plant_ID, Quantity, Purchase_unit_price)
                    VALUES ($purchase_id, $plant_id, $quantity, $unit_cost)"))
                    throw new Exception(mysqli_error($conn));

                if (!mysqli_query($conn, "INSERT INTO purchase_transaction
                    (Purchase_ID, Purchase_date, Total_amount, Supplier_ID, Plant_ID, Quantity, Employee_id)
                    VALUES ($purchase_id, '$date_esc', $total, $supplier_id, $plant_id, $quantity, $employee_id)"))
                    throw new Exception(mysqli_error($conn));

                if (!mysqli_query($conn, "UPDATE plant SET Stock_quantity = Stock_quantity + $quantity WHERE Plant_ID=$plant_id")
                    || mysqli_affected_rows($conn) !== 1)
                    throw new Exception("Inventory stock could not be updated.");

                mysqli_commit($conn);

                if (function_exists("logEmployeeAction")) {
                    logEmployeeAction($conn, "PURCHASE",
                        "Purchase #$purchase_id: supplier #$supplier_id, plant #$plant_id, quantity $quantity, unit cost ৳" . number_format($unit_cost, 2),
                        $purchase_id, $employee_id);
                }

                $message = "Purchase #$purchase_id completed. $quantity unit(s) added to inventory.";
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = "Purchase failed: " . $e->getMessage();
            }
        }
    }
}

$suppliers = mysqli_query($conn, "SELECT Supplier_ID, Supplier_name FROM supplier ORDER BY Supplier_name ASC");
$plants = mysqli_query($conn, "SELECT Plant_ID, Plant_name, Unit_price, Stock_quantity FROM plant ORDER BY Plant_name ASC");
$history = mysqli_query($conn, "
    SELECT pu.Purchase_ID, pu.Purchase_date, pu.Employee_ID,
           s.Supplier_name, p.Plant_name,
           pci.Quantity, pci.Purchase_unit_price,
           (pci.Quantity * pci.Purchase_unit_price) AS Total
    FROM purchase pu
    LEFT JOIN supplier s ON s.Supplier_ID=pu.Supplier_ID
    LEFT JOIN purchase_contains_plant pci ON pci.Purchase_ID=pu.Purchase_ID
    LEFT JOIN plant p ON p.Plant_ID=pci.Plant_ID
    ORDER BY pu.Purchase_ID DESC LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Tracking - Plant Hub</title>
<style>
body{font-family:Segoe UI,sans-serif;background:#f0fdf4;margin:0;color:#1e293b}.container{max-width:1100px;margin:30px auto;padding:0 20px}.card{background:#fff;padding:25px;border-radius:14px;margin-bottom:25px;border:1px solid #e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.04)}h2{color:#065f46;margin-top:0}.back{display:inline-block;margin-bottom:20px;color:#0284c7;text-decoration:none;font-weight:700}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}label{font-weight:700;font-size:14px;display:block;margin-bottom:6px}input,select{width:100%;box-sizing:border-box;padding:11px;border:1px solid #cbd5e1;border-radius:7px}button{background:#10b981;color:#fff;border:0;padding:12px 18px;border-radius:7px;font-weight:700;cursor:pointer}.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:15px}table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f8fafc}@media(max-width:700px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head>
<body>
<?php if(file_exists("header.php")) include "header.php"; ?>
<div class="container">
<a class="back" href="employee_dashboard.php">← Back to Employee Dashboard</a>
<div class="card">
<h2>📦 Purchase Tracking</h2><p>Buy plants from suppliers and automatically increase inventory stock.</p>
<?php if($message): ?><div class="success"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post"><div class="grid">
<div><label>Supplier</label><select name="supplier_id" required><option value="">Select supplier</option>
<?php while($s=mysqli_fetch_assoc($suppliers)): ?><option value="<?=$s["Supplier_ID"]?>"><?=htmlspecialchars($s["Supplier_name"])?></option><?php endwhile; ?>
</select></div>
<div><label>Plant</label><select name="plant_id" required><option value="">Select plant</option>
<?php while($p=mysqli_fetch_assoc($plants)): ?><option value="<?=$p["Plant_ID"]?>"><?=htmlspecialchars($p["Plant_name"])?> — Stock: <?=$p["Stock_quantity"]?></option><?php endwhile; ?>
</select></div>
<div><label>Quantity</label><input type="number" name="quantity" min="1" required></div>
<div><label>Purchase Unit Price (৳)</label><input type="number" name="unit_cost" min="0.01" step="0.01" required></div>
<div><label>Purchase Date</label><input type="date" name="purchase_date" value="<?=date("Y-m-d")?>" required></div>
<div style="display:flex;align-items:end"><button type="submit" name="make_purchase">📦 Complete Purchase</button></div>
</div></form>
</div>
<div class="card"><h2>📋 Purchase History</h2>
<table><tr><th>ID</th><th>Date</th><th>Supplier</th><th>Plant</th><th>Qty</th><th>Unit Cost</th><th>Total</th><th>Employee</th></tr>
<?php if($history && mysqli_num_rows($history)): while($r=mysqli_fetch_assoc($history)): ?>
<tr><td>#<?=$r["Purchase_ID"]?></td><td><?=htmlspecialchars($r["Purchase_date"])?></td><td><?=htmlspecialchars($r["Supplier_name"]??"Unknown")?></td><td><?=htmlspecialchars($r["Plant_name"]??"Unknown")?></td><td><?=$r["Quantity"]?></td><td>৳<?=number_format((float)$r["Purchase_unit_price"],2)?></td><td><strong>৳<?=number_format((float)$r["Total"],2)?></strong></td><td>E<?=htmlspecialchars($r["Employee_ID"]??"-")?></td></tr>
<?php endwhile; else: ?><tr><td colspan="8" style="text-align:center">No purchase records yet.</td></tr><?php endif; ?>
</table></div></div></body></html>
