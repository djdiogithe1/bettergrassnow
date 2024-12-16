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

// Fetch provider personal information
$sql = "SELECT PRO_EMAIL, PRO_FNAME, PRO_LNAME, PRO_ADDRESS, PRO_CITY, PRO_STATE, PRO_ZIP FROM PROVIDERS WHERE PRO_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$provider_info = $result->fetch_assoc();

// Handle message actions (reply, delete, clear sent messages)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Reply to a message
    if (isset($_POST['reply_message'])) {
        $reply_text = $_POST['reply_text'];
        $cust_id = $_POST['CUST_ID'];
        $direction = 'outgoing';

        $sql_reply = "INSERT INTO messages (PRO_ID, CUST_ID, MESSAGE_TEXT, DIRECTION, CONTACT_INFO) VALUES (?, ?, ?, ?, ?)";
        $stmt_reply = $conn->prepare($sql_reply);
        $stmt_reply->bind_param("iisss", $provider_id, $cust_id, $reply_text, $direction, $contact_info);
        $stmt_reply->execute();

        // Redirect to refresh the page
        header("Location: inbox.php");
        exit();
    }

    // Delete a message
    if (isset($_POST['delete_message'])) {
        $message_id = $_POST['MESSAGE_ID'];

        $sql_delete = "DELETE FROM messages WHERE MESSAGE_ID = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $message_id);
        $stmt_delete->execute();

        // Redirect to refresh the page
        header("Location: inbox.php");
        exit();
    }

    // Clear all sent messages
    if (isset($_POST['clear_sent_messages'])) {
        $sql_clear = "DELETE FROM messages WHERE PRO_ID = ? AND DIRECTION = 'outgoing'";
        $stmt_clear = $conn->prepare($sql_clear);
        $stmt_clear->bind_param("i", $provider_id);
        $stmt_clear->execute();

        // Redirect to refresh the page
        header("Location: inbox.php");
        exit();
    }
}

// Fetch received messages (Inbox: cust_id -> pro_id)
$sql_received = "SELECT m.MESSAGE_ID, m.CUST_ID, m.MESSAGE_TEXT, m.CONTACT_INFO, m.DIRECTION, m.MESSAGE_DATE,
                        c.CUST_FNAME AS customer_fname, c.CUST_LNAME AS customer_lname
                 FROM messages m
                 JOIN CUSTOMERS c ON m.CUST_ID = c.CUST_ID
                 WHERE m.PRO_ID = ? AND m.DIRECTION = 'incoming'
                 ORDER BY m.MESSAGE_DATE DESC";
$stmt_received = $conn->prepare($sql_received);
$stmt_received->bind_param("i", $provider_id);
$stmt_received->execute();
$received_messages = $stmt_received->get_result();

// Fetch sent messages (Outgoing: pro_id -> cust_id)
$sql_sent = "SELECT m.MESSAGE_ID, m.CUST_ID, m.MESSAGE_TEXT, m.CONTACT_INFO, m.DIRECTION, m.MESSAGE_DATE,
                    c.CUST_FNAME AS customer_fname, c.CUST_LNAME AS customer_lname
             FROM messages m
             LEFT JOIN CUSTOMERS c ON m.CUST_ID = c.CUST_ID
             WHERE m.PRO_ID = ? AND m.DIRECTION = 'outgoing'
             ORDER BY m.MESSAGE_DATE DESC";
$stmt_sent = $conn->prepare($sql_sent);
$stmt_sent->bind_param("i", $provider_id);
$stmt_sent->execute();
$sent_messages = $stmt_sent->get_result();

$conn->close();

// Function to send email notification
function sendEmailNotification($providerEmail, $messageText) {
    $subject = "New Message Received on BetterGrassNow";
    $message = "Dear Provider,\n\nYou have received a new message on BetterGrassNow. Here's the message:\n\n" . $messageText . "\n\nPlease log in to your account to respond.\n\nBest Regards,\nBetterGrassNow Team";

    // Use PHP's mail function to send the email
    $headers = 'From: no-reply@bettergrassnow.com' . "\r\n" .
               'Reply-To: no-reply@bettergrassnow.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    mail($providerEmail, $subject, $message, $headers);
}

// Trigger an email whenever a new message is received
// This will happen after fetching incoming messages, so we check if we have any incoming messages
if ($received_messages->num_rows > 0) {
    // Send email to the provider for each new incoming message
    while ($message = $received_messages->fetch_assoc()) {
        sendEmailNotification($provider_info['PRO_EMAIL'], $message['MESSAGE_TEXT']);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetterGrassNow - Provider Inbox</title>
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
    		background-color: #1f1f1f;
    		color: #e0e0e0;
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
            background-color: #1f1f1f;
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
        /* Center the container and add a border with neon glow */
.table-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh; /* Centers vertically */
    padding: 20px;
}

.centered-container {
    background-color: #1f1f1f;
    border: 2px solid #00bcd4; /* Aqua border */
    box-shadow: 0 0 20px #00bcd4; /* Neon glow */
    padding: 20px;
    border-radius: 10px;
    max-width: 1200px; /* Controls the width */
    width: 100%; /* Full width */
}

h3 {
    color: #00bcd4;
    text-align: center;
    margin-bottom: 20px;
}

p {
    color: #e0e0e0;
    text-align: center;
}
}
.table {
    background-color: #1f1f1f;
    }

.table th, .table td {
	background-color: #1f1f1f;
    color: #00bcd4; /* Aqua text for table */
    text-align: center;
}

.table thead th {
    background-color: #1c1c1c;
    color: #00bcd4;
    font-weight: bold;
    border-bottom: 1px solid #00bcd4;
}

.btn-secondary, .btn-danger {
    margin: 5px; /* Adds spacing between buttons */
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

        <section class="table-container">
                <div class="col-lg-10">
                    <!-- Received Messages -->
                    <div class="profile py-4">
                        <div class="card">
                            <h3><i class="fa fa-envelope"></i> Received Messages</h3>
                            <p style="color: #00bcd4;">* Beware if a message is deleted it cannot be reversed.</p>
                        </div>
                        <div class="card-body">
                            <?php if ($received_messages->num_rows > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="color: #00bcd4;">From</th>
                                            <th style="color: #00bcd4;">To</th>
                                            <th style="color: #00bcd4;">Message</th>
                                            <th style="color: #00bcd4;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($msg = $received_messages->fetch_assoc()): ?>
                                            <tr>
                                            	<td style="color: #00bcd4;"><?php echo htmlspecialchars($provider_info['PRO_FNAME'] . " " . $provider_info['PRO_LNAME']); ?></td>
                                                <td style="color: #00bcd4;"><?php echo htmlspecialchars($msg['customer_fname'] . " " . $msg['customer_lname']); ?></td>
                                                <td style="color: #00bcd4;"><?php echo htmlspecialchars($msg['MESSAGE_TEXT']); ?></td>
                                                <td style="color: #00bcd4;">
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReplyForm(<?php echo $msg['MESSAGE_ID']; ?>)">Reply</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="MESSAGE_ID" value="<?php echo $msg['MESSAGE_ID']; ?>">
                                                        <button type="submit" name="delete_message" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <tr id="reply-form-<?php echo $msg['MESSAGE_ID']; ?>" style="display:none;">
                                                <td colspan="3">
                                                    <form method="POST">
                                                        <input type="hidden" name="CUST_ID" value="<?php echo $msg['CUST_ID']; ?>">
                                                        <textarea name="reply_text" placeholder="Write your reply..." class="form-control"></textarea>
                                                        <button type="submit" name="reply_message" class="btn btn-primary mt-2">Send Reply</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No received messages found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sent Messages -->
<!-- Sent Messages -->
<div class="profile py-4">
    <div class="card">
        <h3><i class="fa fa-paper-plane"></i> Sent Messages</h3>
    </div>
    <div class="card-body">
        <?php if ($sent_messages->num_rows > 0): ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="color: #00bcd4;">From</th>
                        <th style="color: #00bcd4;">To</th>
                        <th style="color: #00bcd4;">Message</th>
                        <th style="color: #00bcd4;">Sent</th>
                        <th style="color: #00bcd4;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($msg = $sent_messages->fetch_assoc()): ?>
                        <tr>
                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($provider_info['PRO_FNAME'] . " " . $provider_info['PRO_LNAME']); ?></td>
                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($msg['customer_fname'] . " " . $msg['customer_lname']); ?></td>
                            <td style="color: #00bcd4;"><?php echo htmlspecialchars($msg['MESSAGE_TEXT']); ?></td>
                            <td style="color: #00bcd4;"><?php echo date("F j, Y, g:i a", strtotime($msg['MESSAGE_DATE'])); ?></td>
                            <td style="color: #00bcd4;">
                                <!-- Reply Button -->
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReplyForm(<?php echo $msg['MESSAGE_ID']; ?>)">Reply</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="MESSAGE_ID" value="<?php echo $msg['MESSAGE_ID']; ?>">
                                    <button type="submit" name="delete_message" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <!-- Reply Form for Sent Messages -->
                        <tr id="reply-form-<?php echo $msg['MESSAGE_ID']; ?>" style="display:none;">
                            <td colspan="5">
                                <form method="POST">
                                    <input type="hidden" name="CUST_ID" value="<?php echo $msg['CUST_ID']; ?>">
                                    <textarea name="reply_text" placeholder="Write your reply..." class="form-control"></textarea>
                                    <button type="submit" name="reply_message" class="btn btn-primary mt-2">Send Reply</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No sent messages found.</p>
        <?php endif; ?>

        <!-- Button to Clear All Sent Messages -->
        <form method="POST">
            <button type="submit" name="clear_sent_messages" class="btn btn-danger">Clear All Sent Messages</button>
        </form>
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
    <script>
        function toggleReplyForm(messageId) {
            const replyForm = document.getElementById(`reply-form-${messageId}`);
            replyForm.style.display = replyForm.style.display === 'none' ? '' : 'none';
        }
    </script>
</body>
</html>