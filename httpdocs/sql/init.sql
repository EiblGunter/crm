-- Initial SQL Setup for CRM Application
-- Create Database
CREATE DATABASE IF NOT EXISTS `crm_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `crm_db`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Customers / Addresses Table
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(255),
    `vorname` VARCHAR(100),
    `nachname` VARCHAR(100),
    `strasse` VARCHAR(150),
    `plz` VARCHAR(20),
    `ort` VARCHAR(200),
    `email` VARCHAR(100),
    `phone` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample Data
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- password: password
