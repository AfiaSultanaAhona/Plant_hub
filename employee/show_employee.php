<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$base_path = "../";
$page_title = "Manage Employees";
include("../header.php");

$sql = "SELECT * FROM Employee";
$result = mysqli_query($conn, $sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-success m-0">Employee Roster</h2>
        <p class="text-muted mb-0">Authorized system personnel</p>
    </div>
    <a href="../home.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email / Contact</th>
                        <th>Designation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><strong>#<?php echo $row['Employee_ID'] ?? $row['employee_id']; ?></strong></td>
                                <td><?php echo $row['Employee_name'] ?? $row['employee_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['Email'] ?? $row['email'] ?? 'N/A'; ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo $row['Designation'] ?? 'Staff'; ?></span></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No employees found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>