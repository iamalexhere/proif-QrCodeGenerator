-- Create database
CREATE DATABASE IF NOT EXISTS qrcode_db;
USE qrcode_db;

-- Create links table
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_url VARCHAR(10) NOT NULL UNIQUE,
    custom_url VARCHAR(255),
    logo_path VARCHAR(500),
    qr_color VARCHAR(7) DEFAULT '#000000',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for better performance
CREATE INDEX idx_short_url ON links(short_url);
CREATE INDEX idx_custom_url ON links(custom_url);
CREATE INDEX idx_created_at ON links(created_at);

ALTER TABLE links ADD status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER qr_color;
ALTER TABLE links ADD qr_image LONGBLOB NULL;
ALTER TABLE links ADD deleted_at TIMESTAMP NULL AFTER status;

CREATE TABLE clicks (
  id int(11) NOT NULL AUTO_INCREMENT,
  link_id int(11) NOT NULL,
  click_time timestamp NOT NULL DEFAULT current_timestamp(),
  ip_address varchar(45) DEFAULT NULL,
  user_agent text DEFAULT NULL,
  country varchar(100) DEFAULT NULL,
  city varchar(100) DEFAULT NULL,
  device_type varchar(50) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY link_id (link_id),
  CONSTRAINT clicks_ibfk_1 FOREIGN KEY (link_id) REFERENCES links (id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255),
    picture TEXT,
    username VARCHAR(50) NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    plan VARCHAR(20) NOT NULL DEFAULT 'free',
    plan_expires_at DATETIME NULL,
    trial_ends_at DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_google_id (google_id),
    INDEX idx_email (email)
);

ALTER TABLE links
ADD COLUMN user_id INT NULL AFTER id,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE link_analytics_summary (
  link_id INT PRIMARY KEY,
  total_clicks INT DEFAULT 0,
  today_clicks INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
);

-- User monthly quota tracking
CREATE TABLE user_quotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    qr_codes_created INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_month (user_id, month_year),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IDR',
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending',
    transaction_id VARCHAR(255) UNIQUE,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_status (payment_status)
);

DELIMITER $$

CREATE PROCEDURE update_link_analytics(IN p_link_id INT)
BEGIN
  DECLARE total INT DEFAULT 0;
  DECLARE today INT DEFAULT 0;

  SELECT COUNT(*) INTO total FROM clicks WHERE link_id = p_link_id;
  SELECT COUNT(*) INTO today FROM clicks WHERE link_id = p_link_id AND DATE(click_time) = CURDATE();

  INSERT INTO link_analytics_summary (link_id, total_clicks, today_clicks)
  VALUES (p_link_id, total, today)
  ON DUPLICATE KEY UPDATE
    total_clicks = total,
    today_clicks = today,
    last_updated = NOW();
END $$

DELIMITER ;