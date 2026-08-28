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

// 1. Auto-create audit_log table for employee tracking (matching updated schema)
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `audit_log` (
    `Log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `Employee_id` INT NOT NULL,
    `Action_type` VARCHAR(50) NOT NULL,
    `Details` TEXT,
    `Reference_id` INT DEFAULT NULL,
    `Timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_employee` (`Employee_id`),
    KEY `idx_action` (`Action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Backward compatibility check: copy old `audit_logs` data if present
@mysqli_query($conn, "INSERT INTO `audit_log` (`Employee_id`, `Action_type`, `Details`, `Reference_id`, `Timestamp`) 
                      SELECT `employee_id`, `action_type`, `description`, `reference_id`, `created_at` FROM `audit_logs`");

// Ensure transaction tables have Employee_id columns
@mysqli_query($conn, "ALTER TABLE `purchase_transaction` ADD COLUMN `Employee_id` INT DEFAULT NULL");
@mysqli_query($conn, "ALTER TABLE `orders` ADD COLUMN `Employee_id` INT DEFAULT NULL");

// 2. Auto-create loyalty_logs table for points tracking
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `loyalty_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `points` INT NOT NULL,
    `transaction_type` VARCHAR(20) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

/**
 * Helper Function: Process & Award Loyalty Points
 * Earns 10 points for every complete ৳500 spent per order.
 */
function processOrderLoyaltyPoints($conn, $user_id, $order_id, $total_amount) {
    $uid = (int)$user_id;
    $points_earned = (int)floor($total_amount / 500) * 10;
    
    if ($points_earned > 0 && $uid > 0) {
        // Update customer points balance
        mysqli_query($conn, "UPDATE customer SET points = COALESCE(points, 0) + $points_earned WHERE Customer_ID = $uid");

        // Record entry in loyalty_logs
        $oid = $order_id ? (int)$order_id : "NULL";
        $desc = mysqli_real_escape_string($conn, "Earned $points_earned points from Order #$order_id");
        mysqli_query($conn, "INSERT INTO loyalty_logs (user_id, order_id, points, transaction_type, description) 
                    VALUES ($uid, $oid, $points_earned, 'EARNED', '$desc')");
    }
    return $points_earned;
}

/**
 * Helper Function: Redeem Loyalty Points
 * Deducts points from customer balance and logs the redemption.
 */
function redeemLoyaltyPoints($conn, $user_id, $order_id, $points_to_redeem) {
    if ($points_to_redeem <= 0 || !$user_id) return 0;
    
    $uid = (int)$user_id;
    $pts = (int)$points_to_redeem;
    
    // Check current balance
    $res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = $uid LIMIT 1");
    if (!$res || !($row = mysqli_fetch_assoc($res))) return 0;
    
    $current = (int)($row['points'] ?? 0);
    $actual_redeem = min($pts, $current);
    
    if ($actual_redeem <= 0) return 0;
    
    // Deduct points from balance
    mysqli_query($conn, "UPDATE customer SET points = GREATEST(0, points - $actual_redeem) WHERE Customer_ID = $uid");
    
    // Log redemption in loyalty_logs
    $oid = $order_id ? (int)$order_id : "NULL";
    $desc = mysqli_real_escape_string($conn, "Redeemed $actual_redeem points on Order #$order_id (discount applied)");
    mysqli_query($conn, "INSERT INTO loyalty_logs (user_id, order_id, points, transaction_type, description) 
                VALUES ($uid, $oid, $actual_redeem, 'REDEEMED', '$desc')");
    
    return $actual_redeem;
}

/**
 * Helper Function: Employee Audit Trail Logger
 * Auto-detects active Employee session if null, logs actions into audit_log table.
 */
function logEmployeeAction($conn, $action_type, $description, $reference_id = null, $employee_id = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Resolve employee ID automatically if not passed explicitly
    if ($employee_id === null) {
        $raw_emp = $_SESSION['Employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1;
        $employee_id = (int) preg_replace('/[^0-9]/', '', (string)$raw_emp);
    }

    $emp_id = $employee_id > 0 ? $employee_id : 1;
    $action = mysqli_real_escape_string($conn, $action_type);
    $desc   = mysqli_real_escape_string($conn, $description);
    $ref    = ($reference_id !== null) ? (int)$reference_id : "NULL";

    $sql = "INSERT INTO audit_log (Employee_id, Action_type, Details, Reference_id) 
            VALUES ($emp_id, '$action', '$desc', $ref)";
            
    return mysqli_query($conn, $sql);
}

// Alias function for compatibility
function log_audit_trail($conn, $action_type, $details, $reference_id = null) {
    return logEmployeeAction($conn, $action_type, $details, $reference_id);
}
?>