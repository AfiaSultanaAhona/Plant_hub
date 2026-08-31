-- Plant Hub: exchange workflow database fix
-- Run this once in phpMyAdmin after selecting the `plant_hub` database.
-- The IF NOT EXISTS clauses make this safe if DBconnect.php has already added the column/index.

ALTER TABLE `exchange`
    ADD COLUMN IF NOT EXISTS `Order_ID` INT DEFAULT NULL AFTER `exchange_id`;

ALTER TABLE `exchange`
    ADD INDEX IF NOT EXISTS `idx_exchange_order` (`Order_ID`);
