<?php
// upload_image.php

// Database connection (replace with your actual DB credentials)
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'admin';
$password = getenv('DB_PASSWORD') ?: 'Mm3329788$';
$dbname = getenv('DB_NAME') ?: 'Bettergrassweb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Handle the image upload
if (isset($_POST['submit'])) {
    $image = $_FILES['image'];
    $cust_id = $_POST['cust_id'];
    $image_type = $_POST['image_type'];

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    function save_and_get_image($image, $image_type, $order_id, $service_date) {
        global $allowedExtensions;

        // Create folder with ORDER_ID and SERVICE_DATE
        $uploadsDir = __DIR__ . '/uploads/' . $order_id . '_' . $service_date . '/';

        // Check if the folder already exists, create it if it doesn't
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);  // Create the directory if it doesn't exist
        }

        $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            return ["error" => "Invalid file type. Only JPG, JPEG, PNG, or GIF allowed."];
        }

        if ($image['error'] !== 0) {
            return ["error" => "Error during file upload."];
        }

        // Check if the image type already exists in the folder
        if (file_exists($uploadsDir . $image_type . '_front.jpg') || 
            file_exists($uploadsDir . $image_type . '_back.jpg') || 
            file_exists($uploadsDir . $image_type . '_left.jpg') || 
            file_exists($uploadsDir . $image_type . '_right.jpg')) {
            return ["error" => "An image of this type has already been uploaded. Please do not upload a second one."];
        }

        $filename = $image_type . '_' . time() . '_' . basename($image['name']);
        $targetPath = $uploadsDir . $filename;

        // Move the uploaded file to the target folder
        if (move_uploaded_file($image['tmp_name'], $targetPath)) {
            return ["data" => file_get_contents($targetPath)];
        } else {
            return ["error" => "Failed to save image to uploads folder."];
        }
    }

    // Fetch ORDER_ID and SERVICE_DATE for the selected customer
    $query = "SELECT ORDER_ID, SERVICE_DATE FROM service_orders 
              WHERE CUST_ID = :cust_id AND STATUS = 'scheduled'
              ORDER BY SERVICE_DATE ASC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':cust_id', $cust_id, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $serviceOrderId = $order['ORDER_ID'];
        $serviceDate = $order['SERVICE_DATE'];  // Retrieve the SERVICE_DATE
        $result = save_and_get_image($image, $image_type, $serviceOrderId, $serviceDate);

        if (isset($result['error'])) {
            $message = $result['error'];
            $messageClass = "error-message";
        } else {
            $imageData = $result['data'];

            // Log for debugging purposes
            error_log("Updating image of type: $image_type for Order ID: $serviceOrderId");

            // Ensure image type is valid for the update query
            if ($image_type === 'CUST_ADD') {
                $stmt = $pdo->prepare("UPDATE service_orders 
                                       SET CUST_ADD = :image_data 
                                       WHERE ORDER_ID = :order_id AND CUST_ID = :cust_id");
            } else {
                // For other image types like BEFORE_FRONT, AFTER_FRONT, etc.
                $stmt = $pdo->prepare("UPDATE service_orders 
                                       SET $image_type = :image_data 
                                       WHERE ORDER_ID = :order_id AND CUST_ID = :cust_id");
            }

            $stmt->bindParam(':image_data', $imageData, PDO::PARAM_LOB);
            $stmt->bindParam(':order_id', $serviceOrderId, PDO::PARAM_INT);
            $stmt->bindParam(':cust_id', $cust_id, PDO::PARAM_INT);
            $stmt->execute();

            $message = "Image uploaded successfully!";
            $messageClass = "success-message";
        }
    } else {
        $message = "No scheduled service order found for this customer.";
        $messageClass = "error-message";
    }
}

// Fetch customers with scheduled orders
$query = "SELECT DISTINCT s.CUST_ID, c.CUST_FNAME, c.CUST_LNAME, s.SERVICE_DATE, s.SERVICE_TYPE
          FROM service_orders s 
          JOIN CUSTOMERS c ON s.CUST_ID = c.CUST_ID
          WHERE s.STATUS = 'scheduled' 
          ORDER BY s.SERVICE_DATE ASC";
$stmt = $pdo->query($query);
$cust_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Order Image Upload</title>
    <style>
        /* Basic Styles */
        body {
            background-image: url("../assets/img/grass.jpg");
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        h1 {
            margin-bottom: 20px;
        }
        .container {
            background-color: #1f1f1f;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: auto;
        }
        form {
            text-align: left;
        }
        label, select, input[type="file"] {
            display: block;
            margin-bottom: 15px;
            width: 100%;
        }
        button {
            background-color: #00FFFF;
            color: #fff;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #00CCCC;
        }
        .success-message, .error-message {
            text-align: center;
            margin-bottom: 15px;
        }
        .success-message { color: green; }
        .error-message { color: red; }

        /* Navigation Bar */
        header {
            background-color: #1f1f1f;
            padding: 10px 0;
            position: absolute;
            top: 0;
            width: 100%;
        }
        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        nav a {
            color: #ffffff;
            text-decoration: none;
            padding: 10px 15px;
        }
        nav a:hover {
            color: #00CCCC;
        }
        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        nav li {
            margin: 0 10px;
        }
        nav a.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <header>
        <nav>
            <div class="container">
                <a href="../index.html" class="logo">BetterGrassNow</a>
                <ul>
                    <li><a href="../Pro_profile/provider_profile.php">HOME</a></li>
                    <li><a href="../Pro_profile/inbox.php">INBOX</a></li>
                    <li><a href="../Pro_profile/upload_image.php" class="active">JOB IMAGE UPLOADS</a></li>
                    <li><a href="../Pro_profile/job_list.php">SERVICE REQUESTS</a></li>
                    <li><a href="../Pro_profile/funds.php">FUNDS</a></li>
                    <li><a href="logout.php">SIGN OUT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Upload Service Images</h1>
        <?php if (isset($message)): ?>
            <p class="<?= $messageClass; ?>"><?= $message; ?></p>
        <?php endif; ?>
        <form action="upload_image.php" method="POST" enctype="multipart/form-data">
            <label for="cust_id">Select Customer</label>
            <select id="cust_id" name="cust_id" required>
                <?php foreach ($cust_ids as $cust): ?>
                    <option value="<?= $cust['CUST_ID']; ?>">
                        <?= htmlspecialchars($cust['CUST_FNAME'] . ' ' . $cust['CUST_LNAME'] . ' - ' . $cust['SERVICE_TYPE'] . ' - ' . $cust['SERVICE_DATE']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="image_type">Choose Image Type</label>
            <select id="image_type" name="image_type" required>
                <option value="BEFORE_FRONT">Before Front</option>
                <option value="AFTER_FRONT">After Front</option>
                <option value="BEFORE_BACK">Before Back</option>
                <option value="AFTER_BACK">After Back</option>
                <option value="CUST_ADD">Customer Address</option>
            </select>

            <label for="image">Choose Image</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            <button type="submit" name="submit">Upload Image</button>
        </form>
        <p>* Please upload the service images to complete the service request.</p>
    </div>

</body>
</html>


