<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$plant_id = (int)($_GET['id'] ?? $_POST['plant_id'] ?? 0);

if ($plant_id > 0) {
    $res = mysqli_query($conn, "SELECT Plant_name FROM plant WHERE Plant_ID = $plant_id");
    $p = mysqli_fetch_assoc($res);
    $pname = $p['Plant_name'] ?? "ID #$plant_id";

    $sql = "DELETE FROM plant WHERE Plant_ID = $plant_id";
    if (mysqli_query($conn, $sql)) {
        logEmployeeAction($conn, 'PLANT_DELETE', "Deleted plant '$pname' (ID: #$plant_id) from inventory", $plant_id);
        header("Location: show_plant.php?msg=deleted");
        exit;
    } else {
        echo "Error deleting plant: " . mysqli_error($conn);
    }
} else {
    header("Location: show_plant.php");
}
?>