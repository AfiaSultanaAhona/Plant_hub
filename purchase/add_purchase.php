<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Record Purchase";
include("../header.php");

$suppliers = mysqli_query($conn, "SELECT * FROM Supplier");
$plants = mysqli_query($conn, "SELECT * FROM Plant");
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="bi bi-bag-plus me-2"></i>Record Restock Purchase</h5>
            </div>
            <div class="card-body p-4">
                <form action="insert_purchase.php" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Purchase ID</label>
                        <input type="number" name="purchase_id" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            <?php while ($s = mysqli_fetch_assoc($suppliers)) { ?>
                                <option value="<?php echo $s['Supplier_ID']; ?>"><?php echo $s['Supplier_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Plant Item</label>
                        <select name="plant_id" class="form-select" required>
                            <option value="">Select Plant</option>
                            <?php while ($p = mysqli_fetch_assoc($plants)) { ?>
                                <option value="<?php echo $p['Plant_ID']; ?>"><?php echo $p['Plant_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Quantity Received</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Unit Cost (৳)</label>
                            <input type="number" step="0.01" name="unit_cost" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="show_purchase.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i> Save Purchase</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>