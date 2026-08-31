<?php
// DBconnect.php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "plant_hub";

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

/*
 * Create supporting tables if they do not already exist.
 * These checks are safe to run on every page load.
 */
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

/*
 * Add columns only when they are missing. This keeps existing installations
 * working without repeatedly attempting the same ALTER TABLE statement.
 */
$column_checks = [
    ['table' => 'purchase_transaction', 'column' => 'Employee_id', 'definition' => 'INT DEFAULT NULL'],
    ['table' => 'orders', 'column' => 'Employee_id', 'definition' => 'INT DEFAULT NULL'],
    ['table' => 'exchange', 'column' => 'Order_ID', 'definition' => 'INT DEFAULT NULL AFTER exchange_id']
];

foreach ($column_checks as $check) {
    $table = $check['table'];
    $column = $check['column'];
    $definition = $check['definition'];

    $table_e = mysqli_real_escape_string($conn, $table);
    $column_e = mysqli_real_escape_string($conn, $column);

    $exists = mysqli_query($conn, "SELECT COUNT(*) AS cnt
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = '$table_e'
          AND COLUMN_NAME = '$column_e'");

    if ($exists && (int)mysqli_fetch_assoc($exists)['cnt'] === 0) {
        @mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

/**
 * Earn 10 loyalty points for every complete ৳500 spent on an order.
 */
function processOrderLoyaltyPoints($conn, $user_id, $order_id, $total_amount) {
    $uid = (int)$user_id;
    $oid = (int)$order_id;
    $points_earned = (int)floor((float)$total_amount / 500) * 10;

    if ($points_earned > 0 && $uid > 0) {
        mysqli_query($conn, "UPDATE customer
            SET points = COALESCE(points, 0) + $points_earned,
                Loyalty_points = COALESCE(Loyalty_points, 0) + $points_earned
            WHERE Customer_ID = $uid");

        $desc = mysqli_real_escape_string($conn, "Earned $points_earned points from Order #$oid");
        mysqli_query($conn, "INSERT INTO loyalty_logs
            (user_id, order_id, points, transaction_type, description)
            VALUES ($uid, $oid, $points_earned, 'EARNED', '$desc')");
    }

    return $points_earned;
}

/**
 * Redeem loyalty points from a customer's balance.
 */
function redeemLoyaltyPoints($conn, $user_id, $order_id, $points_to_redeem) {
    $uid = (int)$user_id;
    $oid = (int)$order_id;
    $pts = (int)$points_to_redeem;

    if ($uid <= 0 || $pts <= 0) {
        return 0;
    }

    $res = mysqli_query($conn, "SELECT points FROM customer WHERE Customer_ID = $uid LIMIT 1");
    if (!$res || !($row = mysqli_fetch_assoc($res))) {
        return 0;
    }

    $current = (int)($row['points'] ?? 0);
    $actual_redeem = min($pts, $current);

    if ($actual_redeem <= 0) {
        return 0;
    }

    mysqli_query($conn, "UPDATE customer
        SET points = GREATEST(0, points - $actual_redeem),
            Loyalty_points = GREATEST(0, Loyalty_points - $actual_redeem)
        WHERE Customer_ID = $uid");

    $desc = mysqli_real_escape_string($conn, "Redeemed $actual_redeem points on Order #$oid (discount applied)");
    mysqli_query($conn, "INSERT INTO loyalty_logs
        (user_id, order_id, points, transaction_type, description)
        VALUES ($uid, $oid, $actual_redeem, 'REDEEMED', '$desc')");

    return $actual_redeem;
}

/**
 * Employee audit logger.
 */
function logEmployeeAction($conn, $action_type, $description, $reference_id = null, $employee_id = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($employee_id === null) {
        $raw_emp = $_SESSION['employee_id'] ?? $_SESSION['Employee_id'] ?? null;
        $employee_id = (int)preg_replace('/[^0-9]/', '', (string)$raw_emp);
    }

    if ($employee_id <= 0) {
        return false;
    }

    $emp_id = (int)$employee_id;
    $action = mysqli_real_escape_string($conn, $action_type);
    $desc   = mysqli_real_escape_string($conn, $description);
    $ref    = ($reference_id !== null) ? (int)$reference_id : "NULL";

    return mysqli_query($conn, "INSERT INTO audit_log
        (Employee_id, Action_type, Details, Reference_id)
        VALUES ($emp_id, '$action', '$desc', $ref)");
}

function log_audit_trail($conn, $action_type, $details, $reference_id = null) {
    return logEmployeeAction($conn, $action_type, $details, $reference_id);
}
?>
