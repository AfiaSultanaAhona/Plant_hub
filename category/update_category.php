<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $cname  = mysqli_real_escape_string($conn, $_POST['category_name'] ?? '');

    if ($cat_id > 0 && !empty($cname)) {
        $sql = "UPDATE category SET Category_name = '$cname' WHERE Category_ID = $cat_id";
        if (mysqli_query($conn, $sql)) {
            logEmployeeAction($conn, 'CATEGORY_UPDATE', "Updated category #$cat_id to '$cname'", $cat_id);
            header("Location: show_category.php?msg=updated");
            exit;
        } else {
            die("Update Error: " . mysqli_error($conn));
        }
    }
}
header("Location: show_category.php");
exit;
?>