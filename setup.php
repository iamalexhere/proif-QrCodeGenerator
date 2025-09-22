<?php
/**
 * Simple setup script for QR Code Generator
 */

echo "=== QR Code Generator Setup ===\n\n";

// Check if .env exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "Creating .env file from .env.example...\n";
    if (copy(__DIR__ . '/.env.example', __DIR__ . '/.env')) {
        echo "✓ .env file created successfully!\n";
        echo "Please edit .env file with your configuration.\n\n";
    } else {
        echo "✗ Failed to create .env file\n";
        exit(1);
    }
} else {
    echo "✓ .env file already exists\n\n";
}

// Test database connection
echo "Testing database connection...\n";
try {
    require_once __DIR__ . '/config/Config.php';
    require_once __DIR__ . '/classes/Database.php';
    
    $db = Database::getInstance();
    echo "✓ Database connection successful!\n\n";
    
    // Check if tables exist
    $conn = $db->getConnection();
    $result = $conn->query("SHOW TABLES LIKE 'links'");
    
    if ($result->num_rows > 0) {
        echo "✓ Links table already exists\n";
    } else {
        echo "⚠ Links table does not exist. Please run database.sql\n";
        echo "Example: mysql -u root -p < database.sql\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your .env database configuration.\n\n";
}

// Create uploads directory
$uploadsDir = __DIR__ . '/public/uploads';
if (!is_dir($uploadsDir)) {
    echo "Creating uploads directory...\n";
    if (mkdir($uploadsDir, 0755, true)) {
        echo "✓ Uploads directory created\n";
    } else {
        echo "✗ Failed to create uploads directory\n";
    }
} else {
    echo "✓ Uploads directory exists\n";
}

echo "\n=== Setup Complete ===\n";
echo "You can now access your QR Code Generator!\n";
echo "Make sure to:\n";
echo "1. Configure your .env file\n";
echo "2. Import database.sql to your MySQL database\n";
echo "3. Set proper permissions for uploads directory\n";