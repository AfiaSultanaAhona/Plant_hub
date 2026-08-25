<?php
require_once("../check_login.php");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category</title>
</head>
<body>

    <h2>Add New Category</h2>

    <form action="insert_category.php" method="post">
        Category ID: 
        <input type="number" name="category_id" required>
        <br><br>

        Category Name: 
        <input type="text" name="category_name" required>
        <br><br>

        <input type="submit" value="Add Category">
    </form>

    <br>
    <a href="show_category.php">View All Categories</a> | 
    <a href="../home.php">Back to Dashboard</a>

</body>
</html>