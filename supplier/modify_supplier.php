<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$id = $_GET['id'];
$sql = "SELECT * FROM Supplier WHERE Supplier_ID = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Supplier</title>
</head>
<body>

    <h2>Edit Supplier</h2>

    <form action="update_supplier.php" method="post">
        <input type="hidden" name="supplier_id" value="<?php echo $row['Supplier_ID']; ?>">

        Supplier Name: <br>
        <input type="text" name="supplier_name" value="<?php echo $row['Supplier_name']; ?>" required><br><br>

        Phone: <br>
        <input type="text" name="phone" value="<?php echo $row['Phone']; ?>" required><br><br>

        Address: <br>
        <input type="text" name="address" value="<?php echo $row['Address']; ?>" required><br><br>

        Email: <br>
        <input type="email" name="email" value="<?php echo $row['Email']; ?>" required><br><br>

        <input type="submit" value="Update Supplier">
    </form>

    <br>
    <a href="show_supplier.php">Cancel</a>

</body>
</html>