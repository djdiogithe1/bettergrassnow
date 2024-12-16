<?php
// Start the session and check if the user is logged in
session_start();
if (!isset($_SESSION['PRO_ID'])) {
    header("Location: ../User_login/login.php");
    exit();
}

// Include database connection
include_once '../User_profile/db_connection.php';

// Get provider_id from session
$provider_id = $_SESSION['PRO_ID'];

// Prepare and execute the SQL query
$sql = "SELECT * FROM PROVIDERS WHERE PRO_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if a row was returned
if ($result->num_rows > 0) {
    // Fetch the data
    $row = $result->fetch_assoc();
    $provider_name = $row['PRO_FNAME'] . " " . $row['PRO_LNAME']; // Full name
    $provider_email = $row['PRO_EMAIL'] ?? '';
    $provider_address = $row['PRO_ADDRESS'] ?? 'N/A';
    $provider_city = $row['PRO_CITY'] ?? 'N/A';
    $provider_state = $row['PRO_STATE'] ?? 'N/A';
    $provider_zip = $row['PRO_ZIP'] ?? 'N/A';
    $provider_dob = $row['PRO_DOB'] ?? 'N/A';
    $imageSrc = $row['PRO_IMAGE'] ? 'data:image/jpeg;base64,' . base64_encode($row['PRO_IMAGE']) : '../assets/img/default-profile.png'; // If no image, use default

    // Get current hour and set the welcome message based on time of day
    $hour = date("H"); // Get the current hour
    if ($hour >= 5 && $hour < 12) {
        $welcomeMessage = "Good Morning";
    } elseif ($hour >= 12 && $hour < 18) {
        $welcomeMessage = "Good Afternoon";
    } else {
        $welcomeMessage = "Good Evening";
    }
} else {
    // If no provider found, redirect to login page
    header("Location: ../User_login/login.php");
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Provider Profile">
    <meta name="keywords" content="Provider Profile, Services">
    <meta name="author" content="BetterGrassNow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetterGrassNow - Provider Profile</title>
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
        <h1 style="color: #00bcd4;"><?php echo htmlspecialchars($welcomeMessage); ?>, <?php echo htmlspecialchars($provider_name); ?><br> we're glad you're here!</h1>
        </div>
        <br>
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-transparent border-0">
                            <h3 class="mb-0"><i class="fa fa-user"></i> Provider info</h3>
                        </div>
                        <div class="card-body">
                            <!-- Display Profile Image in Provider Info Section -->
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Profile Image" class="profile-image">
                            <table class="table table-transparent border-0">
                                <tr>
                                    <th style="color: #00bcd4;">Date of Birth:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($provider_dob); ?></td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Social Security:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($row['PRO_SOCIAL'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Background Check:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($row['BACKGROUND_CHECK'] ? 'Passed' : 'Not Passed'); ?></td>
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
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($provider_name); ?></td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Address:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($provider_address . ', ' . $provider_city . ', ' . $provider_state . ', ' . $provider_zip); ?></td>
                                </tr>
                                <tr>
                                    <th style="color: #00bcd4;">Email:</th>
                                    <td style="color: #00bcd4;"><?php echo htmlspecialchars($provider_email); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
				<div class="col-lg-8">
    				<div class="card">
        				<div class="card-header bg-transparent border-0">
            				<h3 class="mb-0" style="color: #00bcd4;">Lawn Care Tip of the Day</h3>
        				</div>
        			<div class="card-body">
            	<?php
                	// Array of lawn care tips
                $tips = [
                    "Mow your lawn regularly, but never cut more than one-third of the grass height at once.",
                    "Water deeply but less often to encourage strong root growth.",
                    "Sharpen your mower blades to ensure a clean cut that prevents disease.",
                    "Leave grass clippings on the lawn to decompose and return nutrients to the soil.",
                    "Aerate your lawn annually to improve oxygen flow and nutrient absorption.",
                    "Fertilize your lawn in early spring and fall for optimal growth.",
                    "Water your lawn early in the morning to reduce evaporation.",
                    "Consider overseeding to fill in bare patches and improve lawn density.",
                    "Avoid mowing wet grass to prevent clumping and uneven cuts.",
                    "Adjust mowing height depending on the season: taller in summer, shorter in winter.",
                    "Use a mulching mower to recycle nutrients back into the lawn.",
                    "Test your soil pH annually and adjust with lime or sulfur as needed."
                ];

                // Pick a tip based on the current day
                $dayOfYear = date("z"); // Day of the year (0-365)
                $tipOfTheDay = $tips[$dayOfYear % count($tips)]; // Cycle through the tips array

                echo "<p style='color: #e0e0e0; font-size: 16px;'>" . htmlspecialchars($tipOfTheDay) . "</p>";
            	?>
        		</div>
    				</div>
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
