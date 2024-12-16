<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables (useful for Azure App Service)
$host = getenv('DB_HOST') ?: 'localhost';  // Azure DB hostname
$username = getenv('DB_USER') ?: 'root';  // Azure DB user
$password = getenv('DB_PASSWORD') ?: 'password'; // Azure DB password
$dbname = getenv('DB_NAME') ?: 'Bettergrassweb'; // Azure DB name

// Connect to the Azure MySQL database
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $email = mysqli_real_escape_string($conn, $_POST['CUST_EMAIL']);
    $password = mysqli_real_escape_string($conn, $_POST['CUST_PASS']);
    $password_confirmation = mysqli_real_escape_string($conn, $_POST['CUST_PASSWORD_CONFIRMATION']);
    $fname = mysqli_real_escape_string($conn, $_POST['CUST_FNAME']);
    $lname = mysqli_real_escape_string($conn, $_POST['CUST_LNAME']);
    $address = mysqli_real_escape_string($conn, $_POST['CUST_ADDRESS']);
    $city = mysqli_real_escape_string($conn, $_POST['CUST_CITY']);
    $state = mysqli_real_escape_string($conn, $_POST['CUST_STATE']);
    $zip = mysqli_real_escape_string($conn, $_POST['CUST_ZIP']);

    // Handle the image upload
    $image = NULL;
    if (isset($_FILES['CUST_IMAGE']) && $_FILES['CUST_IMAGE']['error'] == UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['CUST_IMAGE']['tmp_name']);
    }

    // Check if passwords match
    if ($password === $password_confirmation) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL query to insert data
        $query = "INSERT INTO CUSTOMERS (CUST_EMAIL, CUST_PASS, CUST_FNAME, CUST_LNAME, CUST_ADDRESS, CUST_CITY, CUST_STATE, CUST_ZIP, CUST_IMAGE)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($query)) {
            // Bind parameters for the prepared statement
            $stmt->bind_param("sssssssss", $email, $hashed_password, $fname, $lname, $address, $city, $state, $zip, $image);

            // Execute the query
            if ($stmt->execute()) {
                // Redirect to login page upon success
                header("Location: ../User_login/login.html");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Error preparing query: " . $conn->error;
        }
    } else {
        echo "Passwords do not match.";
    }

    // Close the statement and the connection
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>









