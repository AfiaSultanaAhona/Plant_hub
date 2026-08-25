<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Manage Categories";
include("../header.php");

$sql = "SELECT * FROM Category";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Plant Categories</h2>
        <p class="text-muted mb-0">Manage product classifications</p>
    </div>
    <div>
        <a href="../home.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <a href="add_category.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Add Category</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong>#<?php echo $row['Category_ID']; ?></strong></td>
                                <td><?php echo $row['Category_name']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="2" class="text-center py-4 text-muted">No categories recorded yet.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>