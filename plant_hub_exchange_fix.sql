-- Plant Hub Exchange Workflow Fix
-- Run this ONCE in phpMyAdmin on the plant_hub database.
--
-- New workflow:
-- Customer requests -> Pending
-- Employee Approve & Process -> Approved
-- Employee approval performs inventory + wallet/COD processing.
-- Customer does NOT need a second "Complete Exchange" button.

USE plant_hub;

-- 1. Add Order_ID so every exchange request is tied to the exact purchase.
ALTER TABLE exchange
    ADD COLUMN Order_ID INT(11) NULL AFTER Offered_plant_ID;

-- 2. Index the new relationship.
ALTER TABLE exchange
    ADD KEY Order_ID (Order_ID);

-- 3. Make exchange text columns large enough for the displayed payment messages.
ALTER TABLE exchange
    MODIFY payment_method VARCHAR(100) DEFAULT 'N/A',
    MODIFY payment_status VARCHAR(255) DEFAULT 'Pending',
    MODIFY adjustment_direction VARCHAR(100) DEFAULT 'No Adjustment',
    MODIFY notes VARCHAR(500) DEFAULT NULL;

-- 4. Ensure every customer has a usable wallet balance.
UPDATE customer
SET wallet_balance = 0
WHERE wallet_balance IS NULL;

-- IMPORTANT:
-- Existing old exchange rows may have Customer_ID=0 and no Order_ID.
-- They cannot be reliably linked to a real customer order automatically.
-- New requests created after this fix will contain the correct Order_ID.
--
-- Optional cleanup of old test exchange requests:
-- DELETE FROM exchange WHERE Customer_ID=0;
--
-- Do NOT run the DELETE above if you need those old records.

COMMIT;
