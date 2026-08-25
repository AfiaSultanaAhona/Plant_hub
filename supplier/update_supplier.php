<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['supplier_id']) && isset($_POST['supplier_name'])) {

    $id = $_POST['supplier_id'];
    $name = $_POST['supplier_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    $sql = "UPDATE Supplier SET 
            Supplier_name = '$name', 
            Phone = '$phone', 
            Address = '$address', 
            Email = '$email' 
            WHERE Supplier_ID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_supplier.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>