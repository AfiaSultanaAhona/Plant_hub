<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Business Analytics";
include("../header.php");

$sales_summary = mysqli_query($conn, "SELECT COUNT(*) as total_sales, SUM(Total_amount) as total_revenue FROM Sales_Transaction");
$summary_data = mysqli_fetch_assoc($sales_summary);

$low_stock = mysqli_query($conn, "SELECT * FROM Plant WHERE Stock_quantity < 5");

$top_customers = mysqli_query($conn, "
    SELECT Customer.Customer_name, COUNT(Sales_Transaction.Txn_ID) as total_orders, SUM(Sales_Transaction.Total_amount) as total_spent 
    FROM Sales_Transaction 
    JOIN Customer ON Sales_Transaction.Customer_ID = Customer.Customer_ID 
    GROUP BY Sales_Transaction.Customer_ID 
    ORDER BY total_spent DESC LIMIT 5
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Store Performance & Reports</h2>
        <p class="text-muted mb-0">Financial summary and low stock monitoring</p>
    </div>
    <a href="../home.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<!-- Key Performance Indicators (KPI Cards) -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-success text-white p-3">
            <div class="d-flex align-items-center">
                <div class="fs-1 me-3"><i class="bi bi-currency-dollar"></i></div>
                <div>
                    <h6 class="mb-0 text-white-50 text-uppercase fw-bold">Total Gross Revenue</h6>
                    <h3 class="fw-bold m-0">৳<?php echo number_format($summary_data['total_revenue'] ?? 0, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-primary text-white p-3">
            <div class="d-flex align-items-center">
                <div class="fs-1 me-3"><i class="bi bi-receipt"></i></div>
                <div>
                    <h6 class="mb-0 text-white-50 text-uppercase fw-bold">Total Transactions Completed</h6>
                    <h3 class="fw-bold m-0"><?php echo $summary_data['total_sales'] ?? 0; ?> Orders</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Low Stock Alert Box -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-danger text-white fw-bold py-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Low Stock Inventory Alerts (< 5)
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plant</th>
                            <th>Stock Left</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($low_stock) > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($low_stock)) { ?>
                                <tr class="table-danger">
                                    <td><strong><?php echo $row['Plant_name']; ?></strong></td>
                                    <td><span class="badge bg-danger"><?php echo $row['Stock_quantity']; ?> units</span></td>
                                    <td>৳<?php echo $row['Unit_price']; ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="3" class="text-center py-3 text-muted">All inventory levels healthy!</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Spending Customers Box -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white fw-bold py-3">
                <i class="bi bi-trophy-fill me-2"></i>Top Purchasing Customers
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Customer Name</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($top_customers) > 0) { ?>
                            <?php while ($c = mysqli_fetch_assoc($top_customers)) { ?>
                                <tr>
                                    <td><strong><?php echo $c['Customer_name']; ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo $c['total_orders']; ?></span></td>
                                    <td><span class="badge bg-success">৳<?php echo number_format($c['total_spent'], 2); ?></span></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="3" class="text-center py-3 text-muted">No sales records available.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>