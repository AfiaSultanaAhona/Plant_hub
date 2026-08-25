<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['customer_id']) && isset($_POST['customer_name'])) {

    $id = $_POST['customer_id'];
    $name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $sql = "UPDATE Customer SET 
            Customer_name = '$name', 
            Phone = '$phone', 
            Address = '$address' 
            WHERE Customer_ID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_customer.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>