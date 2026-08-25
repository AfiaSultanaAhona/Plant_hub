<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $sql = "DELETE FROM Plant WHERE Plant_ID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: show_plant.php");
        exit();
    } else {
        echo "Error deleting plant: " . mysqli_error($conn);
    }
}
?>