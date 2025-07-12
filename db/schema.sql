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
  status ENUM('available', 'in_progress', 'pending_review', 'completed') DEFAULT 'available',
  assigned_to VARCHAR(255),
  start_time DATETIME,
  submission_time DATETIME
);

CREATE TABLE submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT,
  user_passcode VARCHAR(255),
  file_path TEXT,
  submitted_at DATETIME,
  FOREIGN KEY (task_id) REFERENCES tasks(id)
);

CREATE TABLE bonus_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  min_tasks INT,
  bonus_percent DECIMAL(5,2)
);

CREATE TABLE fund_bank (
  id INT PRIMARY KEY CHECK (id = 1),
  total_funds DECIMAL(10,2),
  last_updated DATETIME
);

INSERT INTO fund_bank (id, total_funds, last_updated) VALUES (1, 0.00, NOW());
