<?php
// Start session
session_start();

// Check if form is submitted
if (isset($_POST['submit'])) {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirmation = $_POST['password_confirmation'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip = trim($_POST['zip']);
    $social = isset($_POST['social']) ? trim($_POST['social']) : null; // Optional
    $dob = isset($_POST['dob']) ? $_POST['dob'] : null; // Optional
    $stripe = trim($_POST['stripe']);
    $background_check = isset($_POST['background_check']) ? 1 : 0;

    // Validate background check agreement
    if (!$background_check) {
        echo "You must agree to a background check.";
        exit;
    }

    // Validate image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_temp = $_FILES['image']['tmp_name'];
        $image_data = file_get_contents($image_temp);
    } else {
        echo "Error uploading image. Please ensure a valid image file is selected.";
        exit;
    }

    // Basic field validations
    if (
        empty($email) || empty($password) || empty($password_confirmation) || 
        empty($fname) || empty($lname) || empty($address) || empty($city) || 
        empty($state) || empty($zip)
    ) {
        echo "All required fields must be filled.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }

    // Validate password confirmation
    if ($password !== $password_confirmation) {
        echo "Passwords do not match!";
        exit;
    }

    // Password strength validation
    if (strlen($password) < 8 || !preg_match('/\d/', $password) || !preg_match('/[A-Za-z]/', $password)) {
        echo "Password must be at least 8 characters long and include both letters and numbers.";
        exit;
    }

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Database credentials
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = 'Mm3329788$';
    $db_name = 'Bettergrassweb';

    // Create a database connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check the connection
    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    } else {
        // Prepare SQL query to insert new provider data
        $stmt = $conn->prepare("INSERT INTO PROVIDERS (
                                    PRO_EMAIL, PRO_PASS, PRO_FNAME, PRO_LNAME, PRO_ADDRESS, 
                                    PRO_CITY, PRO_STATE, PRO_ZIP, CUST_ID, PRO_SOCIAL, 
                                    PRO_DOB, PRO_STRIPE, PRO_PASSWORD_CONFIRMATION, PRO_IMAGE, BACKGROUND_CHECK
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            die('Error preparing statement: ' . $conn->error);
        }

        // Set default values for optional fields
        $cust_id = -99; // Default value as per table structure
        $password_confirmation_hashed = password_hash($password_confirmation, PASSWORD_DEFAULT);

        // Bind parameters and execute the statement
        $stmt->bind_param(
            "sssssssisssssbi", 
            $email, 
            $hashed_password, 
            $fname, 
            $lname, 
            $address, 
            $city, 
            $state, 
            $zip, 
            $cust_id, 
            $social, 
            $dob, 
            $stripe,
            $password_confirmation_hashed,
            $image_data, 
            $background_check
        );

        // Send LONGBLOB data (PRO_IMAGE)
        $stmt->send_long_data(13, $image_data);

        // Execute the statement
        if ($stmt->execute()) {
            // Successful registration - Redirect to Stripe subscription payment
            $_SESSION['success_message'] = "Registration Successful! Please complete your subscription payment.";

            // Stripe payment link with success redirect
            $stripe_payment_url = "https://buy.stripe.com/00g3cRcY9c4laGcaEG?success_url=" . urlencode("http://yourdomain.com/Pro_login/Pro_login.html");

            header("Location: $stripe_payment_url");
            exit;
        } else {
            // Error during insertion
            echo "Error: " . $stmt->error;
        }

        // Close the prepared statement and database connection
        $stmt->close();
        $conn->close();
    }
}
?>

