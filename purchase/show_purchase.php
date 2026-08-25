<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Purchase History";
include("../header.php");

$sql = "SELECT Purchase_Transaction.*, Supplier.Supplier_name, Plant.Plant_name 
        FROM Purchase_Transaction 
        LEFT JOIN Supplier ON Purchase_Transaction.Supplier_ID = Supplier.Supplier_ID 
        LEFT JOIN Plant ON Purchase_Transaction.Plant_ID = Plant.Plant_ID";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Restock & Purchase Orders</h2>
        <p class="text-muted mb-0">Record of plants bought from suppliers</p>
    </div>
    <div>
        <a href="../home.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <a href="add_purchase.php" class="btn btn-success"><i class="bi bi-bag-plus me-1"></i> Record Purchase</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Purchase ID</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Plant Item</th>
                        <th>Qty Received</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong>#<?php echo $row['Purchase_ID']; ?></strong></td>
                                <td><?php echo $row['Purchase_date']; ?></td>
                                <td><?php echo $row['Supplier_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['Plant_name'] ?? 'N/A'; ?></td>
                                <td><span class="badge bg-primary"><?php echo $row['Quantity'] ?? 1; ?> units</span></td>
                                <td><span class="badge bg-success fs-6">৳<?php echo number_format($row['Total_amount'], 2); ?></span></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No purchases recorded yet.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>