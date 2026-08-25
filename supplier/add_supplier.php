<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Add Supplier";
include("../header.php");
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="bi bi-truck me-2"></i>Add New Supplier</h5>
            </div>
            <div class="card-body p-4">
                <form action="insert_supplier.php" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Supplier ID</label>
                        <input type="number" name="supplier_id" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Supplier Company Name</label>
                        <input type="text" name="supplier_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="show_supplier.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i> Save Supplier</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>