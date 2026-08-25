<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Sales History";
include("../header.php");

$sql = "SELECT Sales_Transaction.*, Customer.Customer_name, Employee.Employee_name 
        FROM Sales_Transaction 
        LEFT JOIN Customer ON Sales_Transaction.Customer_ID = Customer.Customer_ID 
        LEFT JOIN Employee ON Sales_Transaction.Employee_ID = Employee.Employee_ID";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Sales Transactions</h2>
        <p class="text-muted mb-0">View all customer orders and billing</p>
    </div>
    <div>
        <a href="../home.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <a href="add_sale.php" class="btn btn-success"><i class="bi bi-cart-plus me-1"></i> Record New Sale</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Txn ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Employee</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong>#<?php echo $row['Txn_ID']; ?></strong></td>
                                <td><?php echo $row['Txn_date']; ?></td>
                                <td><?php echo $row['Customer_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['Employee_name'] ?? 'N/A'; ?></td>
                                <td><span class="badge bg-success fs-6">৳<?php echo number_format($row['Total_amount'], 2); ?></span></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No sales transactions recorded yet.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>