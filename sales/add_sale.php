<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Record Sale";
include("../header.php");

$customers = mysqli_query($conn, "SELECT * FROM Customer");
$employees = mysqli_query($conn, "SELECT * FROM Employee");
$plants = mysqli_query($conn, "SELECT * FROM Plant WHERE Stock_quantity > 0");
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="bi bi-cart-plus me-2"></i>Record New Sale Transaction</h5>
            </div>
            <div class="card-body p-4">
                <form action="insert_sale.php" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Transaction ID</label>
                        <input type="number" name="txn_id" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php while ($c = mysqli_fetch_assoc($customers)) { ?>
                                <option value="<?php echo $c['Customer_ID']; ?>"><?php echo $c['Customer_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Plant Item</label>
                        <select name="plant_id" class="form-select" required>
                            <option value="">Select Plant</option>
                            <?php while ($p = mysqli_fetch_assoc($plants)) { ?>
                                <option value="<?php echo $p['Plant_ID']; ?>">
                                    <?php echo $p['Plant_name']; ?> (Stock: <?php echo $p['Stock_quantity']; ?>) - ৳<?php echo $p['Unit_price']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Sale Date</label>
                            <input type="date" name="txn_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sales Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php while ($e = mysqli_fetch_assoc($employees)) { ?>
                                <option value="<?php echo $e['Employee_ID']; ?>"><?php echo $e['Employee_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="show_sales.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i> Complete Sale</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>