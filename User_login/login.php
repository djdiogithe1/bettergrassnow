<?php
// Start the session
session_start();

// Database connection settings
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'admin';
$password = getenv('DB_PASSWORD') ?: 'Mm3329788$';
$dbname = getenv('DB_NAME') ?: 'Bettergrassweb';

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Prepare the query to select the customer by email
    $query = "SELECT CUST_ID, CUST_EMAIL, CUST_PASS, CUST_FNAME, CUST_LNAME, CUST_ADDRESS, CUST_CITY, CUST_STATE, CUST_ZIP, PRO_ID, CUST_IMAGE FROM CUSTOMERS WHERE CUST_EMAIL = ?";
    
    if ($stmt = $conn->prepare($query)) {
        // Bind the email parameter to the query
        $stmt->bind_param("s", $email);
        
        // Execute the query
        $stmt->execute();
        
        // Store the result
        $stmt->store_result();
        
        // Check if any user with the provided email exists
        if ($stmt->num_rows > 0) {
            // Bind the result to variables
            $stmt->bind_result($CUST_ID, $CUST_EMAIL, $CUST_PASS, $CUST_FNAME, $CUST_LNAME, $CUST_ADDRESS, $CUST_CITY, $CUST_STATE, $CUST_ZIP, $PRO_ID, $CUST_IMAGE);
            
            // Fetch the user data
            $stmt->fetch();
            
            // Verify the password
            if (password_verify($password, $CUST_PASS)) {
                // If password is correct, store user data in the session
                $_SESSION['CUST_ID'] = $CUST_ID;
                $_SESSION['CUST_EMAIL'] = $CUST_EMAIL;
                $_SESSION['CUST_FNAME'] = $CUST_FNAME;
                $_SESSION['CUST_LNAME'] = $CUST_LNAME;
                
                // Redirect to the user's home page
                header("Location: ../User_profile/home.php");
                exit();
            } else {
                // If the password is incorrect, redirect back with error
                header("Location: login.html?error=Invalid%20password.");
                exit();
            }
        } else {
            // If no user found with the provided email, redirect back with error
            header("Location: login.html?error=No%20user%20found%20with%20that%20email.");
            exit();
        }
        
        // Close the prepared statement
        $stmt->close();
    } else {
        // If the statement could not be prepared, show an error message
        header("Location: login.html?error=Error%20preparing%20the%20query.");
        exit();
    }
    
    // Close the database connection
    $conn->close();
}
?>

