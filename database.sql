-- Create database
CREATE DATABASE IF NOT EXISTS qrcode_db;
USE qrcode_db;

-- Create links table
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_url VARCHAR(255),
    custom_url VARCHAR(255),
    logo_path VARCHAR(500),
    qr_color VARCHAR(7) DEFAULT '#000000',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for better performance
CREATE INDEX idx_short_url ON links(short_url);
CREATE INDEX idx_custom_url ON links(custom_url);
CREATE INDEX idx_created_at ON links(created_at);