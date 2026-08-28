<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../DBconnect.php");
mysqli_report(MYSQLI_REPORT_OFF);

// Verify employee is logged in
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$emp_name = $_SESSION['user_name'] ?? 'Staff';
$emp_id = $_SESSION['employee_id'] ?? null;

$suppliers = mysqli_query($conn, "SELECT * FROM Supplier ORDER BY Supplier_name");
$plants = mysqli_query($conn, "SELECT * FROM Plant ORDER BY Plant_name");

// Success/error message from redirect
$msg = $_GET['msg'] ?? '';
$msg_type = $_GET['type'] ?? 'success';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Supplier Purchase - Plant Hub</title>
    <style>
        body { background-color: #ebf5f0; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 700px; margin: 25px auto; padding: 0 20px; }

        .page-header {
            background: linear-gradient(135deg, #064e3b 0%, #0d7b5f 100%);
            color: white; padding: 28px 32px; border-radius: 16px; margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .page-header h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; }
        .page-header p { margin: 0; opacity: 0.85; font-size: 13px; }

        .back-link {
            color: white; text-decoration: none; background: rgba(255,255,255,0.15);
            padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px;
        }
        .back-link:hover { background: rgba(255,255,255,0.25); }

        .card {
            background: white; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .card-title-bar {
            background: #10b981; color: white; padding: 16px 25px;
            font-size: 16px; font-weight: 800;
        }
        .card-body { padding: 30px; }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-weight: 700; font-size: 14px; color: #1f2937;
            margin-bottom: 6px;
        }
        .form-group label .required { color: #ef4444; }
        .form-control {
            width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px;
            font-size: 14px; font-family: 'Segoe UI', sans-serif; box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: #10b981; }
        select.form-control { cursor: pointer; }

        .row { display: flex; gap: 20px; }
        .row .col { flex: 1; }

        .employee-badge {
            background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 16px;
            border-radius: 10px; display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px;
        }
        .employee-badge .icon { font-size: 20px; }
        .employee-badge .info { font-size: 13px; color: #374151; }
        .employee-badge .info strong { color: #064e3b; }

        .total-display {
            background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px;
            padding: 16px 20px; text-align: right; margin-top: 20px;
        }
        .total-label { font-size: 14px; color: #6b7280; font-weight: 600; }
        .total-amount { font-size: 28px; font-weight: 800; color: #10b981; margin-top: 4px; }

        .btn-row { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; }
        .btn-submit {
            background: #10b981; color: white; border: none; padding: 14px 32px;
            border-radius: 10px; font-size: 16px; font-weight: 800; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-submit:hover { background: #059669; transform: translateY(-1px); }
        .btn-back {
            color: #6b7280; text-decoration: none; font-weight: 700; font-size: 14px;
        }
        .btn-back:hover { color: #374151; }

        .alert { padding: 14px 20px; border-radius: 10px; font-weight: 600; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<?php include("../header.php"); ?>

<div class="container">
    <div class="page-header">
        <div>
            <h2>📦 Record Supplier Purchase</h2>
            <p>Buy plants from suppliers and restock inventory</p>
        </div>
        <a href="show_purchase.php" class="back-link">← Purchase History</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?php echo $msg_type === 'error' ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title-bar">🌿 New Supplier Purchase Order</div>
        <div class="card-body">

            <div class="employee-badge">
                <span class="icon">👤</span>
                <div class="info">
                    Processing as: <strong><?php echo htmlspecialchars($emp_name); ?></strong>
                    <?php if ($emp_id): ?>
                        (Employee #<?php echo $emp_id; ?>)
                    <?php endif; ?>
                </div>
            </div>

            <form action="insert_purchase.php" method="post" id="purchaseForm">

                <div class="form-group">
                    <label>Supplier <span class="required">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">— Select Supplier —</option>
                        <?php while ($s = mysqli_fetch_assoc($suppliers)) { ?>
                            <option value="<?php echo $s['Supplier_ID']; ?>">
                                <?php echo htmlspecialchars($s['Supplier_name']); ?>
                                <?php if ($s['Phone']) echo ' — ' . $s['Phone']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Plant Item <span class="required">*</span></label>
                    <select name="plant_id" class="form-control" id="plantSelect" required onchange="updateTotal()">
                        <option value="" data-price="0">— Select Plant —</option>
                        <?php while ($p = mysqli_fetch_assoc($plants)) { ?>
                            <option value="<?php echo $p['Plant_ID']; ?>" data-price="<?php echo $p['Unit_price']; ?>" data-stock="<?php echo $p['Stock_quantity']; ?>">
                                <?php echo htmlspecialchars($p['Plant_name']); ?> — Current Stock: <?php echo $p['Stock_quantity']; ?> units — ৳<?php echo number_format($p['Unit_price'], 2); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Quantity to Purchase <span class="required">*</span></label>
                            <input type="number" name="quantity" class="form-control" id="qtyInput" min="1" value="1" required oninput="updateTotal()">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Unit Cost (৳) <span class="required">*</span></label>
                            <input type="number" step="0.01" name="unit_cost" class="form-control" id="costInput" min="0.01" required oninput="updateTotal()">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purchase Date <span class="required">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="total-display">
                    <div class="total-label">Estimated Total Cost</div>
                    <div class="total-amount" id="totalDisplay">৳0.00</div>
                </div>

                <div class="btn-row">
                    <a href="show_purchase.php" class="btn-back">← Cancel</a>
                    <button type="submit" class="btn-submit">✅ Complete Purchase & Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateTotal() {
    var qty = parseInt(document.getElementById('qtyInput').value) || 0;
    var cost = parseFloat(document.getElementById('costInput').value) || 0;
    var total = qty * cost;
    document.getElementById('totalDisplay').textContent = '৳' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

</body>
</html>