<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['exchange_id'])) {
    $exchange_id = $_POST['exchange_id'];
    $customer_id = $_POST['customer_id'];
    $plant_id = $_POST['plant_id'];
    $qty = $_POST['quantity'];
    $reason = $_POST['reason'];
    $exchange_date = $_POST['exchange_date'];

    $sql = "INSERT INTO Plant_Exchange (Exchange_ID, Exchange_date, Reason, Customer_ID, Plant_ID, Quantity) 
            VALUES ('$exchange_id', '$exchange_date', '$reason', '$customer_id', '$plant_id', '$qty')";

    if (mysqli_query($conn, $sql)) {
        // Restock returned plant item back into inventory
        mysqli_query($conn, "UPDATE Plant SET Stock_quantity = Stock_quantity + $qty WHERE Plant_ID = '$plant_id'");

        header("Location: show_exchange.php");
        exit();
    } else {
        echo "Error recording exchange: " . mysqli_error($conn);
    }
}
?>