<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $sql = "DELETE FROM Customer WHERE Customer_ID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_customer.php");
        exit();
    } else {
        echo "Error deleting customer: " . mysqli_error($conn);
    }
}
?>