<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plant_id = (int)($_POST['plant_id'] ?? 0);
    $pname    = mysqli_real_escape_string($conn, $_POST['plant_name'] ?? '');
    $cat      = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock_quantity'] ?? 0);

    if ($plant_id > 0) {
        $sql = "UPDATE plant SET Plant_name='$pname', Category='$cat', Price='$price', Stock_quantity='$stock' WHERE Plant_ID=$plant_id";
        if (mysqli_query($conn, $sql)) {
            logEmployeeAction($conn, 'PLANT_UPDATE', "Updated plant #$plant_id ($pname) - Stock: $stock, Price: ৳$price", $plant_id);
            header("Location: show_plant.php?msg=updated");
            exit;
        } else {
            die("Update Error: " . mysqli_error($conn));
        }
    }
}
header("Location: show_plant.php");
exit;
?>