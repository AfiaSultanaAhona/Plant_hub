<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['customer_id']) && isset($_POST['customer_name'])) {
    
    $id = $_POST['customer_id'];
    $name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $sql = "INSERT INTO Customer (Customer_ID, Customer_name, Phone, Address) 
            VALUES ('$id', '$name', '$phone', '$address')";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_customer.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>