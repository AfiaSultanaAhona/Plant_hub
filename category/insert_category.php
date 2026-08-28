<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("DBconnect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname = mysqli_real_escape_string($conn, $_POST['category_name'] ?? '');

    if (!empty($cname)) {
        $sql = "INSERT INTO category (Category_name) VALUES ('$cname')";
        if (mysqli_query($conn, $sql)) {
            $cat_id = mysqli_insert_id($conn);
            logEmployeeAction($conn, 'CATEGORY_ADD', "Created category '$cname'", $cat_id);
            header("Location: show_category.php?msg=added");
            exit;
        } else {
            die("Database Error: " . mysqli_error($conn));
        }
    }
}
header("Location: add_category.php");
exit;
?>