<?php
// DBconnect.php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "plant_hub"; // Update this if your database name is different

// Turn off fatal SQL exception throws for PHP 8.1+ compatibility
mysqli_report(MYSQLI_REPORT_OFF);

// Create MySQL connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection status
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

/**
 * Helper Function: Process & Award Loyalty Points
 * Earns 10 points for every complete ৳500 spent per order.
 */
function processOrderLoyaltyPoints($conn, $user_id, $order_id, $total_amount) {
    $points_earned = (int)floor($total_amount / 500) * 10;
    
    if ($points_earned > 0 && $user_id) {
        // Update user's permanent loyalty points balance in customer table
        $update_sql = "UPDATE customer SET loyalty_points = loyalty_points + $points_earned WHERE customer_id = '$user_id' OR id = '$user_id'";
        mysqli_query($conn, $update_sql);

        // Record entry in loyalty_logs
        $desc = "Earned $points_earned points from Order #$order_id";
        $log_sql = "INSERT INTO loyalty_logs (user_id, order_id, points, transaction_type, description) 
                    VALUES ('$user_id', '$order_id', '$points_earned', 'EARNED', '$desc')";
        mysqli_query($conn, $log_sql);
    }
    return $points_earned;
}

/**
 * Helper Function: Employee Audit Trail Logger
 * Logs every sale, supplier purchase, exchange, or stock edit against an employee ID.
 */
function logEmployeeAction($conn, $employee_id, $action_type, $description, $reference_id = null) {
    $emp_id = (int)($employee_id ?? 1); // Defaults to Admin employee ID (1) if no active staff session
    $action = mysqli_real_escape_string($conn, $action_type);
    $desc   = mysqli_real_escape_string($conn, $description);
    $ref    = $reference_id ? (int)$reference_id : "NULL";

    $sql = "INSERT INTO audit_logs (employee_id, action_type, description, reference_id) 
            VALUES ($emp_id, '$action', '$desc', $ref)";
            
    return mysqli_query($conn, $sql);
}
?>