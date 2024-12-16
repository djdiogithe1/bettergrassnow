<?php
// Start the session and check if the provider is logged in
session_start();
if (!isset($_SESSION['PRO_ID'])) {
    header("Location: ../Pro_profile/home.php");
    exit();
}

// Include database connection
include_once '../Pro_profile/db_connection.php';

// Get provider_id from session
$provider_id = $_SESSION['PRO_ID'];

// Fetch provider information (to display in the profile section)
$sql = "SELECT * FROM PROVIDERS WHERE PRO_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $username = $row['PRO_FNAME'] . " " . $row['PRO_LNAME'];
    $user_email = $row['PRO_EMAIL'] ?? ''; 
    $user_address = $row['PRO_ADDRESS'] ?? 'N/A'; 
    $user_city = $row['PRO_CITY'] ?? 'N/A';
    $user_state = $row['PRO_STATE'] ?? 'N/A';
    $user_zip = $row['PRO_ZIP'] ?? 'N/A';
} else {
    $username = "Unknown Provider";
}

// Fetch earnings and refunds based on `pro_id`
// Weekly earnings
$sql_week_earnings = "
    SELECT SUM(total_price) AS week_earnings
    FROM service_orders
    WHERE PRO_ID = ? AND status = 'completed'
    AND YEARWEEK(service_date, 1) = YEARWEEK(CURDATE(), 1)";
$stmt_week = $conn->prepare($sql_week_earnings);
$stmt_week->bind_param("i", $provider_id);
$stmt_week->execute();
$result_week = $stmt_week->get_result();
$week_earnings = $result_week->fetch_assoc()['week_earnings'] ?? 0;

// Year-to-date earnings
$sql_ytd_earnings = "
    SELECT SUM(total_price) AS ytd_earnings
    FROM service_orders
    WHERE PRO_ID = ? AND STATUS = 'completed'
    AND YEAR(SERVICE_DATE) = YEAR(CURDATE())";
$stmt_ytd = $conn->prepare($sql_ytd_earnings);
$stmt_ytd->bind_param("i", $provider_id);
$stmt_ytd->execute();
$result_ytd = $stmt_ytd->get_result();
$ytd_earnings = $result_ytd->fetch_assoc()['ytd_earnings'] ?? 0;

// Total refunds
$sql_refunds = "
    SELECT SUM(TOTAL_PRICE) AS total_refunds
    FROM service_orders
    WHERE PRO_ID = ? AND STATUS = 'refunded'";
$stmt_refunds = $conn->prepare($sql_refunds);
$stmt_refunds->bind_param("i", $provider_id);
$stmt_refunds->execute();
$result_refunds = $stmt_refunds->get_result();
$total_refunds = $result_refunds->fetch_assoc()['total_refunds'] ?? 0;

// Close database connections
$stmt->close();
$stmt_week->close();
$stmt_ytd->close();
$stmt_refunds->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Provider Funds Overview">
    <meta name="keywords" content="Provider, Funds, Earnings, Refunds">
    <meta name="author" content="Mr Michael T Finley">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetterGrassNow - Provider Funds</title>
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
                    <li><a href="../Pro_profile/provider_profile.php">HOME</a></li>
                	<li><a href="../Pro_profile/inbox.php">INBOX</a></li> 
                    <li><a href="../Pro_profile/upload_image.php">JOB IMAGE UPLOADS</a></li>
                    <li><a href="../Pro_profile/job_list.php">SERVICE REQUESTS</a></li>
                    <li><a href="../Pro_profile/funds.php">FUNDS</a></li>
                    <li><a href="logout.php">SIGN OUT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <section>
    		<div class="card">
            <h1>Welcome <?php echo htmlspecialchars($username); ?>,<br> Here's your earnings summary.</h1>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-lg-4">
                        <!-- Weekly Earnings -->
                       
                        <div class="card">
                            <div class="card-header bg-transparent border-0">
                                <h3 class="mb-0">This Week's Earnings</h3>
                            </div>
                            <div class="card-body pt-0">
                                <h4>$<?php echo number_format($week_earnings, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <!-- Year-to-Date Earnings -->
                        	<div class="card">
                            	<div class="card-header bg-transparent border-0">
                                	<h3 class="mb-0">Year-to-Date Earnings</h3>
                            	</div>
                            <div class="card-body pt-0">
                                <h4>$<?php echo number_format($ytd_earnings, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Total Refunds -->
                       	<div class="card">
                            <div class="card-header bg-transparent border-0">
                                <h3 class="mb-0">Total Refunds</h3>
                            </div>
                            <div class="card-body pt-0">
                                <h4>$<?php echo number_format($total_refunds, 2); ?></h4>
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


