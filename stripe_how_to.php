

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <style>
        /* Dark Mode Base Styles */
        body {
            background-color: #121212; /* Dark background */
            color: #e0e0e0; /* Light text for contrast */
            font-family: 'Lato', sans-serif;
        }
        .card {
            background-color: #1c1c1c; /* Dark card background */
            color: #fff;
            border: none;
            margin: 30px auto; /* Center the card */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px #00bcd4; /* Neon aqua glow */
            transition: box-shadow 0.3s ease;
            max-width: 600px;
        }
        .card:hover {
            box-shadow: 0 0 30px #00bcd4; /* Enhanced glow on hover */
        }
        .form-group label {
            color: #00bcd4; /* Aqua text color for labels */
            font-weight: bold;
        }
        .form-control {
            background-color: #111;  /* Dark background for form fields */
            color: #00bcd4; /* Aqua text color for inputs */
            border: 1px solid #555;
            padding: 10px;
            font-size: 16px;
            width: 100%;  /* Full-width fields */
            border-radius: 5px;
        }
        .form-control:focus {
            background-color: #444;
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
            color: #00bcd4;
        }
        /* Glow effect for outer boxed area */
        .profile {
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
        nav ul li a:hover {
            color: #00bcd4;
        }
        nav ul li a.active {
            color: #00bcd4;
            font-weight: bold;
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
        .provider-info span {
            font-weight: bold;
        }

        /* Styling for checkboxes and labels */
        .provider-check {
            margin-bottom: 10px;
        }
        .provider-check input[type="checkbox"] {
            margin-right: 10px;
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
                	<li><a href="./index.html">HOME</a></li>
                	<li><a href="./services/services.html">SERVICES</a></li>
                	<li><a href="./about/about.html">ABOUT US</a></li>
                	<li><a href="./User_login/login.html">LOGIN</a></li>
            	</ul>
            </div>
        </nav>
    </header>

    <!-- Service Order Form -->
    <div class="container">
        <div class="card">
            <h2 class="text-center">Stripe how-to</h2>
            <small>
			<strong>1)&nbsp;Sign up with STRIPE.COM	to accept payments-</strong>
			<br>&nbsp;&nbsp;As a provider on the Better Grass Now platform, you must 
			create a free account with STRIPE.COM. Doing so will also allow you to 
			keep track over all of your finances incoming and out-going.<br>
			<br>
			<strong>2)&nbsp;Create the share payment link-</strong>
			<br>&nbsp;&nbsp;Once you have created your STRIPE.com account. Located 
			on the home page there is the option to create a shared payment link. 
			Select the option for the customer to  "choose what to pay". Once the 
			quote is accepted on the price agreement between you and the customer, 
			update the price located on service requests page on the providers 
			dashboard. Once the payment is updated a message is triggered to the 
			customer to go to the payment url that is provided at registration. <br>
			<br>
			<strong>3)&nbsp;Copy the url-</strong>
			<br>&nbsp;&nbsp;On your STRIP.com dashboard located under "Payment Links", 
			copy the url link and insert this into the registration form. This will 
			allow the customer to pay directly to you and for you to manage the payments, 
			refunds or funds not received. &nbsp;<br>
			<br>
			<strong>4)&nbsp;Payments and fairness-</strong>
			<br>&nbsp;&nbsp;As a provider oon the Better GrassNow platform it is your 
			responsibility to be fair to the customer and provide a customer refunds if 
			the services are cancelled within 3 business days of the services requested. 
			If a customer cancels on the day of service, the provider may issue a partial 
			refund of half of the total cost of the services requested. If a requested 
			cancellation is atleast 72 hours prior to service and a cancellation refund 
			is not processed this may result in removal from the platform. Bettergrassnow 
			is not responsible for payments between the customer and the provider as we 
			provide a communication platform for customers to receive services from 
			providers. <br>
			<br>
			</small>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 BetterGrassNow. All rights reserved.</p>
    </footer>

</body>
</html>
