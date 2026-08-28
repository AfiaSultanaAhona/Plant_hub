<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Manage Plants";
include("../header.php");

$sql = "SELECT Plant.*, Category.Category_name 
        FROM Plant 
        LEFT JOIN Category ON Plant.Category_ID = Category.Category_ID";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Plant Inventory</h2>
        <p class="text-muted mb-0">View stock levels and pricing</p>
    </div>
    <div>
        <a href="../home.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <a href="add_plant.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Add New Plant</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>ID</th>
                        <th>Plant Name</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Stock Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong>#<?php echo $row['Plant_ID']; ?></strong></td>
                                <td><?php echo $row['Plant_name']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $row['Category_name'] ?? 'Uncategorized'; ?></span></td>
                                <td>৳<?php echo number_format($row['Unit_price'], 2); ?></td>
                                <td><?php echo $row['Stock_quantity']; ?> units</td>
                                <td>
                                    <?php if ($row['Stock_quantity'] < 5) { ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Low Stock</span>
                                    <?php } else { ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No plants registered yet.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>