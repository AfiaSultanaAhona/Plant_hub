<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['txn_id']) && isset($_POST['plant_id'])) {

    $txn_id = $_POST['txn_id'];
    $customer_id = $_POST['customer_id'];
    $plant_id = $_POST['plant_id'];
    $qty = (int)$_POST['quantity'];
    $txn_date = $_POST['txn_date'];

    // Get Employee_ID from session (set during login)
    $employee_id = $_SESSION['employee_id'] ?? null;

    // Get unit price and current stock of plant
    $plant_query = mysqli_query($conn, "SELECT Unit_price, Stock_quantity FROM Plant WHERE Plant_ID = '$plant_id'");
    $plant_data = mysqli_fetch_assoc($plant_query);

    if ($plant_data['Stock_quantity'] < $qty) {
        die("Error: Not enough stock available for this sale. Current stock: " . $plant_data['Stock_quantity']);
    }

    $total_amount = $plant_data['Unit_price'] * $qty;

    // Insert into sales_transaction table with Employee_ID
    $sql = "INSERT INTO Sales_Transaction (Txn_ID, Txn_date, Total_amount, Customer_ID, Employee_ID) 
            VALUES ('$txn_id', '$txn_date', '$total_amount', '$customer_id', " . ($employee_id ? "'$employee_id'" : "NULL") . ")";

    if (mysqli_query($conn, $sql)) {
        // Update stock quantity
        $new_stock = $plant_data['Stock_quantity'] - $qty;
        mysqli_query($conn, "UPDATE Plant SET Stock_quantity = '$new_stock' WHERE Plant_ID = '$plant_id'");

        // Log employee action for audit trail
        if ($employee_id) {
            logEmployeeAction($conn, $employee_id, 'SALE', "Recorded sale Txn #$txn_id: $qty units of Plant #$plant_id for ৳$total_amount to Customer #$customer_id", $txn_id);
        }

        header("Location: show_sales.php");
        exit();
    } else {
        echo "Error recording sale: " . mysqli_error($conn);
    }
}
?>