// Fetch Points Logic inside header.php
$user_points = 0; // Default to 0

if (isset($_SESSION['customer_id'])) {
    $cust_id = mysqli_real_escape_string($conn, $_SESSION['customer_id']);
    
    // Extract numerical ID if prefix like 'C' or 'E' exists
    $numeric_id = preg_replace('/[^0-9]/', '', $cust_id);

    // Fetch exact points column for current logged in user
    $query = "SELECT points FROM customer WHERE Customer_ID = '$cust_id' OR Customer_ID = '$numeric_id' LIMIT 1";
    $res = mysqli_query($conn, $query);

    if ($res && $row = mysqli_fetch_assoc($res)) {
        $user_points = (int)($row['points'] ?? 0);
    }
}