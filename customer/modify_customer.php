<?php
require_once("../check_login.php");
require_once("../DBconnect.php");

$id = $_GET['id'];
$sql = "SELECT * FROM Customer WHERE Customer_ID = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Customer</title>
</head>
<body>

    <h2>Edit Customer</h2>

    <form action="update_customer.php" method="post">
        <input type="hidden" name="customer_id" value="<?php echo $row['Customer_ID']; ?>">

        Customer Name: <br>
        <input type="text" name="customer_name" value="<?php echo $row['Customer_name']; ?>" required><br><br>

        Phone: <br>
        <input type="text" name="phone" value="<?php echo $row['Phone']; ?>" required><br><br>

        Address: <br>
        <input type="text" name="address" value="<?php echo $row['Address']; ?>" required><br><br>

        <input type="submit" value="Update Customer">
    </form>

    <br>
    <a href="show_customer.php">Cancel</a>

</body>
</html>