-- ============================================================
-- EXPENSE TRACKER PRO - DATABASE SETUP
-- Run this SQL in your MySQL/phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS expense_tracker 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE expense_tracker;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','user') DEFAULT 'user',
    avatar      VARCHAR(255) DEFAULT NULL,
    currency    VARCHAR(10) DEFAULT 'INR',
    theme       ENUM('light','dark') DEFAULT 'light',
    monthly_budget DECIMAL(15,2) DEFAULT 0.00,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    icon        VARCHAR(50) DEFAULT '💰',
    color       VARCHAR(20) DEFAULT '#6366f1',
    type        ENUM('income','expense','both') DEFAULT 'both',
    user_id     INT DEFAULT NULL,  -- NULL = global category
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TRANSACTIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('income','expense') NOT NULL,
    amount      DECIMAL(15,2) NOT NULL,
    category_id INT DEFAULT NULL,
    description VARCHAR(255) DEFAULT '',
    date        DATE NOT NULL,
    currency    VARCHAR(10) DEFAULT 'INR',
    receipt     VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ============================================================
-- BUDGETS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS budgets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    month       TINYINT NOT NULL,   -- 1-12
    year        SMALLINT NOT NULL,
    amount      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_budget (user_id, month, year),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    message     TEXT NOT NULL,
    type        ENUM('warning','info','success','danger') DEFAULT 'info',
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- DEFAULT DATA: Admin + Demo User
-- ============================================================
-- Admin: admin@demo.com / Admin@123
-- User:  user@demo.com  / User@123
INSERT INTO users (name, email, password, role, currency) VALUES
('Super Admin',  'admin@demo.com', '$2y$12$5Qy6KMx5oF.RzdkM7bSWJeqCXGzMSiMHIf0aOIIc2bH/1Ij5b3Rba', 'admin', 'INR'),
('Demo User',    'user@demo.com',  '$2y$12$T5YksPJMH7Fz1vfVCjXzXe6mJxQV3Xa9ILuqFIBd9hg3FhPJm7mBO', 'user',  'INR');

-- ============================================================
-- DEFAULT CATEGORIES
-- ============================================================
INSERT INTO categories (name, icon, color, type, user_id) VALUES
('Salary',        '💼', '#10b981', 'income',  NULL),
('Freelance',     '💻', '#3b82f6', 'income',  NULL),
('Investment',    '📈', '#8b5cf6', 'income',  NULL),
('Food & Dining', '🍔', '#f59e0b', 'expense', NULL),
('Transport',     '🚗', '#6366f1', 'expense', NULL),
('Shopping',      '🛍️', '#ec4899', 'expense', NULL),
('Utilities',     '💡', '#14b8a6', 'expense', NULL),
('Healthcare',    '🏥', '#ef4444', 'expense', NULL),
('Education',     '📚', '#f97316', 'expense', NULL),
('Entertainment', '🎮', '#a855f7', 'expense', NULL),
('Rent',          '🏠', '#84cc16', 'expense', NULL),
('Other',         '📦', '#94a3b8', 'both',    NULL);

-- ============================================================
-- SAMPLE TRANSACTIONS FOR DEMO USER (user_id=2)
-- ============================================================
INSERT INTO transactions (user_id, type, amount, category_id, description, date) VALUES
(2, 'income',  50000.00, 1, 'Monthly Salary',         DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')),
(2, 'income',  12000.00, 2, 'Website Project',         DATE_FORMAT(NOW(), '%Y-%m-05')),
(2, 'expense', 8000.00,  11,'Monthly Rent',             DATE_FORMAT(NOW(), '%Y-%m-01')),
(2, 'expense', 3500.00,  4, 'Groceries & Dining',       DATE_FORMAT(NOW(), '%Y-%m-08')),
(2, 'expense', 1200.00,  5, 'Cab & Fuel',               DATE_FORMAT(NOW(), '%Y-%m-10')),
(2, 'expense', 2500.00,  6, 'Clothes Shopping',         DATE_FORMAT(NOW(), '%Y-%m-12')),
(2, 'expense', 800.00,   7, 'Electricity Bill',         DATE_FORMAT(NOW(), '%Y-%m-03')),
(2, 'expense', 1500.00,  8, 'Doctor Visit',             DATE_FORMAT(NOW(), '%Y-%m-14')),
(2, 'income',  50000.00, 1, 'Monthly Salary',           DATE_FORMAT(NOW(), '%Y-%m-01')),
(2, 'expense', 600.00,   10,'Netflix & Spotify',        DATE_FORMAT(NOW(), '%Y-%m-07')),
(2, 'expense', 3000.00,  9, 'Online Course',            DATE_FORMAT(NOW(), '%Y-%m-11')),
(2, 'expense', 2000.00,  4, 'Restaurant Dinners',       DATE_FORMAT(NOW(), '%Y-%m-16')),
(2, 'income',  8000.00,  3, 'Stock Dividends',          DATE_FORMAT(NOW(), '%Y-%m-15'));

-- Default budget for demo user
INSERT INTO budgets (user_id, month, year, amount) VALUES
(2, MONTH(NOW()), YEAR(NOW()), 25000.00);
