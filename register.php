<?php
session_start();

// Check if order details exist in session
if (!isset($_SESSION['order_details'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$name = $phone = $address = $email = $gender = '';
$errors = array();
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and save customer details
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    
    // Validation rules
    if (empty($name)) {
        $errors['name'] = "Full name is required";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $name)) {
        $errors['name'] = "Only letters and spaces allowed";
    } elseif (strlen($name) < 3) {
        $errors['name'] = "Name must be at least 3 characters";
    }
    
    if (empty($phone)) {
        $errors['phone'] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors['phone'] = "Please enter a valid 10-digit phone number";
    }
    
    if (empty($address)) {
        $errors['address'] = "Delivery address is required";
    } elseif (strlen($address) < 10) {
        $errors['address'] = "Please provide a more detailed address";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($gender)) {
        $errors['gender'] = "Please select your gender";
    }
    
    // If no errors, proceed with registration and order creation
    if (empty($errors)) {
        // Generate customer ID
        $custId = 'CUST' . time();
        
        // Database connection
        include("back_end/database_connectivity.php");
        
        // Save to database (allow duplicate phone numbers)
        $stmt = $conn->prepare("INSERT INTO Customer_det (Cust_ID, Name, Phno, Address, Email, Gender) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $custId, $name, $phone, $address, $email, $gender);

        if ($stmt->execute()) {
            // Store customer ID in session
            $_SESSION['customer_id'] = $custId;
            
            // Get order details from session
            $orderDetails = $_SESSION['order_details'];
            
            // Generate order number
            $orderNumber = time() . rand(100, 999);
            
            // Get current date and time
            $orderDate = date('Y-m-d H:i:s');
            $status = 'Pending'; // Initial status
            
            // Insert into Orders table
            $orderStmt = $conn->prepare("INSERT INTO Orders (Ord_number, Cust_id, Ord_date, Status, Total_Tax, Delivery_charge, Grand_Tot) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)");
            $orderStmt->bind_param("ssssddd", $orderNumber, $custId, $orderDate, $status, 
                                  $orderDetails['total_tax'], $orderDetails['delivery_charge'], $orderDetails['grand_total']);
            
            if ($orderStmt->execute()) {
                // Get the last inserted order ID
                $orderId = $conn->insert_id;
                
                // Insert into Ordered_items table for each item
                foreach ($orderDetails['order_items'] as $item) {
                    $orderItemStmt = $conn->prepare("INSERT INTO Ordered_items (Ord_id, Food_id, FoodName, Unit_Price, Quantity, Dis_Amount, Tax_percent, Final_item_price) 
                                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $orderItemStmt->bind_param("iisdiidd", $orderId, $item['food_id'], $item['food_name'], 
                                              $item['unit_price'], $item['quantity'], $item['discount_amount'], 
                                              $item['tax_percent'], $item['final_unit_price']);
                    
                    if (!$orderItemStmt->execute()) {
                        // Handle error
                        $errors['database'] = "Error creating order items: " . $conn->error;
                        break;
                    }
                    
                    $orderItemStmt->close();
                }
                
                // Store order ID in session for payment page
                $_SESSION['order_id'] = $orderId;
                
                // Redirect to payment page
                header("Location: payment.php");
                exit();
                
            } else {
                $errors['database'] = "Error creating order: " . $conn->error;
            }
            
            $orderStmt->close();
        } else {
            $errors['database'] = "Registration failed. Please try again.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Food Order System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            padding: 20px;
            background-image: url("images/backgrounds/back4.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="email"]:focus,
        textarea:focus,
        select:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .error {
            color: #ff4757;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .error-field {
            border-color: #ff4757 !important;
        }
        
        .success {
            color: #2ed573;
            text-align: center;
            margin-bottom: 15px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #45a049;
        }
        
        .required {
            color: #ff4757;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registration</h2>
        
        <?php if (!empty($errors['database'])): ?>
            <div class="error"><?php echo $errors['database']; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="registrationForm">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" 
                    class="<?php echo isset($errors['name']) ? 'error-field' : ''; ?>">
                <?php if (isset($errors['name'])): ?>
                    <div class="error"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" 
                    class="<?php echo isset($errors['phone']) ? 'error-field' : ''; ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="error"><?php echo $errors['phone']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="address">Delivery Address <span class="required">*</span></label>
                <textarea id="address" name="address" 
                    class="<?php echo isset($errors['address']) ? 'error-field' : ''; ?>"><?php echo htmlspecialchars($address); ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <div class="error"><?php echo $errors['address']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email (Optional)</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                    class="<?php echo isset($errors['email']) ? 'error-field' : ''; ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="error"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="gender">Gender <span class="required">*</span></label>
                <select id="gender" name="gender" class="<?php echo isset($errors['gender']) ? 'error-field' : ''; ?>">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($gender == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
                <?php if (isset($errors['gender'])): ?>
                    <div class="error"><?php echo $errors['gender']; ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit">Register & Continue</button>
        </form>
    </div>

    <script>
        // Client-side validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            let isValid = true;
            const name = document.getElementById('name');
            const phone = document.getElementById('phone');
            const address = document.getElementById('address');
            const gender = document.getElementById('gender');
            
            // Reset error styles
            [name, phone, address, gender].forEach(field => {
                field.classList.remove('error-field');
            });
            
            // Validate name
            if (name.value.trim() === '') {
                showError(name, 'Full name is required');
                isValid = false;
            } else if (!/^[a-zA-Z ]*$/.test(name.value)) {
                showError(name, 'Only letters and spaces allowed');
                isValid = false;
            } else if (name.value.trim().length < 3) {
                showError(name, 'Name must be at least 3 characters');
                isValid = false;
            }
            
            // Validate phone
            if (phone.value.trim() === '') {
                showError(phone, 'Phone number is required');
                isValid = false;
            } else if (!/^[0-9]{10}$/.test(phone.value)) {
                showError(phone, 'Please enter a valid 10-digit phone number');
                isValid = false;
            }
            
            // Validate address
            if (address.value.trim() === '') {
                showError(address, 'Delivery address is required');
                isValid = false;
            } else if (address.value.trim().length < 10) {
                showError(address, 'Please provide a more detailed address');
                isValid = false;
            }
            
            // Validate gender
            if (gender.value === '') {
                showError(gender, 'Please select your gender');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        function showError(field, message) {
            field.classList.add('error-field');
            
            // Remove any existing error message
            const existingError = field.nextElementSibling;
            if (existingError && existingError.classList.contains('error')) {
                existingError.remove();
            }
            
            // Create and insert error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.textContent = message;
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        
        // Real-time validation for phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    </script>
</body>
</html>