-- Select the database
USE musician_db;

-- Drop the existing table if you want to start fresh (BE CAREFUL with this in production!)
-- DROP TABLE IF EXISTS users;

-- Create the users table with all required columns
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    country VARCHAR(100),
    phone VARCHAR(20),
    verification_token VARCHAR(255),
    verification_expires TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(50),
    reset_pin VARCHAR(6) DEFAULT NULL,
    reset_pin_expires TIMESTAMP NULL DEFAULT NULL,
    last_pin_request TIMESTAMP NULL DEFAULT NULL,
    reset_attempts INT DEFAULT 0
    );

-- Add any missing columns if the table already exists
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS verification_expires TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS created_by VARCHAR(50),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_by VARCHAR(50),
    ADD COLUMN IF NOT EXISTS reset_pin VARCHAR(6) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reset_pin_expires TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS last_pin_request TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reset_attempts INT DEFAULT 0;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_verification_token ON users(verification_token);
CREATE INDEX IF NOT EXISTS idx_reset_pin ON users(reset_pin);

-- Create the admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_online BOOLEAN DEFAULT FALSE
    );

-- Add online status column to users table if not exists
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_online BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS user_type ENUM('user', 'admin') DEFAULT 'user';

-- Drop the existing musicians table if you want to start fresh (BE CAREFUL with this in production!)
DROP TABLE IF EXISTS musicians;

-- Create the musicians table
CREATE TABLE musicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    genre VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add profile_picture column to users table if not exists
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL;