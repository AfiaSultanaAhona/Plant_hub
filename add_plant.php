<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("DBconnect.php");

/* =========================================================
   EMPLOYEE LOGIN CHECK
   ========================================================= */
$employee_id = $_SESSION['Employee_id']
    ?? $_SESSION['employee_id']
    ?? $_SESSION['user_id']
    ?? null;

if (!$employee_id) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   VARIABLES
   ========================================================= */
$error = "";
$success = "";


/* =========================================================
   FETCH CATEGORIES
   ========================================================= */
$categories = [];

$category_query = mysqli_query(
    $conn,
    "SELECT Category_ID, Category_name FROM category ORDER BY Category_name ASC"
);

if ($category_query) {
    while ($category = mysqli_fetch_assoc($category_query)) {
        $categories[] = $category;
    }
}


/* =========================================================
   HANDLE ADD PLANT
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plant_name = trim($_POST['plant_name'] ?? '');
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $low_stock_level = (int)($_POST['low_stock_level'] ?? 5);
    $care_info = trim($_POST['care_info'] ?? '');
    $category_id = !empty($_POST['category_id'])
        ? (int)$_POST['category_id']
        : null;


    /* ---------------------------------------------------------
       VALIDATION
       --------------------------------------------------------- */

    if ($plant_name === '') {
        $error = "Please enter the plant name.";

    } elseif ($unit_price < 0) {
        $error = "Unit price cannot be negative.";

    } elseif ($stock_quantity < 0) {
        $error = "Stock quantity cannot be negative.";

    } elseif ($low_stock_level < 0) {
        $error = "Low stock level cannot be negative.";

    } else {

        /* -----------------------------------------------------
           SAFE INSERT (Fixes duplicate '0' key error)
           ----------------------------------------------------- */

        // 1. Resolve any existing zero-ID conflicts in database
        mysqli_query($conn, "UPDATE plant SET Plant_ID = 9999 WHERE Plant_ID = 0");

        // 2. Compute next unique Plant_ID
        $max_res = mysqli_query($conn, "SELECT MAX(Plant_ID) AS max_id FROM plant");
        $max_row = mysqli_fetch_assoc($max_res);
        $next_id = ((int)($max_row['max_id'] ?? 0)) + 1;

        $sql = "INSERT INTO plant
                (Plant_ID, Plant_name, Unit_price, Stock_quantity, Low_stock_level, Care_info, Category_ID)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "isdiisi",
                $next_id,
                $plant_name,
                $unit_price,
                $stock_quantity,
                $low_stock_level,
                $care_info,
                $category_id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                /* Redirect back to employee inventory */
                header(
                    "Location: employee_dashboard.php?view=inventory&msg=added_new&id=" .
                    $next_id
                );
                exit;

            } else {

                $error = "Failed to add plant: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }

        } else {

            $error = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Plant - Plant Hub</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0fdf4;
            color: #1e293b;
        }

        .navbar {
            background: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #15803d;
            text-decoration: none;
        }

        .employee-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 7px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .container {
            max-width: 750px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        h1 {
            margin-top: 0;
            color: #14532d;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 7px;
            color: #334155;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #15803d;
            box-shadow: 0 0 0 2px rgba(21,128,61,0.1);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
        }

        .btn {
            border: none;
            padding: 11px 18px;
            border-radius: 7px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-add {
            background: #10b981;
            color: white;
        }

        .btn-add:hover {
            background: #059669;
        }

        .btn-back {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-back:hover {
            background: #cbd5e1;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }

        .required {
            color: #ef4444;
        }

        @media (max-width: 600px) {

            .navbar {
                padding: 15px 20px;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .buttons {
                flex-direction: column-reverse;
                gap: 12px;
                align-items: stretch;
            }

            .buttons .btn {
                text-align: center;
            }
        }

    </style>

</head>

<body>

<div class="navbar">

    <a href="employee_dashboard.php" class="logo">
        🌱 Plant Hub
    </a>

    <div class="employee-badge">
        👤 Employee
    </div>

</div>


<div class="container">

    <div class="card">

        <h1>🌿 Add New Plant</h1>

        <p class="subtitle">
            Enter the plant information below to add a new plant to inventory.
        </p>


        <?php if ($error): ?>

            <div class="alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="add_plant.php">

            <!-- PLANT NAME -->

            <div class="form-group">

                <label for="plant_name">
                    Plant Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="plant_name"
                    name="plant_name"
                    placeholder="e.g. Monstera Deliciosa"
                    value="<?php echo htmlspecialchars($_POST['plant_name'] ?? ''); ?>"
                    required
                >

            </div>


            <!-- PRICE + STOCK -->

            <div class="row">

                <div class="form-group">

                    <label for="unit_price">
                        Unit Price (৳) <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        id="unit_price"
                        name="unit_price"
                        min="0"
                        step="0.01"
                        placeholder="650.00"
                        value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="stock_quantity">
                        Initial Stock <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        min="0"
                        placeholder="10"
                        value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>"
                        required
                    >

                </div>

            </div>


            <!-- LOW STOCK + CATEGORY -->

            <div class="row">

                <div class="form-group">

                    <label for="low_stock_level">
                        Low Stock Alert Level
                    </label>

                    <input
                        type="number"
                        id="low_stock_level"
                        name="low_stock_level"
                        min="0"
                        value="<?php echo htmlspecialchars($_POST['low_stock_level'] ?? '5'); ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select id="category_id" name="category_id">

                        <option value="">
                            -- Select Category --
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?php echo (int)$category['Category_ID']; ?>"
                                <?php
                                if (
                                    isset($_POST['category_id']) &&
                                    $_POST['category_id'] == $category['Category_ID']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >

                                <?php echo htmlspecialchars($category['Category_name']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- CARE INFORMATION -->

            <div class="form-group">

                <label for="care_info">
                    Plant Care Information
                </label>

                <textarea
                    id="care_info"
                    name="care_info"
                    placeholder="Example: Needs bright indirect sunlight and watering twice a week."
                ><?php echo htmlspecialchars($_POST['care_info'] ?? ''); ?></textarea>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <a
                    href="employee_dashboard.php?view=inventory"
                    class="btn btn-back"
                >
                    ← Back to Inventory
                </a>

                <button
                    type="submit"
                    class="btn btn-add"
                >
                    ➕ Add Plant
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>