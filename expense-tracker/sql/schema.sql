-- Personal Expense & Budget Tracker: database schema
-- Import via phpMyAdmin (Import tab) or: mysql -u root < sql/schema.sql

CREATE DATABASE IF NOT EXISTS expense_tracker
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expense_tracker;

-- One row per registered person. Passwords are stored as hashes, never plain text.
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Money going out. user_id is a foreign key: deleting a user deletes their expenses.
CREATE TABLE IF NOT EXISTS expenses (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED  NOT NULL,
  title        VARCHAR(120)  NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  category     VARCHAR(50)   NOT NULL,
  expense_date DATE          NOT NULL,
  note         VARCHAR(255)  NULL,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_date (user_id, expense_date)
) ENGINE=InnoDB;

-- Money coming in (pocket money, stipend, freelance work, ...).
CREATE TABLE IF NOT EXISTS incomes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED  NOT NULL,
  source      VARCHAR(120)  NOT NULL,
  amount      DECIMAL(10,2) NOT NULL,
  income_date DATE          NOT NULL,
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_incomes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_date (user_id, income_date)
) ENGINE=InnoDB;

-- One monthly limit per category per user. UNIQUE lets us use INSERT ... ON DUPLICATE KEY UPDATE.
CREATE TABLE IF NOT EXISTS budgets (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  category      VARCHAR(50)   NOT NULL,
  monthly_limit DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_category (user_id, category)
) ENGINE=InnoDB;
