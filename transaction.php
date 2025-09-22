<?php
// Prevent caching - ADD THIS AT THE VERY TOP
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Your existing session_start() and other code...
session_start();

// Check for success redirect
if (isset($_GET['success']) && isset($_SESSION['payment_success'])) {
    $success = $_SESSION['payment_success'];
    $delivery_person_name = $_SESSION['delivery_person_name'] ?? '';
    $delivery_person_phno = $_SESSION['delivery_person_phno'] ?? '';
    // Do NOT unset here; wait until after HTML output
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: register.php");
    exit();
}

// Check if order details exist in session
if (!isset($_SESSION['order_details']) && !isset($success)) {
    header("Location: foods.php");
    exit();
}

// Database connection
include("back_end/database_connectivity.php");

// Initialize variables
$errors = array();
$success = $success ?? '';
$delivery_persons = array();
$delivery_person_name = $delivery_person_name ?? '';
$delivery_person_phno = $delivery_person_phno ?? '';

// Get the grand total from session
if (isset($_SESSION['order_details']) && isset($_SESSION['order_details']['grand_total'])) {
    $grand_total = $_SESSION['order_details']['grand_total'];
} else {
    // If order details don't exist, redirect back to foods page
    header("Location: foods.php");
    exit();
}

// If order is successful and delivery person is assigned, fetch their phone number
if ($success && $delivery_person_name && $delivery_person_name !== 'Not assigned yet') {
    $phno_stmt = $conn->prepare("SELECT Phno FROM delivery_persons WHERE Name = ? LIMIT 1");
    $phno_stmt->bind_param("s", $delivery_person_name);
    $phno_stmt->execute();
    $phno_result = $phno_stmt->get_result();
    if ($phno_result->num_rows === 1) {
        $row = $phno_result->fetch_assoc();
        $delivery_person_phno = $row['Phno'];
    }
    $phno_stmt->close();
}
$customer_address = '';

// Get customer address
$customerQuery = $conn->prepare("SELECT Address FROM customer_det WHERE Cust_ID = ?");
$customerQuery->bind_param("s", $_SESSION['customer_id']);
$customerQuery->execute();
$customerResult = $customerQuery->get_result();

if ($customerResult->num_rows === 1) {
    $customer = $customerResult->fetch_assoc();
    $customer_address = $customer['Address'];
}
$customerQuery->close();

// Fetch available delivery persons with their phone numbers
$deliveryPersonQuery = $conn->prepare("SELECT Del_person_id, Name, Phno FROM Delivery_persons WHERE Status = 'available'");
$deliveryPersonQuery->execute();
$deliveryPersonResult = $deliveryPersonQuery->get_result();

if ($deliveryPersonResult->num_rows > 0) {
    while ($row = $deliveryPersonResult->fetch_assoc()) {
        $delivery_persons[] = $row;
    }
}
$deliveryPersonQuery->close();

// Process payment form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $delivery_address = trim($_POST['delivery_address']);
    
    // Validate delivery address
    if (empty($delivery_address)) {
        $errors[] = "Delivery address is required";
    }
    
    if (empty($errors)) {
        // Get order details from session
        $orderDetails = $_SESSION['order_details'];
        
        // Generate order number
        $orderNumber = time() . rand(100, 999);
        
        // Get current date and time
        $status = 'Pending'; // Initial status
        
        // Insert into Orders table
        $orderStmt = $conn->prepare("INSERT INTO Orders (Ord_number, Cust_id, Ord_date, Status, Total_Tax, Delivery_charge, Grand_Tot) 
                                    VALUES (?, ?, NOW(), ?, ?, ?, ?)");
        $orderStmt->bind_param("sssddd", $orderNumber, $_SESSION['customer_id'], $status, 
                              $orderDetails['total_tax'], $orderDetails['delivery_charge'], $orderDetails['grand_total']);
        
        if ($orderStmt->execute()) {
            // Get the last inserted order ID
            $ord_id = $conn->insert_id;
            
            // Insert into Ordered_items table for each item
            foreach ($orderDetails['order_items'] as $item) {
                $orderItemStmt = $conn->prepare("INSERT INTO Ordered_items (Ord_id, Food_id, FoodName, Unit_Price, Quantity, Dis_Amount, Tax_percent, Final_item_price) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $orderItemStmt->bind_param("iisdiidd", $ord_id, $item['food_id'], $item['food_name'], 
                                          $item['unit_price'], $item['quantity'], $item['discount_amount'], 
                                          $item['tax_percent'], $item['final_unit_price']);
                
                if (!$orderItemStmt->execute()) {
                    // Handle error
                    $errors[] = "Error creating order items: " . $conn->error;
                    break;
                }
                
                $orderItemStmt->close();
            }
            
            if (empty($errors)) {
                if ($payment_method === 'cash') {
                    // Cash on delivery processing
                    $delivery_person_id = null;
                    $delivery_status = 'Pending';
                    $delivery_person_name = 'Not assigned yet';
                    $delivery_person_phno = '';
                    
                    // Assign delivery person if available
                    if (!empty($delivery_persons)) {
                        $random_index = array_rand($delivery_persons);
                        $delivery_person_id = $delivery_persons[$random_index]['Del_person_id'];
                        $delivery_person_name = $delivery_persons[$random_index]['Name'];
                        $delivery_person_phno = $delivery_persons[$random_index]['Phno'];
                        $delivery_status = 'Processing';
                        
                        // Update delivery person status to Busy
                        $updateStmt = $conn->prepare("UPDATE Delivery_persons SET Status = 'busy' WHERE Del_person_id = ?");
                        $updateStmt->bind_param("i", $delivery_person_id);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                    
                    // Insert into Delivery table
                    $deliveryStmt = $conn->prepare("INSERT INTO Delivery (Ord_id, Cust_id, Del_address, Del_date, Del_person_id, Del_status) 
                                                   VALUES (?, ?, ?, NOW(), ?, ?)");
                    $deliveryStmt->bind_param("issss", $ord_id, $_SESSION['customer_id'], $delivery_address, $delivery_person_id, $delivery_status);
                    
                    if ($deliveryStmt->execute()) {
                        $delivery_id = $conn->insert_id;
                        
                        // Generate transaction ID for cash on delivery
                        $trans_id = 'cod_' . time() . rand(100, 999);
                        
                        // Insert into Trans table for cash payment, including date
                        $transStmt = $conn->prepare("INSERT INTO Trans (Trans_id, Ord_id, Pay_Mode, Pay_Status, Amount, date) 
                                                    VALUES (?, ?, 'Cash on Delivery', 'Not Completed', ?, NOW())");
                        $transStmt->bind_param("sid", $trans_id, $ord_id, $orderDetails['grand_total']);
                        
                        if ($transStmt->execute()) {
                            // Store success data in session
                            $_SESSION['payment_success'] = 'cash_success';
                            $_SESSION['delivery_person_name'] = $delivery_person_name;
                            $_SESSION['delivery_person_phno'] = $delivery_person_phno;
                            
                            // Clear order details from session
                            unset($_SESSION['order_details']);
                            
                            // Redirect to prevent form resubmission
                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                            exit();
                        } else {
                            $errors[] = "Transaction recording failed: " . $conn->error;
                        }
                        $transStmt->close();
                    } else {
                        $errors[] = "Delivery scheduling failed: " . $conn->error;
                    }
                    $deliveryStmt->close();
                    
                } elseif ($payment_method === 'mobile') {
                    // Mobile payment processing
                    if (isset($_POST['transaction_id']) && isset($_POST['full_name'])) {
                        $transaction_id = trim($_POST['transaction_id']);
                        $full_name = trim($_POST['full_name']);
                        
                        if (empty($transaction_id) || empty($full_name)) {
                            $errors[] = "Please fill in all required fields";
                        } else {
                            // Generate transaction ID for mobile payment
                            $trans_id = 'mb_' . time() . rand(100, 999);
                            
                            // Insert into Trans table for mobile payment, including date
                            $transStmt = $conn->prepare("INSERT INTO Trans (Trans_id, Ord_id, Pay_Mode, Pay_Status, Amount, date) 
                                                        VALUES (?, ?, 'Mobile Banking', 'Paid', ?, NOW())");
                            $transStmt->bind_param("sid", $trans_id, $ord_id, $orderDetails['grand_total']);
                            
                            if ($transStmt->execute()) {
                                // Schedule delivery
                                $delivery_person_id = null;
                                $delivery_status = 'Pending';
                                $delivery_person_name = 'Not assigned yet';
                                $delivery_person_phno = '';
                                
                                if (!empty($delivery_persons)) {
                                    $random_index = array_rand($delivery_persons);
                                    $delivery_person_id = $delivery_persons[$random_index]['Del_person_id'];
                                    $delivery_person_name = $delivery_persons[$random_index]['Name'];
                                    $delivery_person_phno = $delivery_persons[$random_index]['Phno'];
                                    $delivery_status = 'Processing';
                                    
                                    // Update delivery person status to Busy
                                    $updateStmt = $conn->prepare("UPDATE Delivery_persons SET Status = 'busy' WHERE Del_person_id = ?");
                                    $updateStmt->bind_param("i", $delivery_person_id);
                                    $updateStmt->execute();
                                    $updateStmt->close();
                                }
                                
                                // Insert into Delivery table
                                $deliveryStmt = $conn->prepare("INSERT INTO Delivery (Ord_id, Cust_id, Del_address, Del_date, Del_person_id, Del_status) 
                                                               VALUES (?, ?, ?, NOW(), ?, ?)");
                                $deliveryStmt->bind_param("issss", $ord_id, $_SESSION['customer_id'], $delivery_address, $delivery_person_id, $delivery_status);
                                
                                if ($deliveryStmt->execute()) {
                                    // Store success data in session
                                    $_SESSION['payment_success'] = 'mobile_success';
                                    $_SESSION['delivery_person_name'] = $delivery_person_name;
                                    $_SESSION['delivery_person_phno'] = $delivery_person_phno;
                                    
                                    // Clear order details from session
                                    unset($_SESSION['order_details']);
                                    
                                    // Redirect to prevent form resubmission
                                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                                    exit();
                                } else {
                                    $errors[] = "Delivery scheduling failed: " . $conn->error;
                                }
                                $deliveryStmt->close();
                            } else {
                                $errors[] = "Transaction recording failed: " . $conn->error;
                            }
                            $transStmt->close();
                        }
                    }
                }
            }
        } else {
            $errors[] = "Error creating order: " . $conn->error;
        }
        
        $orderStmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Food Order System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 90%;
        }
        
        .payment-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            color: #ff6b6b;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .payment-options {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        
        .payment-option {
            text-align: center;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 45%;
        }
        
        .payment-option:hover {
            border-color: #ff6b6b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.2);
        }
        
        .payment-option.selected {
            border-color: #ff6b6b;
            background-color: #fff5f5;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }
        
        .payment-option i {
            font-size: 2rem;
            color: #ff6b6b;
            margin-bottom: 10px;
        }
        
        .mobile-form {
            display: none;
            margin-top: 20px;
        }
        
        .cash-message {
            display: none;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        textarea:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
        }
        
        .pay-button-container {
            display: none;
            margin-top: 20px;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #ff6b6b;
            box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
        }
        
        .btn-primary:hover {
            background: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.4);
        }
        
        .btn-pay {
            background: #28a745;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
            width: 100%;
        }
        
        .btn-pay:hover {
            background: #218838;
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
        }
        
        .btn-menu {
            background: #17a2b8;
            box-shadow: 0 4px 10px rgba(23, 162, 184, 0.3);
        }
        
        .btn-menu:hover {
            background: #138496;
            box-shadow: 0 6px 15px rgba(23, 162, 184, 0.4);
        }
        
        .confirmation {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .confirmation-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .confirmation-message {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #555;
        }
        
        .payment-method {
            font-weight: 600;
            color: #ff6b6b;
            font-size: 1.3rem;
            margin: 15px 0;
        }
        
        .delivery-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        
        .delivery-person {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .error {
            color: #ff4757;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success-message {
            color: #28a745;
            font-weight: 600;
            margin: 10px 0;
            text-align: center;
        }
        
        .processing-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #ff6b6b;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .amount-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .amount-label {
            font-size: 1rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .amount-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b6b;
        }
        
        .amount-field {
            background-color: #f8f9fa;
            cursor: not-allowed;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .payment-options {
                flex-direction: column;
                align-items: center;
            }
            
            .payment-option {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="processing-overlay" id="processingOverlay">
        <div class="spinner"></div>
        <p>Processing your order...</p>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="confirmation">
                <div class="confirmation-icon">✓</div>
                <h1>Order Confirmed!</h1>
                <div class="payment-method">
                    Payment Method: <?php echo $success === 'mobile_success' ? 'Mobile Banking' : 'Cash on Delivery'; ?>
                </div>
                
                <div class="delivery-info">
                    <p>Thank you for your order!</p>
                    
                    <?php if ($delivery_person_name !== 'Not assigned yet'): ?>
                        <div class="delivery-person">
                            <p>Your order will be delivered by:</p>
                            <h3><?php echo htmlspecialchars($delivery_person_name); ?></h3>
                            <?php if (!empty($delivery_person_phno)): ?>
                                <p>Phone Number: <strong><?php echo htmlspecialchars($delivery_person_phno); ?></strong></p>
                            <?php endif; ?>
                        </div>
                        <p>You will receive a call when your food reaches near you.</p>
                    <?php else: ?>
                        <p>Sorry for the inconvenience. We will assign a delivery person soon and you will get a message once assigned.</p>
                    <?php endif; ?>
                </div>
                
                <a href="foods.php" class="btn btn-menu">Go Back to Menu</a>
            </div>
        <?php else: ?>
            <div class="payment-form">
                <h1>Complete Your Order</h1>
                <p class="subtitle">Review your details and make payment</p>
                
                <?php if (!empty($errors)): ?>
                    <div class="error" style="text-align: center; margin-bottom: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="amount-display">
                    <div class="amount-label">Total Amount to Pay:</div>
                    <div class="amount-value">&#8377;<?php echo number_format($grand_total, 2); ?></div>
                </div>
                
                <form method="POST" id="paymentForm">
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address *</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" placeholder="Enter delivery address" required><?php echo htmlspecialchars($customer_address); ?></textarea>
                        <small>You can edit your default address if needed</small>
                    </div>
                    
                    <div class="payment-options">
                        <div class="payment-option" onclick="selectPayment('cash')">
                            <div>💵</div>
                            <h3>Cash on Delivery</h3>
                            <p>Pay when you receive your order</p>
                        </div>
                        
                        <div class="payment-option" onclick="selectPayment('mobile')">
                            <div>📱</div>
                            <h3>Mobile Banking</h3>
                            <p>Pay securely online</p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="paymentMethod" value="">
                    
                    <div id="cashMessage" class="cash-message">
                        <p>Click "Confirm Order" to place your order with cash payment</p>
                    </div>
                    
                    <div id="mobileForm" class="mobile-form">
                        <div class="form-group">
                            <label for="transaction_id">Transaction ID *</label>
                            <input type="text" id="transaction_id" name="transaction_id" placeholder="Enter your transaction ID" required>
                            <small style="color: #666;">Please provide a unique transaction ID from your mobile banking app</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount  *</label>
                            <input type="text" id="amount" name="amount" class="amount-field" value="&#8377;<?php echo number_format($grand_total, 2); ?>" readonly required>
                            <small style="color: #666;">This is the total amount you need to pay via mobile banking</small>
                        </div>
                    </div>
                    
                    <div class="pay-button-container" id="payButtonContainer">
                        <button type="submit" class="btn btn-pay">Pay Now</button>
                    </div>
                    
                    <div id="confirmButtonContainer" class="pay-button-container" style="display: none;">
                        <button type="button" onclick="processCashPayment()" class="btn btn-pay">Confirm Order</button>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="foods.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Instant Confirmation Modal -->
    <div id="instantConfirmationModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div style="background:white; border-radius:15px; padding:40px 30px; max-width:400px; margin:auto; text-align:center; box-shadow:0 5px 20px rgba(0,0,0,0.2);">
            <div style="font-size:3rem; color:#28a745; margin-bottom:20px;">✓</div>
            <h2 style="color:#ff6b6b; margin-bottom:10px;">Order Confirmed!</h2>
            <div style="margin-bottom:15px; color:#555;">Thank you for your order!<br>Your delivery is being processed.</div>
            <div id="instantDeliveryPerson" style="margin-bottom:10px; color:#333;"></div>
            <div id="instantDeliveryPhone" style="margin-bottom:10px; color:#333;"></div>
            <div style="font-size:0.95rem; color:#666;">You will receive a call when your food reaches near you.</div>
        </div>
    </div>

    <script>
    function selectPayment(method) {
        document.getElementById('paymentMethod').value = method;
        // Remove selected class from all options
        document.querySelectorAll('.payment-option').forEach(option => {
            option.classList.remove('selected');
        });
        // Add selected class to clicked option
        event.currentTarget.classList.add('selected');
        // Show appropriate form/message
        if (method === 'cash') {
            document.getElementById('cashMessage').style.display = 'block';
            document.getElementById('mobileForm').style.display = 'none';
            document.getElementById('payButtonContainer').style.display = 'none';
            document.getElementById('confirmButtonContainer').style.display = 'block';
        } else if (method === 'mobile') {
            document.getElementById('cashMessage').style.display = 'none';
            document.getElementById('mobileForm').style.display = 'block';
            document.getElementById('payButtonContainer').style.display = 'block';
            document.getElementById('confirmButtonContainer').style.display = 'none';
        }
    }

    // Show instant confirmation modal
    function showInstantConfirmation(deliveryPerson, deliveryPhone) {
        var modal = document.getElementById('instantConfirmationModal');
        var deliveryDiv = document.getElementById('instantDeliveryPerson');
        var phoneDiv = document.getElementById('instantDeliveryPhone');
        
        if (deliveryPerson && deliveryPerson !== 'Not assigned yet') {
            deliveryDiv.innerHTML = 'Your order will be delivered by <strong>' + deliveryPerson + '</strong>';
            if (deliveryPhone) {
                phoneDiv.innerHTML = 'Phone Number: <strong>' + deliveryPhone + '</strong>';
            } else {
                phoneDiv.innerHTML = '';
            }
        } else {
            deliveryDiv.innerHTML = 'We will assign a delivery person soon and you will get a message once assigned.';
            phoneDiv.innerHTML = '';
        }
        modal.style.display = 'flex';
    }

    function processCashPayment() {
        // Prevent double submission
        if (window._processingCash) return;
        window._processingCash = true;
        var deliveryPerson = '';
        var deliveryPhone = '';
        <?php if (!empty($delivery_persons)) {
            $names = array_map(function($p){return $p['Name'];}, $delivery_persons);
            $phones = array_map(function($p){return $p['Phno'];}, $delivery_persons);
            $jsNames = json_encode($names);
            $jsPhones = json_encode($phones);
        ?>
        var deliveryPersons = <?php echo $jsNames; ?>;
        var deliveryPhones = <?php echo $jsPhones; ?>;
        if (deliveryPersons.length > 0) {
            var randomIndex = Math.floor(Math.random() * deliveryPersons.length);
            deliveryPerson = deliveryPersons[randomIndex];
            deliveryPhone = deliveryPhones[randomIndex];
        } else {
            deliveryPerson = 'Not assigned yet';
            deliveryPhone = '';
        }
        <?php } else { ?>
        deliveryPerson = 'Not assigned yet';
        deliveryPhone = '';
        <?php } ?>
        showInstantConfirmation(deliveryPerson, deliveryPhone);
        // Delay form submission to allow user to see the modal
        setTimeout(function() {
            document.getElementById('processingOverlay').style.display = 'flex';
            document.getElementById('paymentMethod').value = 'cash';
            document.getElementById('paymentForm').submit();
        }, 30000); // 30 seconds
    }

    // Intercept Pay Now button for mobile payment to show modal for 30 seconds
    document.addEventListener('DOMContentLoaded', function() {
        var payBtn = document.querySelector('.btn-pay');
        if (payBtn) {
            payBtn.addEventListener('click', function(e) {
                // Only intercept if mobile payment is selected
                var paymentMethod = document.getElementById('paymentMethod').value;
                if (paymentMethod === 'mobile') {
                    e.preventDefault();
                    if (window._processingMobile) return;
                    window._processingMobile = true;
                    const transactionId = document.getElementById('transaction_id').value;
                    const fullName = document.getElementById('full_name').value;
                    if (!transactionId || !fullName) {
                        alert('Please fill in all required fields for mobile payment.');
                        window._processingMobile = false;
                        return;
                    }
                    var deliveryPerson = '';
                    var deliveryPhone = '';
                    <?php if (!empty($delivery_persons)) { ?>
                    var deliveryPersons = <?php echo $jsNames; ?>;
                    var deliveryPhones = <?php echo $jsPhones; ?>;
                    if (deliveryPersons.length > 0) {
                        var randomIndex = Math.floor(Math.random() * deliveryPersons.length);
                        deliveryPerson = deliveryPersons[randomIndex];
                        deliveryPhone = deliveryPhones[randomIndex];
                    } else {
                        deliveryPerson = 'Not assigned yet';
                        deliveryPhone = '';
                    }
                    <?php } else { ?>
                    deliveryPerson = 'Not assigned yet';
                    deliveryPhone = '';
                    <?php } ?>
                    showInstantConfirmation(deliveryPerson, deliveryPhone);
                    setTimeout(function() {
                        document.getElementById('processingOverlay').style.display = 'flex';
                        document.getElementById('paymentForm').submit();
                    }, 30000); // 30 seconds
                }
            });
        }
    });

    // Validate payment method selection on form submit (for both cash and mobile)
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const paymentMethod = document.getElementById('paymentMethod').value;
        if (!paymentMethod) {
            e.preventDefault();
            alert('Please select a payment method.');
            return;
        }
        // For mobile, prevent double submit (handled by Pay Now click)
        if (paymentMethod === 'mobile' && window._processingMobile) {
            e.preventDefault();
            return;
        }
        // For cash, prevent double submit (handled by processCashPayment)
        if (paymentMethod === 'cash' && window._processingCash) {
            e.preventDefault();
            return;
        }
    });

    // Hide processing overlay and modal when page loads
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('processingOverlay').style.display = 'none';

        document.getElementById('instantConfirmationModal').style.display = 'none';
    });

    // Also hide processing overlay if page is loaded from cache (back button)
    window.addEventListener('pageshow', function(event) {
        document.getElementById('processingOverlay').style.display = 'none';
        document.getElementById('instantConfirmationModal').style.display = 'none';
    });
    </script>
</body>
<?php
// Now safe to clear the session variables after HTML output
if (isset($_GET['success'])) {
    unset($_SESSION['payment_success']);
    unset($_SESSION['delivery_person_name']);
    unset($_SESSION['delivery_person_phno']);
}
?>
</html>