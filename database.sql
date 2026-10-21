CREATE DATABASE IF NOT EXISTS expense_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expense_tracker;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('income','expense') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    trans_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, trans_date)
);
