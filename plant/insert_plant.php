<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pname = mysqli_real_escape_string($conn, $_POST['plant_name'] ?? '');
    $cat   = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock_quantity'] ?? 0);

    if (!empty($pname) && $price > 0) {
        $sql = "INSERT INTO plant (Plant_name, Category, Price, Stock_quantity) VALUES ('$pname', '$cat', '$price', '$stock')";
        if (mysqli_query($conn, $sql)) {
            $plant_id = mysqli_insert_id($conn);
            logEmployeeAction($conn, 'PLANT_ADD', "Added plant '$pname' (Initial Stock: $stock, Price: ৳$price)", $plant_id);
            header("Location: show_plant.php?msg=added");
            exit;
        } else {
            die("Database Error: " . mysqli_error($conn));
        }
    }
}
header("Location: add_plant.php");
exit;
?>