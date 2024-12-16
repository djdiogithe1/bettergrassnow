<?php
// Start session and verify login
session_start();
if (!isset($_SESSION['CUST_ID'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include database connection
include_once '../User_profile/db_connection.php';

// Get the user ID from session
$user_id = $_SESSION['CUST_ID'];

// Fetch customer's city
$sql_city = "SELECT CUST_CITY FROM CUSTOMERS WHERE CUST_ID = ?";
$stmt_city = $conn->prepare($sql_city);
$stmt_city->bind_param("i", $user_id);
$stmt_city->execute();
$result_city = $stmt_city->get_result();
$stmt_city->close();

if ($result_city->num_rows > 0) {
    $row_city = $result_city->fetch_assoc();
    $user_city = $row_city['CUST_CITY'];
} else {
    echo "<p class='alert alert-danger'>Customer data not found. Please log in again.</p>";
    exit();
}

// Fetch existing service orders for the logged-in user
$sql_orders = "
    SELECT so.*, CONCAT(p.PRO_FNAME, ' ', p.PRO_LNAME) AS PROVIDER_NAME
    FROM service_orders AS so
    LEFT JOIN PROVIDERS AS p ON so.PRO_ID = p.PRO_ID
    WHERE so.CUST_ID = ?  -- Filter by CUST_ID
    ORDER BY so.ORDER_DATE DESC
";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id); // Bind the logged-in user ID to the query
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();
$stmt_orders->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
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
            <div class="profile py-8">
                <h1 class="text-center">Service Requests</h1>
			<div class="container">
        		<div class="card">
                <!-- Existing Service Orders -->
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="color: #00bcd4;">Provider</th>
                                <th style="color: #00bcd4;">Service Type</th>
                                <th style="color: #00bcd4;">Frequency</th>
                                <th style="color: #00bcd4;">Date</th>
                                <th style="color: #00bcd4;">Address</th>
                                <th style="color: #00bcd4;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders_result->fetch_assoc()) { ?>
                                <tr>
                                    <td style="color: #00bcd4;"><?= $order['PROVIDER_NAME']; ?></td>
                                    <td style="color: #00bcd4;"><?= $order['SERVICE_TYPE']; ?></td>
                                    <td style="color: #00bcd4;"><?= $order['SERVICE_FREQUENCY']; ?></td>
                                    <td style="color: #00bcd4;"><?= $order['SERVICE_DATE']; ?></td>
                                    <td style="color: #00bcd4;"><?= $order['SERVICE_ADDRESS']; ?></td>
                                    <td style="color: #00bcd4;"><?= ucfirst($order['STATUS']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
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

