-- Plant Hub Team Database Sync / Migration
-- MariaDB 10.4+ / XAMPP
-- Run this AFTER creating/selecting the plant_hub database.
-- This script preserves existing customer/order/exchange data.

USE `plant_hub`;

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

START TRANSACTION;

-- 1. CUSTOMER WALLET
ALTER TABLE `customer`
    ADD COLUMN IF NOT EXISTS `wallet_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- 2. LINK EXCHANGE REQUESTS TO ORDERS
ALTER TABLE `exchange`
    ADD COLUMN IF NOT EXISTS `Order_ID` INT(11) DEFAULT NULL AFTER `Offered_plant_ID`;

ALTER TABLE `exchange`
    ADD INDEX IF NOT EXISTS `idx_exchange_order` (`Order_ID`);

-- 3. REPAIR LEGACY ORDER ID 0, IF PRESENT
SET @next_order_id := (
    SELECT COALESCE(MAX(`Order_id`), 0) + 1
    FROM `orders`
);

UPDATE `orders`
SET `Order_id` = @next_order_id
WHERE `Order_id` = 0;

-- 4. ADD ORDERS PRIMARY KEY ONLY IF MISSING
SET @pk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND CONSTRAINT_NAME = 'PRIMARY'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);

SET @sql := IF(
    @pk_exists = 0,
    'ALTER TABLE `orders` ADD PRIMARY KEY (`Order_id`)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. ENABLE AUTO-INCREMENT FOR NEW ORDERS
ALTER TABLE `orders`
    MODIFY `Order_id` INT(11) NOT NULL AUTO_INCREMENT;

-- 6. SET THE NEXT AUTO-INCREMENT VALUE
SET @next_order_id := (
    SELECT COALESCE(MAX(`Order_id`), 0) + 1
    FROM `orders`
);

SET @sql := CONCAT(
    'ALTER TABLE `orders` AUTO_INCREMENT = ',
    @next_order_id
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

-- 7. VERIFICATION
SELECT
    MIN(`Order_id`) AS min_order_id,
    MAX(`Order_id`) AS max_order_id,
    COUNT(*) AS total_orders,
    COUNT(DISTINCT `Order_id`) AS unique_order_ids
FROM `orders`;

SELECT
    `Customer_ID`,
    `Customer_name`,
    `wallet_balance`,
    `points`
FROM `customer`
ORDER BY `Customer_ID`;

SELECT
    `exchange_id`,
    `Order_ID`,
    `Customer_ID`,
    `Offered_plant_ID`,
    `Received_plant_ID`,
    `status`,
    `payment_method`,
    `payment_status`
FROM `exchange`
ORDER BY `exchange_id` DESC
LIMIT 20;
