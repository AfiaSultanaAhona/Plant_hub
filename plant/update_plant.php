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

    $sql = "UPDATE Plant SET 
            Plant_name = '$name', 
            Unit_price = '$price', 
            Stock_quantity = '$stock', 
            Low_stock_level = '$low_stock', 
            Care_info = '$care', 
            Category_ID = '$cat_id' 
            WHERE Plant_ID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_plant.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>