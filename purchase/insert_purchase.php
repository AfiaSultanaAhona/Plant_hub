<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['purchase_id'])) {
    $purchase_id = $_POST['purchase_id'];
    $supplier_id = $_POST['supplier_id'];
    $plant_id = $_POST['plant_id'];
    $qty = $_POST['quantity'];
    $unit_cost = $_POST['unit_cost'];
    $purchase_date = $_POST['purchase_date'];

    $total_amount = $unit_cost * $qty;

    $sql = "INSERT INTO Purchase_Transaction (Purchase_ID, Purchase_date, Total_amount, Supplier_ID, Plant_ID, Quantity) 
            VALUES ('$purchase_id', '$purchase_date', '$total_amount', '$supplier_id', '$plant_id', '$qty')";

    if (mysqli_query($conn, $sql)) {
        // Automatically INCREASE inventory stock quantity when purchasing from suppliers
        mysqli_query($conn, "UPDATE Plant SET Stock_quantity = Stock_quantity + $qty WHERE Plant_ID = '$plant_id'");

        header("Location: show_purchase.php");
        exit();
    } else {
        echo "Error recording purchase: " . mysqli_error($conn);
    }
}
?>