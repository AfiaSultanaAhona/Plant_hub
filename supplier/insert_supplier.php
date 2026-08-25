<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['supplier_id']) && isset($_POST['supplier_name'])) {
    
    $id = $_POST['supplier_id'];
    $name = $_POST['supplier_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    $sql = "INSERT INTO Supplier (Supplier_ID, Supplier_name, Phone, Address, Email) 
            VALUES ('$id', '$name', '$phone', '$address', '$email')";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_supplier.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>