<?php
// Initialize the session
session_start();

// Store a logout message if needed
$_SESSION["logout_message"] = "You have been successfully logged out.";

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Get the base URL for absolute redirection
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host . '/College-Project/';

// Redirect to home page with absolute URL
header("Location: " . $baseUrl);
exit;
