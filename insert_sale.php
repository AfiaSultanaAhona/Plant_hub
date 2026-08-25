<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['sale_id']) && isset($_POST['plant_id'])) {

    $sale_id = $_POST['sale_id'];
    $customer_id = $_POST['customer_id'];
    $employee_id = $_POST['employee_id'];
    $plant_id = $_POST['plant_id'];
    $qty = $_POST['quantity'];
    $sale_date = $_POST['sale_date'];

    // Get unit price and current stock of plant
    $plant_query = mysqli_query($conn, "SELECT Unit_price, Stock_quantity FROM Plant WHERE Plant_ID = '$plant_id'");
    $plant_data = mysqli_fetch_assoc($plant_query);

    if ($plant_data['Stock_quantity'] < $qty) {
        die("Error: Not enough stock available for this sale. Current stock: " . $plant_data['Stock_quantity']);
    }

    $total_amount = $plant_data['Unit_price'] * $qty;

    // Insert into sales table
    $sql = "INSERT INTO Sales_Transaction (Txn_ID, Txn_date, Total_amount, Customer_ID, Employee_ID) 
            VALUES ('$sale_id', '$sale_date', '$total_amount', '$customer_id', '$employee_id')";

    if (mysqli_query($conn, $sql)) {
        // Update stock quantity
        $new_stock = $plant_data['Stock_quantity'] - $qty;
        mysqli_query($conn, "UPDATE Plant SET Stock_quantity = '$new_stock' WHERE Plant_ID = '$plant_id'");

        header("Location: show_sales.php");
        exit();
    } else {
        echo "Error recording sale: " . mysqli_error($conn);
    }
}
?>