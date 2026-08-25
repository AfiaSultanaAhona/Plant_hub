<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_POST['category_id']) && isset($_POST['category_name'])) {

    $id = $_POST['category_id'];
    $name = $_POST['category_name'];

    $sql = "UPDATE Category SET Category_name = '$name' WHERE Category_ID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_category.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>