<?php
session_start();
include("back_end/database_connectivity.php");

// Initialize variables
$errors = [];
$success = '';
$emailVerified = false;
$identifier = '';
$password = '';
$newPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    // Forgot password step 1: verify email
    if (isset($_POST['forgot_password'])) {
        if (!empty($identifier)) {
            $checkEmail = $conn->prepare("SELECT Cust_ID FROM customer_det WHERE Email = ?");
            $checkEmail->bind_param("s", $identifier);
            $checkEmail->execute();
            $checkResult = $checkEmail->get_result();

            if ($checkResult->num_rows > 0) {
                $emailVerified = true;
                $success = "Email verified. Please enter your new password.";
            } else {
                $errors[] = "Email not found";
            }
            $checkEmail->close();
        } else {
            $errors[] = "Please enter your email to reset password";
        }
    }

    // Forgot password step 2: update password
    if (isset($_POST['reset_password'])) {
        if (!empty($identifier) && !empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE customer_det SET Password = ? WHERE Email = ?");
            $update->bind_param("ss", $hashedPassword, $identifier);
            if ($update->execute()) {
                $success = "Password updated successfully. You can now log in.";
            } else {
                $errors[] = "Failed to update password.";
            }
            $update->close();
        } else {
            $errors[] = "Email and new password are required.";
        }
    }

    // Login logic
    if (isset($_POST['login']) && empty($errors)) {
        if (!empty($identifier) && !empty($password)) {
            $checkUser = $conn->prepare("SELECT Cust_ID, Name, Password FROM customer_det WHERE Email = ?");
            $checkUser->bind_param("s", $identifier);
            $checkUser->execute();
            $result = $checkUser->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['Password'])) {
                    $_SESSION['customer_id'] = $user['Cust_ID'];
                    $_SESSION['customer_name'] = $user['Name'];
                    $_SESSION['customer_email'] = $identifier;
                    
                    // Check if user has previous orders
                    $checkOrders = $conn->prepare("SELECT Ord_id FROM orders WHERE Cust_id = ? ORDER BY Ord_date DESC LIMIT 1");
                    $checkOrders->bind_param("s", $user['Cust_ID']);
                    $checkOrders->execute();
                    $orderResult = $checkOrders->get_result();
                    
                    if ($orderResult->num_rows > 0) {
                        $order = $orderResult->fetch_assoc();
                        $_SESSION['last_order_id'] = $order['Ord_id'];
                        
                        // Set flag to show suggestion after login
                        $_SESSION['show_last_order_suggestion'] = true;
                        
                        // Redirect to a page that will show the suggestion
                        header("Location: last_order_suggestion.php");
                        exit();
                    } else {
                        // No previous orders, go directly to transaction page
                        header("Location: transaction.php");
                        exit();
                    }
                } else {
                    $errors[] = "Incorrect password";
                }
            } else {
                $errors[] = "Email not found";
            }
            $checkUser->close();
        } else {
            $errors[] = "Email and password are required.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Food Order System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
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
        .container { max-width: 450px; width: 90%; }
        .login-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        h1 { text-align: center; color: #ff6b6b; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 12px 15px; border: 2px solid #ddd;
            border-radius: 8px; font-size: 1rem; transition: border-color 0.3s;
        }
        input:focus {
            border-color: #ff6b6b; outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
        }
        .btn {
            display: inline-block; padding: 12px 24px; color: white;
            border: none; border-radius: 50px; font-size: 1.1rem;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-align: center; text-decoration: none; width: 100%;
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
        .btn-forgot {
            background: transparent; color: #ff6b6b;
            border: none; padding: 5px 0; font-size: 0.9rem;
            cursor: pointer; text-decoration: underline;
        }
        .error {
            color: #ff4757; font-size: 14px;
            margin-top: 5px; text-align: center;
        }
        .success-message {
            color: #28a745; font-weight: 600;
            margin: 10px 0; text-align: center;
        }
        .register-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1>Login</h1>
            <p class="subtitle">Welcome back! Please login to continue</p>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="identifier">Email *</label>
                    <input type="email" id="identifier" name="identifier" value="<?= htmlspecialchars($identifier) ?>" required>
                </div>

                <?php if ($emailVerified || isset($_POST['reset_password'])): ?>
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-primary">Update Password</button>
                <?php else: ?>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div style="text-align: right; margin-bottom: 20px;">
                        <button type="submit" name="forgot_password" class="btn-forgot">Forgot Password?</button>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                <?php endif; ?>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
</body>
</html>