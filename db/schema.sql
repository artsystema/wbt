CREATE DATABASE IF NOT EXISTS task_tracker;
USE task_tracker;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  passcode VARCHAR(255) UNIQUE NOT NULL,
  note TEXT
);

CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  description TEXT,
  links TEXT,
  category VARCHAR(255),
  attachments TEXT,
  reward DECIMAL(10,2),
  estimated_minutes INT,
  date_posted DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('available', 'in_progress', 'pending_review', 'completed', 'archived') DEFAULT 'available',
  assigned_to VARCHAR(255),
  start_time DATETIME,
  submission_time DATETIME,
  quit_comment TEXT,
  last_rejected VARCHAR(255)
);

CREATE TABLE submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT,
  user_passcode VARCHAR(255),
  file_path TEXT,
  comment TEXT,
  submitted_at DATETIME,
  FOREIGN KEY (task_id) REFERENCES tasks(id)
);

CREATE TABLE bonus_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  min_tasks INT,
  bonus_percent DECIMAL(5,2)
);

INSERT INTO bonus_rules (min_tasks, bonus_percent) VALUES
  (1, 0.05),
  (2, 0.10),
  (5, 0.25),
  (10, 0.50),
  (20, 19.00);

CREATE TABLE fund_bank (
  id INT PRIMARY KEY CHECK (id = 1),
  total_funds DECIMAL(10,2),
  last_updated DATETIME
);

INSERT INTO fund_bank (id, total_funds, last_updated) VALUES (1, 0.00, NOW());

CREATE TABLE payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  passcode VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  paid_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fund_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  txn_type ENUM('deposit','payout') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_user (
  id INT PRIMARY KEY,
  password_hash VARCHAR(255) NOT NULL
);

INSERT INTO admin_user (id, password_hash) VALUES (1, '');
