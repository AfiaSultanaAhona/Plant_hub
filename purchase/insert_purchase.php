<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['supplier_id']) && isset($_POST['plant_id'])) {
    $supplier_id = $_POST['supplier_id'];
    $plant_id = $_POST['plant_id'];
    $qty = (int)$_POST['quantity'];
    $unit_cost = (float)$_POST['unit_cost'];
    $purchase_date = $_POST['purchase_date'];

    // Get Employee_ID from session (set during login)
    $employee_id = $_SESSION['employee_id'] ?? null;

    $total_amount = $unit_cost * $qty;

    // Validate inputs
    if ($qty <= 0) {
        die("Error: Quantity must be at least 1.");
    }
    if ($unit_cost <= 0) {
        die("Error: Unit cost must be greater than 0.");
    }

    // Insert into purchase_transaction with Employee_ID
    $sql = "INSERT INTO Purchase_Transaction (Purchase_date, Total_amount, Supplier_ID, Plant_ID, Quantity, Employee_ID) 
            VALUES ('$purchase_date', '$total_amount', '$supplier_id', '$plant_id', '$qty', " . ($employee_id ? "'$employee_id'" : "NULL") . ")";

    if (mysqli_query($conn, $sql)) {
        $new_purchase_id = mysqli_insert_id($conn);

        // Automatically INCREASE inventory stock quantity when purchasing from suppliers
        mysqli_query($conn, "UPDATE Plant SET Stock_quantity = Stock_quantity + $qty WHERE Plant_ID = '$plant_id'");

        // Also insert into the relational purchase tables
        @mysqli_query($conn, "INSERT INTO purchase (Purchase_ID, Purchase_date, Supplier_ID, Employee_ID) 
                VALUES ('$new_purchase_id', '$purchase_date', '$supplier_id', " . ($employee_id ? "'$employee_id'" : "NULL") . ")");
        @mysqli_query($conn, "INSERT INTO purchase_contains_plant (Purchase_ID, Plant_ID, Quantity, Purchase_unit_price) 
                VALUES ('$new_purchase_id', '$plant_id', '$qty', '$unit_cost')");

        // Log employee action for audit trail
        if ($employee_id) {
            // Fetch plant name for the log description
            $plant_res = mysqli_query($conn, "SELECT Plant_name FROM Plant WHERE Plant_ID = '$plant_id'");
            $plant_name = ($plant_res && $pr = mysqli_fetch_assoc($plant_res)) ? $pr['Plant_name'] : "Plant #$plant_id";

            logEmployeeAction($conn, $employee_id, 'PURCHASE', "Purchased $qty units of $plant_name from Supplier #$supplier_id for ৳$total_amount", $new_purchase_id);
        }

        header("Location: show_purchase.php");
        exit();
    } else {
        echo "Error recording purchase: " . mysqli_error($conn);
    }
}
?>