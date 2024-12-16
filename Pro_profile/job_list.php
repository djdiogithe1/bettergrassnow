<?php
// Start the session and check if the user is logged in
session_start();
if (!isset($_SESSION['PRO_ID'])) {
    header("Location: ../Pro_profile/home.php");
    exit();
}

// Include database connection
include_once '../Pro_profile/db_connection.php';

// Get pro_id from session (this is the logged-in provider's ID)
$pro_id = $_SESSION['PRO_ID'];

// Fetch provider details
$sql_provider = "SELECT PRO_FNAME, PRO_LNAME, PRO_STRIPE FROM PROVIDERS WHERE PRO_ID = ?";
$stmt_provider = $conn->prepare($sql_provider);
$stmt_provider->bind_param("i", $pro_id);
$stmt_provider->execute();
$result_provider = $stmt_provider->get_result();

if ($result_provider->num_rows > 0) {
    $row = $result_provider->fetch_assoc();
    $username = $row['PRO_FNAME'] . " " . $row['PRO_LNAME'];
    $pro_stripe = $row['PRO_STRIPE']; // Stripe Payment Link
} else {
    echo "Provider not found. Please log in again.";
    exit();
}

// Function to send a message to the customer
function notify_customer($conn, $cust_id, $message) {
    $sql_message = "INSERT INTO messages (cust_id, message_text, status, direction) VALUES (?, ?, 'unread', 'outgoing')";
    $stmt_message = $conn->prepare($sql_message);
    $stmt_message->bind_param("is", $cust_id, $message);
    $stmt_message->execute();
    $stmt_message->close();
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        $valid_statuses = ['completed', 'cancelled', 'pending', 'refunded', 'scheduled'];

        // Validate status
        if (!in_array($status, $valid_statuses)) {
            echo "Invalid status.";
            exit();
        }

        // Update status
        $sql_update_status = "UPDATE service_orders SET status = ? WHERE order_id = ? AND pro_id = ?";
        $stmt_update_status = $conn->prepare($sql_update_status);
        $stmt_update_status->bind_param("sii", $status, $order_id, $pro_id);

        if ($stmt_update_status->execute()) {
            // Notify customer
            $sql_get_customer = "SELECT cust_id FROM service_orders WHERE order_id = ?";
            $stmt_get_customer = $conn->prepare($sql_get_customer);
            $stmt_get_customer->bind_param("i", $order_id);
            $stmt_get_customer->execute();
            $result_customer = $stmt_get_customer->get_result();
            $cust_id = $result_customer->fetch_assoc()['cust_id'];

            $message = "The status of your job (Order ID: $order_id) has been updated to '$status' by $username.";
            notify_customer($conn, $cust_id, $message);

            echo "<script>alert('Order status updated successfully. Notification sent to the customer.');</script>";
        } else {
            echo "Failed to update status.";
        }
    }

    if (isset($_POST['update_price'])) {
        $order_id = intval($_POST['order_id']);
        $new_price = floatval($_POST['total_price']);

        // Update price
        $sql_update_price = "UPDATE service_orders SET total_price = ? WHERE order_id = ? AND pro_id = ?";
        $stmt_update_price = $conn->prepare($sql_update_price);
        $stmt_update_price->bind_param("dii", $new_price, $order_id, $pro_id);

        if ($stmt_update_price->execute()) {
            // Notify customer
            $sql_get_customer = "SELECT cust_id FROM service_orders WHERE order_id = ?";
            $stmt_get_customer = $conn->prepare($sql_get_customer);
            $stmt_get_customer->bind_param("i", $order_id);
            $stmt_get_customer->execute();
            $result_customer = $stmt_get_customer->get_result();
            $cust_id = $result_customer->fetch_assoc()['cust_id'];

            $message = "The price of your job (Order ID: $order_id) has been updated to $$new_price. Please complete payment here: $pro_stripe.";
            notify_customer($conn, $cust_id, $message);

            echo "<script>alert('Price updated successfully. Notification sent to the customer.');</script>";
        } else {
            echo "Failed to update price.";
        }
    }

    if (isset($_POST['remove_job'])) {
        $order_id = intval($_POST['order_id']);

        // Remove job
        $sql_remove_job = "DELETE FROM service_orders WHERE order_id = ? AND pro_id = ?";
        $stmt_remove_job = $conn->prepare($sql_remove_job);
        $stmt_remove_job->bind_param("ii", $order_id, $pro_id);

        if ($stmt_remove_job->execute()) {
            // Notify customer
            $sql_get_customer = "SELECT cust_id FROM service_orders WHERE order_id = ?";
            $stmt_get_customer = $conn->prepare($sql_get_customer);
            $stmt_get_customer->bind_param("i", $order_id);
            $stmt_get_customer->execute();
            $result_customer = $stmt_get_customer->get_result();
            $cust_id = $result_customer->fetch_assoc()['cust_id'];

            $message = "Your job (Order ID: $order_id) has been removed by $username.";
            notify_customer($conn, $cust_id, $message);

            echo "<script>alert('Job removed successfully. Notification sent to the customer.');</script>";
        } else {
            echo "Failed to remove job.";
        }
    }
}

// Fetch jobs based on status
$statuses = ['pending', 'scheduled', 'completed', 'cancelled', 'refunded'];
$jobs = [];
foreach ($statuses as $status) {
    $sql_jobs = "SELECT order_id, zelle_email, service_type, service_frequency, service_date, service_address, status, total_price
                 FROM service_orders WHERE pro_id = ? AND status = ? ORDER BY service_date DESC";
    $stmt_jobs = $conn->prepare($sql_jobs);
    $stmt_jobs->bind_param("is", $pro_id, $status);
    $stmt_jobs->execute();
    $result_jobs = $stmt_jobs->get_result();

    while ($job = $result_jobs->fetch_assoc()) {
        $jobs[$status][] = $job;
    }
}

$stmt_provider->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetterGrassNow - Job List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Dark Mode Base Styles */
        body {
            background-image: url("../assets/img/grass.jpg");
            color: #e0e0e0; /* Light text for contrast */
            font-family: 'Lato', sans-serif;
        }
        .card {
            background-color: #1c1c1c; /* Dark card background */
            color: #fff;
            border: none;
            max-width: 600px;
            margin: 30px auto; /* Center the card */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px #00bcd4; /* Neon aqua glow */
            transition: box-shadow 0.3s ease;
        }
        .profile-image {
            width: 150px;  /* Increase width */
            height: 150px; /* Increase height */
            border-radius: 8px; /* Rounded corners */
            object-fit: cover;
            border: 4px solid #00bcd4;
            margin-bottom: 20px;
        }
        .card:hover {
            box-shadow: 0 0 30px #00bcd4; /* Enhanced glow on hover */
        }
        .card h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #00bcd4; /* Aqua text color */
        }
        .form-group label {
            color: #00bcd4; /* Aqua text color for labels */
            font-weight: bold;
        }
        .form-control {
            background-color: #333;  /* Dark background for form fields */
            color: #00bcd4; /* Aqua text color for inputs */
            border: 1px solid #555;
            padding: 10px;
            font-size: 16px;
            width: 100%;  /* Full-width fields */
            border-radius: 5px;
        }
        .form-control:focus {
            background-color: #1f1f1f;
            border-color: #00bcd4;
            color: #00bcd4;
        }
        .btn-primary {
            background-color: #00bcd4;
            border: none;
            font-size: 16px;
            padding: 12px 20px;
            width: 100%;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #008c99;
        }
        footer {
            background-color: #222;
            color: #aaa;
            text-align: center;
            padding: 15px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
        .table-bordered {
            border: 1px solid #444;
        }
        .table th, .table td {
        	background-color: #1c1c1c;
            color: #00bcd4;
        }
        /* Glow effect for outer boxed area */
        .profile {
        	background-color= #1f1f1f;
            box-shadow: 0 0 25px #00bcd4; /* Neon aqua glow */
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        /* Navigation Bar Styling */
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
        }
         nav ul li a:hover {
            color: #00bcd4;
        }
        nav ul li a.active {
            color: #00bcd4;
            font-weight: bold;
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
            <h1>Hey <?php echo htmlspecialchars($username); ?>,<br> your job list is here.</h1>
            <div class="container">
            
                <!-- Loop through the job sections -->
                <?php
                $sections = ['pending' => 'Pending Jobs', 'scheduled' => 'Scheduled Jobs', 'completed' => 'Completed Jobs', 'cancelled' => 'Cancelled Jobs', 'refunded' => 'Refunded Jobs'];
                foreach ($sections as $status => $title): ?>
                    <h2><?php echo $title; ?></h2>
                    <div class="profile py-4" style="background-color: #1f1f1f;" >
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="color: #00bcd4;">Service Type</th>
                                    <th style="color: #00bcd4;">Frequency</th>
                                    <th style="color: #00bcd4;">Service Date</th>
                                    <th style="color: #00bcd4;">Service Address</th>
                                    <th style="color: #00bcd4;">Status</th>
                                    <th style="color: #00bcd4;">Total Price</th>
                                    <th style="color: #00bcd4;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jobs[$status])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No <?php echo strtolower($title); ?>.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs[$status] as $job): ?>
                                        <tr>
                                        
                                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($job['service_type']); ?></td>
                                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($job['service_frequency']); ?></td>
                                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($job['service_date']); ?></td>
                                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($job['service_address']); ?></td>
                                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($job['status']); ?></td>
                                            <td style="color: #00bcd4;">
                                                <!-- Editable price for pending and scheduled statuses -->
                                                <?php if (in_array($status, ['pending', 'scheduled'])): ?>
                                                    <form action="job_list.php" method="post" style="display:inline;">
                                                        <input type="hidden" name="order_id" value="<?php echo $job['order_id']; ?>">
                                                        <input type="number" step="0.01" name="total_price" value="<?php echo $job['total_price']; ?>" class="form-control form-control-sm d-inline-block w-auto">
                                                        <button type="submit" name="update_price" class="btn btn-success btn-sm">Update Price</button>
                                                    </form>
                                                <?php else: ?>
                                                    $<?php echo number_format($job['total_price'], 2); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Status update and remove actions -->
                                                <?php if (in_array($status, ['pending', 'scheduled'])): ?>
                                                    <form action="job_list.php" method="post" style="display:inline;">
                                                        <input type="hidden" name="order_id" value="<?php echo $job['order_id']; ?>">
                                                        <select name="status">
                                                            <option value="pending" <?php echo $job['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="scheduled" <?php echo $job['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                            <option value="cancelled" <?php echo $job['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            <option value="refunded" <?php echo $job['status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                        </select>
                                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form action="job_list.php" method="post" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $job['order_id']; ?>"><br>

                                                </form>
                                                <td style="color: #00bcd4;">
    										<!-- Before and After Image Gallery for scheduled jobs -->
    										<?php if ($status == 'scheduled'): ?>
        										<div class="image-gallery">
            								<!-- Check if before image exists and display it -->
            								<?php if (!empty($job['before_image'])): ?>
                							<a href="#" data-toggle="modal" data-target="#beforeImageModal_<?php echo $job['order_id']; ?>">
                    						<img src="../images/<?php echo $job['before_image']; ?>" alt="Before Image" class="img-thumbnail" style="width: 80px; height: 80px;">
                							</a>

                							<!-- Modal for before image -->
                							<div class="modal fade" id="beforeImageModal_<?php echo $job['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="beforeImageLabel_<?php echo $job['order_id']; ?>" aria-hidden="true">
                    							<div class="modal-dialog" role="document">
                        							<div class="modal-content">
                            							<div class="modal-header">
                                					<h5 class="modal-title" id="beforeImageLabel_<?php echo $job['order_id']; ?>">Before Image</h5>
                                				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    			<span aria-hidden="true">&times;</span>
                                				</button>
                            				</div>
                            			<div class="modal-body">
                                	<img src="../images/<?php echo $job['before_image']; ?>" alt="Before Image" class="img-fluid">
                            	</div>
                        	</div>
                    	</div>
                	</div>
            	<?php endif; ?>

            	<!-- Check if after image exists and display it -->
            	<?php if (!empty($job['after_image'])): ?>
                <a href="#" data-toggle="modal" data-target="#afterImageModal_<?php echo $job['order_id']; ?>">
                    <img src="../images/<?php echo $job['after_image']; ?>" alt="After Image" class="img-thumbnail" style="width: 80px; height: 80px;">
                </a>

                <!-- Modal for after image -->
                	<div class="modal fade" id="afterImageModal_<?php echo $job['order_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="afterImageLabel_<?php echo $job['order_id']; ?>" aria-hidden="true">
                    	<div class="modal-dialog" role="document">
                        	<div class="modal-content">
                            	<div class="modal-header">
                                	<h5 class="modal-title" id="afterImageLabel_<?php echo $job['order_id']; ?>">After Image</h5>
                                	<button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    	<span aria-hidden="true">&times;</span>
                                	</button>
                            			</div>
                            				<div class="modal-body">
                                				<img src="../images/<?php echo $job['after_image']; ?>" alt="After Image" class="img-fluid">
                            				</div>
                        				</div>
                    				</div>
                				</div>
            					<?php endif; ?>
        								</div>
    								<?php endif; ?>
											</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
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
    <!-- Add these scripts at the bottom of your <body> tag to enable Bootstrap modals -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
</body>
</html>
