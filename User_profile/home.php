<?php
// Start the session and check if the user is logged in
session_start();
if (!isset($_SESSION['CUST_ID'])) {
    header("Location: ../User_login/login.php");
    exit();
}

// Include database connection
include_once '../User_profile/db_connection.php';

// Increase PHP memory limit
ini_set('memory_limit', '256M'); // Increase memory limit to 256MB

// Get user_id from session
$user_id = $_SESSION['CUST_ID'];

// Prepare and execute the SQL query for customer details
$sql = "SELECT * FROM CUSTOMERS WHERE CUST_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if a row was returned
if ($result->num_rows > 0) {
    // Fetch the data
    $row = $result->fetch_assoc();
    $username = $row['CUST_FNAME'] . " " . $row['CUST_LNAME']; // Full name
    $user_email = $row['CUST_EMAIL'] ?? ''; 
    $user_address = $row['CUST_ADDRESS'] ?? 'N/A'; 
    $user_city = $row['CUST_CITY'] ?? 'N/A';
    $user_state = $row['CUST_STATE'] ?? 'N/A';
    $user_zip = $row['CUST_ZIP'] ?? 'N/A';
    
    // Check if a profile image exists
    if ($row['CUST_IMAGE']) {
        $imageSrc = 'data:image/jpeg;base64,' . base64_encode($row['CUST_IMAGE']); // Convert image to base64
    } else {
        $imageSrc = '../assets/img/default_profile.png'; // Default image if none exists
    }
}

// Get current hour and set the welcome message based on time of day
$hour = date("H"); // Get the current hour
if ($hour >= 5 && $hour < 12) {
    $welcomeMessage = "Good Morning";
} elseif ($hour >= 12 && $hour < 18) {
    $welcomeMessage = "Good Afternoon";
} else {
    $welcomeMessage = "Good Evening";
}

// Get images from the service_images table using ORDER_ID (with pagination)
$imagesQuery = "SELECT * FROM service_orders WHERE CUST_ID = ? ORDER BY ORDER_ID DESC LIMIT 10"; // Fetch only 10 records
$imageStmt = $conn->prepare($imagesQuery);
$imageStmt->bind_param("i", $user_id);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();

// Fetch completed service orders for the customer, including the service date
$orderQuery = "SELECT ORDER_ID, SERVICE_DATE FROM service_orders WHERE CUST_ID = ? AND STATUS = 'completed' ORDER BY ORDER_ID DESC";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("i", $user_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Internet Marketing, Marketing, Promotions, Business Advertisement, Advertisement">
    <meta name="keywords" content="Marketing, Internet Marketing, Email, Internet, Local Advertisement, Business Solutions, Advertisement">
    <meta name="author" content="Mr Michael T Finley">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetterGrassNow</title>
    <link rel="icon" type="image/png" href="../assets/img/Lawnmower.png">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            background-image: url("../assets/img/grass.jpg");
            color: #e0e0e0;
            font-family: 'Lato', sans-serif;
        }
        .card {
            background-color: #000000;
            color: #fff;
            border: none;
            margin: 30px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px #00bcd4;
            transition: box-shadow 0.3s ease;
            max-width: 800px;
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
            width: 100%;
            border-radius: 5px;
        }
        .form-control:focus {
            background-color: #444;
            border-color: #00bcd4;
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
        .table-bordered {
            border: 1px solid #444;
        }
        .table th, .table td {
            color: #00bcd4;
        }
        .profile {
            box-shadow: 0 0 25px #00bcd4;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        nav {
            background-color: #1c1c1c;
            padding: 15px 0;
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
            padding: 0;
            margin: 0;
            display: flex;
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
        .provider-info span {
            font-weight: bold;
        }
        .provider-check {
            margin-bottom: 10px;
        }
        .provider-check input[type="checkbox"] {
            margin-right: 10px;
        }
		/* Updated styles for image gallery */
		.image-gallery {
    		display: flex; /* Use flexbox to arrange images horizontally */
    		position: absolute;
    		top: 50px;
    		left: 50%;
    		transform: translateX(-50%);
    		background-color: rgba(0, 0, 0, 0.8);
    		padding: 20px;
    		border-radius: 8px;
    		box-shadow: 0 0 20px #00bcd4;
    		z-index: 100;
    		display: none;
    		justify-content: center; /* Center the images horizontally */
		}

		.image-gallery img {
    		max-width: 100px;  /* Set initial size for small images */
    		margin: 5px;
    		border-radius: 8px;
    		object-fit: cover;
    		transition: transform 0.3s ease;  /* Smooth transition for zoom effect */
		}

		/* Enlarge the image when hovered over */
		.image-gallery img:hover {
    		transform: scale(1.5);  /* Enlarge the image */
		}



		.completed-orders li:hover .image-gallery {
    		display: block;
		}

        .completed-orders li:hover .image-gallery {
            display: block;
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

    <section>
    	<div class="card">
        <h1 style="color: #00bcd4;"><?php echo htmlspecialchars($welcomeMessage); ?>, <?php echo htmlspecialchars($username); ?><br> we're glad you're here!</h1>
        </div>
        <br>
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-transparent border-0">
                            <h3 class="mb-0"><i class="fa fa-user"></i> Service info</h3>
                        </div>
                        <div class="card-body">
                            <!-- Display Profile Image in Service Info Section -->
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Profile Image" class="profile-image">
                            <table class="table table-transparent border-0">
                                <tr>
                                    <th style="color: #00bcd4;">Last date mowed:</th>
                                    <td style="color: #00bcd4;">10/15/2024</td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Service frequency:</th>
                                    <td style="color: #00bcd4;">Bi-weekly</td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Service Day:</th>
                                    <td style="color: #00bcd4;">Tuesday</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-transparent border-0">
                            <h3 class="mb-0"><i class="fa fa-user"></i> Personal Information</h3>
                        </div>
                        <div class="card-body pt-0">
                            <table class="table transparent border-0">
                                <tr>
                                    <th style="color: #00bcd4;">Name:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($username); ?></td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Email:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($user_email); ?></td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Address:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($user_address . ", " . $user_city . ", " . $user_state . ", " . $user_zip); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header bg-transparent border-0">
                            <h3 class="mb-0"><i class="fa fa-users"></i> Past Service Orders</h3>
                        </div>
                        <div class="card-body">
                            <div class="completed-orders">
                                <?php
                                while ($order = $orderResult->fetch_assoc()) {
                                    $order_id = $order['ORDER_ID'];
                                    $service_date = $order['SERVICE_DATE'];
                                    $imageQuery = "SELECT BEFORE_FRONT, AFTER_FRONT, BEFORE_BACK, AFTER_BACK FROM service_orders WHERE ORDER_ID = ?";
                                    $imageStmt = $conn->prepare($imageQuery);
                                    $imageStmt->bind_param("i", $order_id);
                                    $imageStmt->execute();
                                    $imageResult = $imageStmt->get_result();
                                    $images = $imageResult->fetch_assoc();

                                    // Check if image data exists before base64 encoding
                                    $beforeFront = $images['BEFORE_FRONT'] ? 'data:image/jpeg;base64,' . base64_encode($images['BEFORE_FRONT']) : '';
                                    $afterFront = $images['AFTER_FRONT'] ? 'data:image/jpeg;base64,' . base64_encode($images['AFTER_FRONT']) : '';
                                    $beforeBack = $images['BEFORE_BACK'] ? 'data:image/jpeg;base64,' . base64_encode($images['BEFORE_BACK']) : '';
                                    $afterBack = $images['AFTER_BACK'] ? 'data:image/jpeg;base64,' . base64_encode($images['AFTER_BACK']) : '';

                                    echo '<li style="color: #00bcd4;" data-order-id="' . $order_id . '">Order ID: ' . $order_id . ' - Service Date: ' . $service_date . '
                                        <div class="image-gallery">';
                                    if ($beforeFront) {
                                        echo '<img src="' . $beforeFront . '" alt="Before Front">';
                                    }
                                    if ($afterFront) {
                                        echo '<img src="' . $afterFront . '" alt="After Front">';
                                    }
                                    if ($beforeBack) {
                                        echo '<img src="' . $beforeBack . '" alt="Before Back">';
                                    }
                                    if ($afterBack) {
                                        echo '<img src="' . $afterBack . '" alt="After Back">';
                                    }
                                    echo '</div>
                                    </li>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </section>
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

