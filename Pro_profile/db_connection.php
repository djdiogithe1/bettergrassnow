<?php
// Start session if it hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Retrieve credentials from environment variables (preferable for security)
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'admin';
$password = getenv('DB_PASSWORD') ?: 'Mm3329788$';
$dbname = getenv('DB_NAME') ?: 'Bettergrassweb';

// Create a connection to the database using mysqli
$conn = new mysqli($host, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optionally, set the character set to UTF-8 for proper character encoding
$conn->set_charset("utf8");

// You can now include this file in your other PHP files to use the database connection.
?>

