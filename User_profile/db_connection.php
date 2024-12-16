<?php
// db_connection.php

// Database connection settings
$servername = "localhost";  // Typically "localhost" for local environments
$username = "admin";         // Your MySQL username (usually "root" on MAMP)
$password = "Mm3329788$";             // Your MySQL password (usually "" on MAMP)
$dbname = "Bettergrassweb";    // The name of your database (replace with your actual DB name)

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);  // Terminate if connection fails
}
?>



