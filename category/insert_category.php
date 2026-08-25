<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
    
    $id = $_POST['category_id'];
    $name = $_POST['category_name'];

    $sql = "INSERT INTO Category (Category_ID, Category_name) VALUES ('$id', '$name')";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_category.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>