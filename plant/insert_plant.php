<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['plant_id']) && isset($_POST['plant_name'])) {
    
    $id = $_POST['plant_id'];
    $name = $_POST['plant_name'];
    $price = $_POST['unit_price'];
    $stock = $_POST['stock_quantity'];
    $low_stock = $_POST['low_stock'];
    $care = $_POST['care_info'];
    $cat_id = $_POST['category_id'];

    $sql = "INSERT INTO Plant (Plant_ID, Plant_name, Unit_price, Stock_quantity, Low_stock_level, Care_info, Category_ID) 
            VALUES ('$id', '$name', '$price', '$stock', '$low_stock', '$care', '$cat_id')";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_plant.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>