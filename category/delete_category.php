<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

$cat_id = (int)($_GET['id'] ?? $_POST['category_id'] ?? 0);

if ($cat_id > 0) {
    $res = mysqli_query($conn, "SELECT Category_name FROM category WHERE Category_ID = $cat_id");
    $c = mysqli_fetch_assoc($res);
    $cname = $c['Category_name'] ?? "ID #$cat_id";

    $sql = "DELETE FROM category WHERE Category_ID = $cat_id";
    if (mysqli_query($conn, $sql)) {
        logEmployeeAction($conn, 'CATEGORY_DELETE', "Deleted category '$cname' (ID: #$cat_id)", $cat_id);
        header("Location: show_category.php?msg=deleted");
        exit;
    } else {
        echo "Error deleting category: " . mysqli_error($conn);
    }
}
?>