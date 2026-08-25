<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Plant Exchanges";
include("../header.php");

$sql = "SELECT Plant_Exchange.*, Customer.Customer_name, Plant.Plant_name 
        FROM Plant_Exchange 
        LEFT JOIN Customer ON Plant_Exchange.Customer_ID = Customer.Customer_ID 
        LEFT JOIN Plant ON Plant_Exchange.Plant_ID = Plant.Plant_ID";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Returns & Exchanges</h2>
        <p class="text-muted mb-0">Track returned items and replacements</p>
    </div>
    <div>
        <a href="../home.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <a href="add_exchange.php" class="btn btn-success"><i class="bi bi-arrow-return-left me-1"></i> Record Return</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Exchange ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Plant Returned</th>
                        <th>Qty</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong>#<?php echo $row['Exchange_ID']; ?></strong></td>
                                <td><?php echo $row['Exchange_date']; ?></td>
                                <td><?php echo $row['Customer_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['Plant_name'] ?? 'N/A'; ?></td>
                                <td><span class="badge bg-warning text-dark"><?php echo $row['Quantity']; ?> units</span></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $row['Reason']; ?></span></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No exchange records found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>