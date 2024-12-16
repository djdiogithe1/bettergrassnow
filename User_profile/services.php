<?php
// Start session and verify login
session_start();
if (!isset($_SESSION['CUST_ID'])) {
    header("Location: ../User_login/login.php");
    exit();
}

// Include database connection
include_once '../User_profile/db_connection.php';

// Get the user ID from session
$user_id = $_SESSION['CUST_ID'];

// Fetch customer's image, city, state, zip, and address
$sql_city = "SELECT CUST_IMAGE, CUST_CITY, CUST_STATE, CUST_ZIP, CUST_ADDRESS FROM CUSTOMERS WHERE CUST_ID = ?";
$stmt_city = $conn->prepare($sql_city);
$stmt_city->bind_param("i", $user_id);
$stmt_city->execute();
$result_city = $stmt_city->get_result();
$stmt_city->close();

if ($result_city->num_rows > 0) {
    $row_city = $result_city->fetch_assoc();
    $user_image = $row_city['CUST_IMAGE'];
    $user_city = $row_city['CUST_CITY'];
    $user_state = $row_city['CUST_STATE'];
    $user_zip = $row_city['CUST_ZIP'];
    $user_address = $row_city['CUST_ADDRESS'];
} else {
    echo "<p class='alert alert-danger'>Customer data not found. Please log in again.</p>";
    exit();
}

// Fetch providers based on user's city and active subscription
$sql_providers = "SELECT PRO_ID, PRO_FNAME, PRO_LNAME, PRO_IMAGE, PRO_CITY 
                  FROM PROVIDERS 
                  WHERE PRO_CITY = ? AND SUBSCRIPTION_STATUS = 'active'";
$stmt_providers = $conn->prepare($sql_providers);
$stmt_providers->bind_param("s", $user_city);
$stmt_providers->execute();
$providers_result = $stmt_providers->get_result();
$stmt_providers->close();


// Handle service order placement
if (isset($_POST['place_order'])) {
    $service_type = $_POST['service_type'];
    $service_frequency = $_POST['service_frequency'];
    $service_date = $_POST['service_date'];
    $service_address = $_POST['service_address'];
    $zelle_email = $_POST['zelle_email'] ?? null; // Default null if Zelle email isn't provided
    $provider_ids = $_POST['provider_ids'] ?? []; // Array of selected provider IDs
    $status = 'pending';

    // Insert order for each selected provider
    foreach ($provider_ids as $provider_id) {
        // Insert the order
        $sql_insert = "INSERT INTO service_orders 
            (CUST_ID, SERVICE_TYPE, SERVICE_FREQUENCY, PRO_ID, PAYMENT_METHOD, ZELLE_EMAIL, SERVICE_DATE, SERVICE_ADDRESS, STATUS, SERVICE_CITY, SERVICE_STATE, SERVICE_ZIP, CUST_IMAGE) 
            VALUES (?, ?, ?, ?, 'zelle', ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Prepare statement and bind parameters
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param(
            "issssssssssb", 
            $user_id,
            $service_type,
            $service_frequency,
            $provider_id,
            $zelle_email,
            $service_date,
            $service_address,
            $status,
            $user_city,
            $user_state,
            $user_zip,
            $user_image
        );

        if ($stmt_insert->execute()) {
            echo "<p class='alert alert-success'>Service order placed successfully with Provider ID $provider_id!</p>";

            // Insert a message to the provider
            $message_text = "There is a new requested $service_type ($service_frequency) at $service_address, $user_city, $user_state, $user_zip.";
            $sql_message = "INSERT INTO messages (PRO_ID, CUST_ID, MESSAGE_TEXT, STATUS, DIRECTION) VALUES (?, ?, ?, 'unread', 'incoming')";
            $stmt_message = $conn->prepare($sql_message);
            $stmt_message->bind_param("iis", $provider_id, $user_id, $message_text);
            $stmt_message->execute();
            $stmt_message->close();
        } else {
            echo "<p class='alert alert-danger'>Failed to place the order for Provider ID $provider_id. Please try again.</p>";
        }
        $stmt_insert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place a Service Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900&display=swap" rel="stylesheet">

    <style>
        /* Dark Mode Base Styles */
        body {
            background-image: url("../assets/img/grass.jpg");
            color: #e0e0e0;
            font-family: 'Lato', sans-serif;
        }
        .card {
    		background-color: #1c1c1c;
    		color: #fff;
    		border: none;
    		margin: 30px auto;
    		padding: 20px;
    		border-radius: 8px;
    		box-shadow: 0 0 20px #00bcd4;
    		transition: box-shadow 0.3s ease;
    		max-width: 550px; /* Reduced form width */
		}
        .card:hover {
            box-shadow: 0 0 30px #00bcd4;
        }
        .form-group label {
            color: #00bcd4;
            font-weight: bold;
        }
        .form-control {
            background-color: #333;
            color: #00bcd4;
            border: 1px solid #555;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
        }
        .form-control:focus {
            background-color: #444;
            border-color: #00bcd4;
        }
        .btn-primary {
            background-color: #00bcd4;
            border: none;
            font-size: 16px;
            padding: 12px 20px;
            width: 100%; /* Full-width button */
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #008c99;
        }

        /* Navigation Bar Styling */
        nav {
            background-color: #1c1c1c;
            padding: 10px 0;
        }
        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav .logo {
            font-size: 24px;
            color: #00bcd4;
            text-decoration: none;
        }
        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        nav ul li {
            margin-left: 20px;
        }
        nav ul li a {
            color: #e0e0e0;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        nav ul li a:hover {
            color: #00bcd4;
        }
        nav ul li a.active {
            color: #00bcd4;
            font-weight: bold;
        }

        /* Responsive Navigation */
        .menu-toggle {
            display: none;
            cursor: pointer;
        }
        .menu-toggle i {
            font-size: 24px;
            color: #00bcd4;
        }
        @media (max-width: 768px) {
            nav ul {
                display: none;
                flex-direction: column;
                background-color: rgba(28, 28, 28, 0.9); /* Transparent background */
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                padding: 10px 0;
            }
            nav ul.show {
                display: flex;
            }
            nav ul li {
                margin: 10px 0;
                text-align: center;
            }
            .menu-toggle {
                display: block;
            }
        }

        /* Provider List Styling */
        .provider-list {
            list-style: none;
            padding: 0;
        }
        .provider-list li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .provider-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #00bcd4;
        }
        .provider-info {
            color: #00bcd4;
        }
        footer {
    		background-color: #1c1c1c; /* Same color as the form */
    		color: #00bcd4; /* Aqua text color */
    		text-align: center; /* Center align content */
    		padding: 15px 0;
    		position: relative;
    		bottom: 0;
    		width: 100%;
    		font-weight: bold;
    		box-shadow: 0 0 10px #00bcd4; /* Subtle glow like the form */
		}

    </style>

    <script>
        // Hamburger menu functionality
        document.addEventListener("DOMContentLoaded", function() {
            const toggle = document.querySelector(".menu-toggle");
            const navLinks = document.querySelector("nav ul");

            toggle.addEventListener("click", () => {
                navLinks.classList.toggle("show");
            });
        });
    </script>
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <a href="../index.html" class="logo">BetterGrassNow</a>
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <ul>
                    <li><a href="../User_profile/home.php">HOME</a></li>
                    <li><a href="../User_profile/inbox.php">INBOX</a></li>
                    <li><a href="../User_profile/services.php">REQUEST A QUOTE</a></li>
                    <li><a href="../User_profile/service_requests.php">SERVICE LOG</a></li>
                    <li><a href="logout.php">SIGN OUT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <h3 class="text-center">Place a Service Order</h3>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="service_type">Service Type</label>
                    <select name="service_type" id="service_type" class="form-control" required>
                        <option value="lawn_mowing">Lawn Mowing</option>
                        <option value="hedge_trimming">Hedge Trimming</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="service_frequency">Service Frequency</label>
                    <select name="service_frequency" id="service_frequency" class="form-control">
                        <option value="once">Once</option>
                        <option value="weekly">Weekly</option>
                        <option value="bi-weekly">Bi-weekly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="service_date">Service Date</label>
                    <input type="date" id="service_date" name="service_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="service_address">Service Address</label>
                    <input type="text" id="service_address" name="service_address" class="form-control" required>
                    <p>*Include street address only, services are based on your city. (Example: 1234 2ST ST.)</p>
                </div>
                

                <h4>Select Providers</h4>
                <ul class="provider-list">
                    <?php
                    while ($provider = $providers_result->fetch_assoc()) {
                        echo '<li>';
                        echo '<input type="checkbox" name="provider_ids[]" value="' . $provider['PRO_ID'] . '">';
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($provider['PRO_IMAGE']) . '" alt="Provider Image" class="provider-image">';
                        echo '<div class="provider-info">';
                        echo '<span>' . $provider['PRO_FNAME'] . ' ' . $provider['PRO_LNAME'] . '</span><br>';
                        echo '<span>' . $provider['PRO_CITY'] . '</span>';
                        echo '</div>';
                        echo '</li>';
                    }
                    ?>
                </ul>
                <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
            </form>
        </div>
    </div>

	<div class="footer">
            <div class="footer-content">
                <footer>
                    2023-2024 BetterGrassNow&copy;
                            <br><a href="../contact/contact.html">Contact us</a>
        					<br><a href="../sitemap.php">Sitemap</a>
        					<br><a href="../terms.php">Terms and Conditions</a> 
                    </footer>
            </div>
        </div>
</body>
</html>
