<?php
// Start the session
session_start();

// Destroy the session to log the user out
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

// Redirect to the homepage after logging out
header("Location: ../index.html");
exit();
?>