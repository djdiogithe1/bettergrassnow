<?php
// Assuming you've already started the session and validated login
session_start();
if (!isset($_SESSION['CUST_ID'])) {
    header("Location: ../User_login/login.php");
    exit();
}

// Include the database connection
include_once '../User_profile/db_connection.php';

// Get the form data
$service_type = $_POST['SERVICE_TYPE'];  // Weekly, Bi-Weekly, Monthly
$provider_id = $_POST['PROVIDER'];  // Selected Provider ID
$payment_method = $_POST['PAYMENT_METHOD'];  // Zelle
$zelle_email = $_POST['ZELLE_EMAIL'];  // Zelle email

// Here you can insert this data into your database or process it
$user_id = $_SESSION['CUST_ID']; // User ID from session

$sql = "INSERT INTO service_orders (CUST_ID, SERVICE_TYPE, PRO_ID, payment_method, zelle_email) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $cust_id, $service_type, $provider_id, $payment_method, $zelle_email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Service order placed successfully!";
} else {
    echo "There was an error placing your order.";
}

$stmt->close();
$conn->close();
?>
