<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "careercompass";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'hr', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Create career_assessments table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS career_assessments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    career_interest VARCHAR(255) NOT NULL,
    results TEXT NOT NULL, -- Store AI results (can be long)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) !== TRUE) {
    die("Error creating career_assessments table: " . $conn->error);
}

// Create career_roadmaps table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS career_roadmaps (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    career_goal VARCHAR(255) NOT NULL,
    roadmap_data JSON NOT NULL, -- Store the steps array as JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) !== TRUE) {
    die("Error creating career_roadmaps table: " . $conn->error);
}
?>
