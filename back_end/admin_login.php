<?php
session_start();
require 'database_connectivity.php';

// Configuration
const SETUP_KEY = 'f3b9d6a1c2e7435f8d9ab2c6ef31b3a5';
const DEFAULT_USERNAME = 'admin';
const DEFAULT_NAME = 'System Administrator';
const DEFAULT_EMAIL = 'admin@example.com';

// First-time setup check
$admin_count = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM admins");
if ($result) {
    $row = $result->fetch_assoc();
    $admin_count = (int)$row['count'];
    $result->close();
}

// First-time setup
if ($admin_count === 0) {
    if (isset($_GET['setup_key']) && hash_equals(SETUP_KEY, $_GET['setup_key'])) {
        $temp_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
        
        $username = DEFAULT_USERNAME;
        $name = DEFAULT_NAME;
        $email = DEFAULT_EMAIL;
        
        $stmt = $conn->prepare("INSERT INTO admins (username, password, name, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $name, $email);
        
        if ($stmt->execute()) {
            echo "<div style='padding:20px;background:#e0ffe0;border:2px solid green;margin:20px auto;width:80%;'>
                <h3>✅ Setup Complete</h3>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> $temp_password</p>
                <p><strong>Email:</strong> $email</p>
                <p style='color:red;'>⚠️ Save this password immediately!</p>
                <a href='admin_login.php'>Proceed to Login</a>
            </div>";
            exit();
        } else {
            die("Setup failed: " . $conn->error);
        }
    } else {
        // Show setup instructions if no admins exist
        echo "<div style='padding:20px;background:#fff3cd;border:2px solid #ffeaa7;margin:20px auto;width:80%;'>
            <h3>⚠️ Initial Setup Required</h3>
            <p>No administrator accounts found. You need to set up the system.</p>
            <p>Please visit the following URL to create the default admin account:</p>
            <p><code>" . htmlspecialchars($_SERVER['PHP_SELF']) . "?setup_key=" . SETUP_KEY . "</code></p>
            <p><em>Note: This key should be kept secret and removed after setup.</em></p>
        </div>";
        exit();
    }
}

// Handle login
$error = "";
$success = "";
$show_forgot_form = false;
$show_reset_form = false;
$reset_email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if it's a login request or a forgot password request
    if (isset($_POST['login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Both fields are required";
        } else {
            $stmt = $conn->prepare("SELECT id, username, password, name FROM admins WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $db_user, $db_pass, $db_name);
                    $stmt->fetch();
                    
                    if (password_verify($password, $db_pass)) {
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $id;
                        $_SESSION['admin_name'] = $db_name;
                        $_SESSION['last_activity'] = time();
                        
                        $redirect_url = $_SESSION['redirect_url'] ?? 'admin.php';
                        unset($_SESSION['redirect_url']);
                        header("Location: " . $redirect_url);
                        exit();
                    } else {
                        $error = "Invalid credentials";
                    }
                } else {
                    $error = "Invalid credentials";
                }
                $stmt->close();
            } else {
                $error = "Database error";
            }
        }
    } 
    // Handle forgot username/password request
    elseif (isset($_POST['forgot'])) {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = "Email is required";
        } else {
            $stmt = $conn->prepare("SELECT id, username, name FROM admins WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    $_SESSION['reset_email'] = $email;
                    $show_reset_form = true;
                    $reset_email = $email;
                } else {
                    $error = "No account found with that email address";
                }
                $stmt->close();
            } else {
                $error = "Database error";
            }
        }
    }
    // Handle reset password/username request
    elseif (isset($_POST['reset'])) {
        $email = $_SESSION['reset_email'] ?? '';
        $new_username = trim($_POST['username'] ?? '');
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($email)) {
            $error = "Invalid reset request";
            $show_forgot_form = true;
        } elseif (empty($new_username)) {
            $error = "Username is required";
            $show_reset_form = true;
            $reset_email = $email;
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error = "Both password fields are required";
            $show_reset_form = true;
            $reset_email = $email;
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match";
            $show_reset_form = true;
            $reset_email = $email;
        } else {
            // Check if username is already taken (excluding current admin)
            $check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND email != ?");
            $check_stmt->bind_param("ss", $new_username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Username is already taken";
                $show_reset_form = true;
                $reset_email = $email;
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE email = ?");
                $update_stmt->bind_param("sss", $new_username, $hashed_password, $email);
                
                if ($update_stmt->execute()) {
                    $success = "Username and password updated successfully. You can now login with your new credentials.";
                    unset($_SESSION['reset_email']);
                    $show_reset_form = false;
                } else {
                    $error = "Error updating credentials: " . $conn->error;
                    $show_reset_form = true;
                    $reset_email = $email;
                }
            }
        }
    }
}

// Handle "Forgot username/password" link click
if (isset($_GET['forgot'])) {
    $show_forgot_form = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4cb5f5;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
        }
        
        .login-form {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 181, 245, 0.2);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background-color: #ffeaea;
            color: var(--error-color);
            border: 1px solid #ffc9c9;
        }
        
        .alert-success {
            background-color: #eaffea;
            color: var(--success-color);
            border: 1px solid #c9ffc9;
        }
        
        .alert-icon {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
            font-size: 15px;
        }
        
        .back-link:hover {
            text-decoration: underline;
            color: var(--primary-color);
        }
        
        .email-display {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-color);
            font-weight: 500;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Access your administration dashboard</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!$show_forgot_form && !$show_reset_form): ?>
                <!-- Login Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Login</button>
                    
                    <a href="?forgot=1" class="forgot-link">Forgot username or password?</a>
                </form>
            <?php elseif ($show_forgot_form && !$show_reset_form): ?>
                <!-- Forgot Password Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <button type="submit" name="forgot" class="btn">Reset Credentials</button>
                    
                    <a href="admin_login.php" class="back-link">Back to Login</a>
                </form>
            <?php elseif ($show_reset_form): ?>
                <!-- Reset Password Form -->
                <div class="email-display">
                    Resetting credentials for: <?php echo htmlspecialchars($reset_email); ?>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">New Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="reset" class="btn">Update Credentials</button>
                    
                    <a href="admin_login.php" class="back-link">Back to Login</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>