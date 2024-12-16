<?php
session_start(); // Start a session to store user data after login

// Database connection details
$servername = "localhost";
$username = "admin";
$password = "Mm3329788$";
$dbname = "Bettergrassweb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data and sanitize inputs
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? ''; // Assume password is always present, handle validation later.

// Input validation: Check if email and password are not empty
if (empty($email) || empty($password)) {
    echo "Please enter both email and password.";
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format.";
    exit;
}

// Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("SELECT PRO_ID, PRO_PASS, SUBSCRIPTION_STATUS FROM PROVIDERS WHERE PRO_EMAIL = ?");
if ($stmt === false) {
    echo "Error preparing statement: " . $conn->error;
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

// Check if the email exists in the database
if ($stmt->num_rows > 0) {
    // Bind the result to variables
    $stmt->bind_result($PRO_ID, $hashed_password, $subscription_status);
    $stmt->fetch();

    // Verify the password
    if (password_verify($password, $hashed_password)) {
        // Check subscription status
        if (strtolower($subscription_status) === 'active') {
            // Subscription is active, allow login
            $_SESSION['PRO_ID'] = $PRO_ID; // Store provider ID in session
            $_SESSION['PRO_EMAIL'] = $email; // Store email in session
            header("Location: ../Pro_profile/Provider_profile.php"); // Redirect to dashboard
            exit();
        } else {
            // Subscription is inactive
            echo "Your subscription is not active. Please renew your subscription to log in.";
        }
    } else {
        // Incorrect password
        echo "Invalid password.";
    }
} else {
    // Email not found in the database
    echo "No account found with that email address.";
}

// Close the connection
$stmt->close();
$conn->close();
?>


