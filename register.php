<?php
session_start();
include("back_end/database_connectivity.php");

// Initialize variables
$errors = array();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $cust_id = strtoupper(uniqid('CUST')); // Generate unique customer ID
    $name = trim($_POST['name']);
    $phno = trim($_POST['phno']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $gender = trim($_POST['gender']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($name)) $errors[] = "Name is required";
    if (empty($phno)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    
    // Check if email already exists
    if (empty($errors) && !empty($email)) {
        $checkEmail = $conn->prepare("SELECT Cust_ID FROM customer_det WHERE Email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkResult = $checkEmail->get_result();
        
        if ($checkResult->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $checkEmail->close();
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert customer into database
        $stmt = $conn->prepare("INSERT INTO customer_det (Cust_ID, Name, Phno, Address, Email, Gender, Password, Registered_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $cust_id, $name, $phno, $address, $email, $gender, $hashed_password);
        
        if ($stmt->execute()) {
            // Store in session AFTER successful insertion
            $_SESSION['customer_id'] = $cust_id;
            $_SESSION['customer_name'] = $name;

            $success = 'Registration successful!';

            // Remove redirect so success message is shown
            // header("Location: register.php");
            // exit();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Food Order System</title>
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
            max-width: 500px;
            width: 90%;
        }
        
        .register-form {
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
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
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
            width: 100%;
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-form">
            <h1>Create Account</h1>
            <p class="subtitle">Join us to order delicious food</p>
            
            <?php if (!empty($errors)): ?>
                <div class="error" style="text-align: center; margin-bottom: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="phno">Phone Number *</label>
                    <input type="tel" id="phno" name="phno" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" placeholder="Enter your delivery address" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>