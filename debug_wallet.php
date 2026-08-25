<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include("DBconnect.php");
session_start();

echo "<h3>Debug: Customer Table Schema</h3>";
$desc = mysqli_query($conn, "DESCRIBE customer");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($desc)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

echo "<h3>Session Data</h3>";
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? $_SESSION['id'] ?? $_SESSION['Customer_ID'] ?? 'NOT SET';
echo "user_id from session: <b>" . htmlspecialchars($user_id) . "</b><br>";
$raw_numeric_id = (int)preg_replace('/[^0-9]/', '', (string)$user_id);
echo "raw_numeric_id: <b>$raw_numeric_id</b><br>";
$u_id_esc = mysqli_real_escape_string($conn, (string)$user_id);
echo "u_id_esc: <b>$u_id_esc</b><br>";

echo "<h3>All Customers</h3>";
$all = mysqli_query($conn, "SELECT * FROM customer");
echo "<table border='1'><tr>";
if ($all && mysqli_num_rows($all) > 0) {
    $first = true;
    while ($row = mysqli_fetch_assoc($all)) {
        if ($first) {
            foreach (array_keys($row) as $col) echo "<th>$col</th>";
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $val) echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td>No rows found</td></tr>";
}
echo "</table>";

echo "<h3>Test: Query matching</h3>";
$q1 = mysqli_query($conn, "SELECT Customer_ID, wallet_balance FROM customer WHERE Customer_ID = '$raw_numeric_id'");
echo "Query WHERE Customer_ID = '$raw_numeric_id': ";
if ($q1 && $r1 = mysqli_fetch_assoc($q1)) {
    echo "MATCH - wallet_balance = " . $r1['wallet_balance'];
} else {
    echo "NO MATCH";
}
echo "<br>";

$q2 = mysqli_query($conn, "SELECT Customer_ID, wallet_balance FROM customer WHERE Customer_id = '$raw_numeric_id'");
echo "Query WHERE Customer_id = '$raw_numeric_id': ";
if ($q2 && $r2 = mysqli_fetch_assoc($q2)) {
    echo "MATCH - wallet_balance = " . $r2['wallet_balance'];
} else {
    echo "NO MATCH";
}
echo "<br>";

echo "<h3>Test: wallet UPDATE</h3>";
$test_update = mysqli_query($conn, "UPDATE customer SET wallet_balance = wallet_balance + 0 WHERE Customer_ID = '$raw_numeric_id'");
echo "UPDATE with Customer_ID = '$raw_numeric_id': affected rows = " . mysqli_affected_rows($conn) . "<br>";
?>
