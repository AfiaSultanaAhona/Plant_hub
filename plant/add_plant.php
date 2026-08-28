<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Add New Plant";
include("../header.php");

$categories = mysqli_query($conn, "SELECT * FROM Category");
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Add New Plant Item</h5>
            </div>
            <div class="card-body p-4">
                <form action="insert_plant.php" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Plant ID</label>
                        <input type="number" name="plant_id" class="form-control" placeholder="e.g. 101" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Plant Name</label>
                        <input type="text" name="plant_name" class="form-control" placeholder="e.g. Aloe Vera" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                                <option value="<?php echo $cat['Category_ID']; ?>">
                                    <?php echo $cat['Category_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Unit Price (৳)</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Initial Stock</label>
                            <input type="number" name="stock_quantity" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="show_plant.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i> Save Plant</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>