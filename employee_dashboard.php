<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

// ============================================================
// 1. SESSION VALIDATION
// ============================================================
$employee_id = $_SESSION['Employee_id']
    ?? $_SESSION['employee_id']
    ?? $_SESSION['user_id']
    ?? null;

if (!$employee_id) {
    header("Location: login.php");
    exit;
}

$role_label = '👤 Employee';

$emp_username = $_SESSION['username']
    ?? $_SESSION['Employee_name']
    ?? ('emp' . preg_replace('/[^0-9]/', '', (string)$employee_id));


// ============================================================
// 2. HANDLE ADD STOCK ACTION
// ============================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'add_stock'
) {

    $pid = (int)($_POST['plant_id'] ?? 0);
    $add_qty = (int)($_POST['add_quantity'] ?? 0);

    if ($add_qty > 0 && $pid >= 0) {

        $pk_col = 'Plant_ID';
        $stock_col = 'Stock_quantity';

        // Detect actual Plant table column names
        $cols = mysqli_query($conn, "SHOW COLUMNS FROM plant");

        if ($cols) {
            while ($c = mysqli_fetch_assoc($cols)) {

                $field_lower = strtolower($c['Field']);

                if (
                    $field_lower === 'plant_id'
                    || $field_lower === 'id'
                ) {
                    $pk_col = $c['Field'];
                }

                if (
                    $field_lower === 'stock_quantity'
                    || $field_lower === 'stock'
                    || $field_lower === 'quantity'
                ) {
                    $stock_col = $c['Field'];
                }
            }
        }

        // ----------------------------------------------------
        // Update Stock
        // ----------------------------------------------------
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE plant
             SET `$stock_col` = `$stock_col` + ?
             WHERE `$pk_col` = ?"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $add_qty,
                $pid
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // ----------------------------------------------------
        // Audit Log
        // ----------------------------------------------------
        @mysqli_query(
            $conn,
            "INSERT INTO stock_audit
            (Plant_ID, Employee_ID, Action, Quantity, Timestamp)
            VALUES
            ($pid, '$employee_id', 'ADD_STOCK', $add_qty, NOW())"
        );
    }

    header(
        "Location: employee_dashboard.php?view=inventory&msg=added&qty=$add_qty&id=$pid"
    );

    exit;
}


// ============================================================
// 3. HANDLE DELETE PLANT ACTION
// ============================================================
if (
    isset($_GET['action'])
    && $_GET['action'] === 'delete'
    && isset($_GET['id'])
) {

    $del_id = (int)$_GET['id'];

    $pk_col = 'Plant_ID';

    // Detect actual primary key column
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM plant");

    if ($cols) {

        while ($c = mysqli_fetch_assoc($cols)) {

            $field_lower = strtolower($c['Field']);

            if (
                $field_lower === 'plant_id'
                || $field_lower === 'id'
            ) {

                $pk_col = $c['Field'];
                break;
            }
        }
    }

    // Delete plant
    mysqli_query(
        $conn,
        "DELETE FROM plant
         WHERE `$pk_col` = $del_id"
    );

    // Audit log
    @mysqli_query(
        $conn,
        "INSERT INTO stock_audit
        (Plant_ID, Employee_ID, Action, Quantity, Timestamp)
        VALUES
        ($del_id, '$employee_id', 'DELETE', 0, NOW())"
    );

    header(
        "Location: employee_dashboard.php?view=inventory&msg=deleted&id=$del_id"
    );

    exit;
}


// ============================================================
// 4. STATUS MESSAGES
// ============================================================
$msg = "";

if (isset($_GET['msg'])) {

    if ($_GET['msg'] === 'added') {

        $q = (int)($_GET['qty'] ?? 0);
        $i = (int)($_GET['id'] ?? 0);

        $msg = "✅ Added exactly $q stock to Plant #$i.";

    } elseif ($_GET['msg'] === 'deleted') {

        $i = (int)($_GET['id'] ?? 0);

        $msg = "🗑️ Plant #$i deleted successfully.";

    } elseif ($_GET['msg'] === 'added_new') {

        $msg = "🌱 New plant added successfully.";

    } elseif ($_GET['msg'] === 'updated') {

        $msg = "✏️ Plant details updated successfully.";
    }
}


// ============================================================
// 5. VIEW SWITCHER
// ============================================================
$view = $_GET['view'] ?? 'dashboard';


// ============================================================
// 6. DASHBOARD STATISTICS
// ============================================================
$total_plants = 0;
$total_stock = 0;

$count_res = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total_p,
        SUM(Stock_quantity) AS total_s
     FROM plant"
);

if ($count_res && $row = mysqli_fetch_assoc($count_res)) {

    $total_plants = $row['total_p'] ?? 0;
    $total_stock = $row['total_s'] ?? 0;
}


// ============================================================
// 7. INVENTORY DATA
// ============================================================
$plants_query = null;

if ($view === 'inventory') {

    $sql = "
        SELECT
            p.*,
            COALESCE(
                c.Category_name,
                c.category_name,
                c.Name,
                c.name,
                p.Category,
                p.category,
                'General'
            ) AS fetched_category

        FROM plant p

        LEFT JOIN category c
            ON
                p.Category_ID = c.Category_ID
                OR p.category_id = c.category_id
                OR p.Category = c.Category_ID

        ORDER BY 1 ASC
    ";

    $plants_query = mysqli_query($conn, $sql);

    // Fallback query
    if (!$plants_query) {

        $plants_query = mysqli_query(
            $conn,
            "SELECT *
             FROM plant
             ORDER BY 1 ASC"
        );
    }
}


// ============================================================
// 8. AUDIT LOG DATA
// ============================================================
$audit_query = null;

if ($view === 'audit') {

    $audit_tables = [
        'stock_audit',
        'audit',
        'inventory_log',
        'audit_log'
    ];

    foreach ($audit_tables as $table) {

        $check_table = mysqli_query(
            $conn,
            "SHOW TABLES LIKE '$table'"
        );

        if (
            $check_table
            && mysqli_num_rows($check_table) > 0
        ) {

            $audit_query = mysqli_query(
                $conn,
                "SELECT *
                 FROM `$table`
                 ORDER BY 1 DESC
                 LIMIT 50"
            );

            break;
        }
    }
}

?>
<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Employee Dashboard - Plant Hub</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0fdf4;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }

        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            background: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .navbar .logo {
            font-size: 22px;
            font-weight: bold;
            color: #15803d;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #334155;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .nav-links a:hover {
            color: #15803d;
        }

        .nav-links a.active {
            color: #15803d;
            border-bottom: 2px solid #15803d;
            padding-bottom: 4px;
        }

        .user-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .btn-logout {
            background: #fee2e2;
            color: #ef4444 !important;
            padding: 6px 16px;
            border-radius: 20px;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #fecaca;
            color: #dc2626 !important;
        }


        /* =====================================================
           MAIN CONTAINER
        ===================================================== */

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }


        /* =====================================================
           DASHBOARD GRID
        ===================================================== */

        .grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 32px;
            color: #15803d;
        }

        .stat-card p {
            margin: 5px 0 0 0;
            color: #64748b;
            font-weight: 600;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 15px;
        }

        .table th {
            background: #f8fafc;
            padding: 12px;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn-add {
            background: #10b981;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }

        .btn-add:hover {
            background: #059669;
        }

        .btn-cat {
            background: #0284c7;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
            display: inline-block;
        }

        .btn-cat:hover {
            background: #0369a1;
        }

        .btn-edit {
            color: #0284c7;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
        }

        .btn-del {
            background: #ef4444;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
        }

        .btn-del:hover {
            background: #dc2626;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }


        /* =====================================================
           STOCK FORM
        ===================================================== */

        .stock-form {
            display: flex;
            gap: 6px;
        }

        .stock-input {
            width: 60px;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .btn-stock {
            background: #0284c7;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-stock:hover {
            background: #0369a1;
        }


        /* =====================================================
           AUDIT BADGE
        ===================================================== */

        .badge-action {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            background: #e2e8f0;
        }


        /* =====================================================
           FEATURE LINKS
        ===================================================== */

        .feature-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));

            gap: 15px;
            margin-top: 20px;
        }

        .feature-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            text-decoration: none;
            color: #1e293b;
            transition: 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            border-color: #10b981;
            background: #f0fdf4;
        }

        .feature-card h3 {
            margin: 0 0 6px 0;
            color: #15803d;
        }

        .feature-card p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .navbar {
                padding: 15px 20px;
            }

            .nav-links {
                gap: 12px;
            }

            .table {
                font-size: 13px;
            }

            .table th,
            .table td {
                padding: 8px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVIGATION BAR
========================================================= -->

<div class="navbar">

    <a
        href="employee_dashboard.php"
        class="logo"
    >
        🌱 Plant Hub
    </a>


    <div class="nav-links">

        <!-- HOME -->
        <a
            href="employee_dashboard.php"
            class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>"
        >
            Home 🏠
        </a>


        <!-- INVENTORY -->
        <a
            href="employee_dashboard.php?view=inventory"
            class="<?php echo $view === 'inventory' ? 'active' : ''; ?>"
        >
            Inventory 🌿
        </a>


        <!-- AUDIT -->
        <a
            href="employee_dashboard.php?view=audit"
            class="<?php echo $view === 'audit' ? 'active' : ''; ?>"
        >
            Audit Logs 📋
        </a>


        <!-- PURCHASE TRACKING -->
        <a href="purchase_tracking.php">
            Purchases 📦
        </a>


        <!-- SUPPLIER MANAGEMENT -->
        <a href="supplier_management.php">
            Suppliers 🚚
        </a>


        <!-- EXCHANGE -->
        <a href="exchange_management.php">
            Exchanges 🔄
        </a>


        <!-- REPORTS -->
        <a href="reports.php">
            Reports 📊
        </a>


        <!-- EMPLOYEE ROLE -->
        <div class="user-badge">
            <?php echo $role_label; ?>
        </div>


        <!-- LOGOUT -->
        <a
            href="logout.php"
            class="btn-logout"
        >
            Logout
        </a>

    </div>

</div>


<!-- =========================================================
     MAIN CONTAINER
========================================================= -->

<div class="container">


<?php if ($view === 'inventory'): ?>


    <!-- =====================================================
         INVENTORY VIEW
    ====================================================== -->

    <div class="card">

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:15px;
                flex-wrap:wrap;
            "
        >

            <h2>
                🌿 Plant Inventory Management
            </h2>


            <div>

                <a
                    href="manage_categories.php"
                    class="btn-cat"
                >
                    🏷️ Manage Categories
                </a>


                <a
                    href="add_plant.php"
                    class="btn-add"
                    style="margin-right:15px;"
                >
                    ➕ Add Plant
                </a>


                <a
                    href="employee_dashboard.php"
                    style="
                        color:#0284c7;
                        text-decoration:none;
                        font-weight:bold;
                    "
                >
                    ← Back to Dashboard
                </a>

            </div>

        </div>


        <!-- STATUS MESSAGE -->

        <?php if ($msg): ?>

            <div
                class="alert-info"
                style="margin-top:15px;"
            >
                <?php echo htmlspecialchars($msg); ?>
            </div>

        <?php endif; ?>


        <!-- INVENTORY TABLE -->

        <table class="table">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Plant Name</th>

                    <th>Category</th>

                    <th>Price</th>

                    <th>Stock Quantity</th>

                    <th>Add Stock</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                $plants_query
                && mysqli_num_rows($plants_query) > 0
            ): ?>


                <?php while (
                    $p = mysqli_fetch_assoc($plants_query)
                ):

                    $row = array_change_key_case(
                        $p,
                        CASE_LOWER
                    );

                    $pid =
                        $row['plant_id']
                        ?? $row['id']
                        ?? 0;

                    $pname =
                        $row['plant_name']
                        ?? $row['name']
                        ?? '-';

                    $pcat =
                        $row['fetched_category']
                        ?? $row['category']
                        ?? $row['category_name']
                        ?? 'Indoor';

                    if (is_numeric($pcat)) {
                        $pcat = "Category #" . $pcat;
                    }

                    $pprice =
                        (float)(
                            $row['price']
                            ?? $row['plant_price']
                            ?? $row['unit_price']
                            ?? 0
                        );

                    $pstock =
                        $row['stock_quantity']
                        ?? $row['stock']
                        ?? 0;

                ?>


                    <tr>


                        <!-- ID -->

                        <td>
                            #<?php echo $pid; ?>
                        </td>


                        <!-- NAME -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars($pname);
                                ?>
                            </strong>

                        </td>


                        <!-- CATEGORY -->

                        <td>

                            <span
                                style="
                                    background:#e2e8f0;
                                    padding:3px 8px;
                                    border-radius:4px;
                                    font-size:12px;
                                    font-weight:600;
                                "
                            >
                                <?php
                                echo htmlspecialchars($pcat);
                                ?>
                            </span>

                        </td>


                        <!-- PRICE -->

                        <td>

                            ৳<?php
                            echo number_format(
                                $pprice,
                                2
                            );
                            ?>

                        </td>


                        <!-- STOCK -->

                        <td>

                            <strong>
                                <?php
                                echo $pstock;
                                ?>
                            </strong>

                        </td>


                        <!-- ADD STOCK -->

                        <td>

                            <form
                                method="POST"
                                action="employee_dashboard.php?view=inventory"
                                class="stock-form"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="add_stock"
                                >


                                <input
                                    type="hidden"
                                    name="plant_id"
                                    value="<?php echo $pid; ?>"
                                >


                                <input
                                    type="number"
                                    name="add_quantity"
                                    class="stock-input"
                                    min="1"
                                    placeholder="Qty"
                                    required
                                >


                                <button
                                    type="submit"
                                    class="btn-stock"
                                >
                                    + Add
                                </button>

                            </form>

                        </td>


                        <!-- ACTIONS -->

                        <td>

                            <a
                                href="edit_plant.php?id=<?php echo $pid; ?>"
                                class="btn-edit"
                            >
                                Edit
                            </a>


                            <a
                                href="employee_dashboard.php?view=inventory&action=delete&id=<?php echo $pid; ?>"
                                class="btn-del"
                                onclick="
                                    return confirm(
                                        'Delete plant?'
                                    )
                                "
                            >
                                Delete
                            </a>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="7"
                        style="text-align:center;"
                    >
                        No plant records found.
                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>


<?php elseif ($view === 'audit'): ?>


    <!-- =====================================================
         AUDIT VIEW
    ====================================================== -->

    <div class="card">

        <h2>
            📋 Stock Audit Logs
        </h2>

        <p style="color:#64748b;">
            Recent inventory operations and system activity trail.
        </p>


        <table class="table">

            <thead>

                <tr>

                    <th>Log ID / Time</th>

                    <th>Plant ID</th>

                    <th>Employee</th>

                    <th>Action</th>

                    <th>Quantity Change</th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                $audit_query
                && mysqli_num_rows($audit_query) > 0
            ): ?>


                <?php while (
                    $a = mysqli_fetch_assoc($audit_query)
                ): ?>


                    <tr>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $a['Timestamp']
                                ?? $a['timestamp']
                                ?? $a['date']
                                ?? 'N/A'
                            );

                            ?>

                        </td>


                        <td>

                            #

                            <?php

                            echo htmlspecialchars(
                                $a['Plant_ID']
                                ?? $a['plant_id']
                                ?? '-'
                            );

                            ?>

                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $a['Employee_ID']
                                ?? $a['employee_id']
                                ?? $emp_username
                            );

                            ?>

                        </td>


                        <td>

                            <span class="badge-action">

                                <?php

                                echo htmlspecialchars(
                                    $a['Action']
                                    ?? $a['action']
                                    ?? 'UPDATE'
                                );

                                ?>

                            </span>

                        </td>


                        <td>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $a['Quantity']
                                    ?? $a['quantity']
                                    ?? $a['qty']
                                    ?? 0
                                );

                                ?>

                            </strong>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="5"
                        style="
                            text-align:center;
                            color:#64748b;
                        "
                    >
                        No audit records found.
                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>


<?php else: ?>


    <!-- =====================================================
         HOME DASHBOARD
    ====================================================== -->

    <div class="card">

        <h2>

            Welcome,
            <?php
            echo htmlspecialchars($emp_username);
            ?>
            👋

        </h2>


        <p style="color:#64748b;">

            Manage plant inventory, purchases,
            suppliers, exchanges, reports, and
            system records from your dashboard.

        </p>

    </div>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="grid">


        <!-- TOTAL PLANT TYPES -->

        <div class="stat-card">

            <h3>
                <?php echo $total_plants; ?>
            </h3>

            <p>
                Total Plant Types
            </p>

        </div>


        <!-- TOTAL STOCK -->

        <div class="stat-card">

            <h3>
                <?php echo $total_stock; ?>
            </h3>

            <p>
                Total Stock Items
            </p>

        </div>


        <!-- INVENTORY -->

        <div
            class="stat-card"
            style="
                display:flex;
                flex-direction:column;
                justify-content:center;
                align-items:center;
            "
        >

            <a
                href="employee_dashboard.php?view=inventory"
                class="btn-add"
                style="
                    width:80%;
                    text-align:center;
                "
            >
                🌿 Manage Inventory
            </a>

        </div>

    </div>


    <!-- =====================================================
         FEATURE SHORTCUTS
    ====================================================== -->

    <div class="card">

        <h2>
            ⚡ Employee Features
        </h2>

        <p style="color:#64748b;">
            Access the main employee functions of Plant Hub.
        </p>


        <div class="feature-grid">


            <!-- PURCHASE -->

            <a
                href="purchase_tracking.php"
                class="feature-card"
            >

                <h3>
                    📦 Purchase Tracking
                </h3>

                <p>
                    Buy plants from suppliers
                    and update inventory stock.
                </p>

            </a>


            <!-- SUPPLIER -->

            <a
                href="supplier_management.php"
                class="feature-card"
            >

                <h3>
                    🚚 Supplier Management
                </h3>

                <p>
                    Manage suppliers and
                    restocking information.
                </p>

            </a>


            <!-- EXCHANGE -->

            <a
                href="exchange_management.php"
                class="feature-card"
            >

                <h3>
                    🔄 Plant Exchange
                </h3>

                <p>
                    Process customer plant
                    exchanges and cash adjustments.
                </p>

            </a>


            <!-- REPORTS -->

            <a
                href="reports.php"
                class="feature-card"
            >

                <h3>
                    📊 Reports & Analytics
                </h3>

                <p>
                    View revenue, best-sellers,
                    customers, suppliers and
                    exchange performance.
                </p>

            </a>


            <!-- INVENTORY -->

            <a
                href="employee_dashboard.php?view=inventory"
                class="feature-card"
            >

                <h3>
                    🌿 Inventory
                </h3>

                <p>
                    Check stock quantities,
                    prices and plant categories.
                </p>

            </a>


            <!-- AUDIT -->

            <a
                href="employee_dashboard.php?view=audit"
                class="feature-card"
            >

                <h3>
                    📋 Audit Logs
                </h3>

                <p>
                    Track employee actions
                    and inventory changes.
                </p>

            </a>


        </div>

    </div>


<?php endif; ?>


</div>


</body>

</html>