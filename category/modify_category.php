<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$id = $_GET['id'];
$sql = "SELECT * FROM Category WHERE Category_ID = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category</title>
</head>
<body>

    <h2>Edit Category</h2>

    <form action="update_category.php" method="post">
        <!-- Hidden input field keeps track of Category_ID during update -->
        <input type="hidden" name="category_id" value="<?php echo $row['Category_ID']; ?>">

        Category Name: 
        <input type="text" name="category_name" value="<?php echo $row['Category_name']; ?>" required>
        <br><br>

        <input type="submit" value="Update Category">
    </form>

    <br>
    <a href="show_category.php">Cancel</a>

</body>
</html>