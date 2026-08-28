<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$id = $_GET['id'];
$sql = "SELECT * FROM Plant WHERE Plant_ID = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$cat_sql = "SELECT * FROM Category";
$cat_result = mysqli_query($conn, $cat_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Plant</title>
</head>
<body>

    <h2>Edit Plant</h2>

    <form action="update_plant.php" method="post">
        <input type="hidden" name="plant_id" value="<?php echo $row['Plant_ID']; ?>">

        Plant Name: 
        <input type="text" name="plant_name" value="<?php echo $row['Plant_name']; ?>" required><br><br>

        Unit Price: 
        <input type="number" step="0.01" name="unit_price" value="<?php echo $row['Unit_price']; ?>" required><br><br>

        Stock Quantity: 
        <input type="number" name="stock_quantity" value="<?php echo $row['Stock_quantity']; ?>" required><br><br>

        Low Stock Alert Level: 
        <input type="number" name="low_stock" value="<?php echo $row['Low_stock_level']; ?>" required><br><br>

        Care Information: <br>
        <textarea name="care_info" rows="3" cols="30"><?php echo $row['Care_info']; ?></textarea><br><br>

        Category:
        <select name="category_id" required>
            <?php while ($cat_row = mysqli_fetch_assoc($cat_result)) { ?>
                <option value="<?php echo $cat_row['Category_ID']; ?>" <?php if($row['Category_ID'] == $cat_row['Category_ID']) echo 'selected'; ?>>
                    <?php echo $cat_row['Category_name']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <input type="submit" value="Update Plant">
    </form>

    <br>
    <a href="show_plant.php">Cancel</a>

</body>
</html>