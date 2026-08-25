<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Add Customer";
include("../header.php");
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Add New Customer</h5>
            </div>
            <div class="card-body p-4">
                <form action="insert_customer.php" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Customer ID</label>
                        <input type="number" name="customer_id" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="show_customer.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i> Save Customer</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>