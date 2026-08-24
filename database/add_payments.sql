-- Run this once in phpMyAdmin after importing database/pms_db.sql.
CREATE TABLE `tblpayments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `BillingId` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `provider` enum('cash','esewa','khalti') NOT NULL,
  `payment_reference` varchar(100) NOT NULL,
  `gateway_transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_invoice` (`UserID`,`BillingId`),
  UNIQUE KEY `uq_payment_reference` (`payment_reference`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`UserID`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
