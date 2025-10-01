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