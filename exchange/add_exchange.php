<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Record Exchange";
include("../header.php");

$customers = mysqli_query($conn, "SELECT * FROM Customer");
$plants = mysqli_query($conn, "SELECT * FROM Plant");
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="bi bi-arrow-return-left me-2"></i>Record Plant Return</h5>
            </div>
            <div class="card-body p-4">
                <form action="insert_exchange.php" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Exchange ID</label>
                        <input type="number" name="exchange_id" class="form-control" required>
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
                        <label class="form-label fw-bold">Plant Item Returned</label>
                        <select name="plant_id" class="form-select" required>
                            <option value="">Select Plant</option>
                            <?php while ($p = mysqli_fetch_assoc($plants)) { ?>
                                <option value="<?php echo $p['Plant_ID']; ?>"><?php echo $p['Plant_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Quantity Returned</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Exchange Date</label>
                            <input type="date" name="exchange_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Return</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g., Damaged, Wrong size" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="show_exchange.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i> Submit Return</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>