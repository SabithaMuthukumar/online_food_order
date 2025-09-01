<?php
session_start();
require 'database_connectivity.php';

// Configuration
const SETUP_KEY = 'f3b9d6a1c2e7435f8d9ab2c6ef31b3a5';
const DEFAULT_USERNAME = 'admin';
const DEFAULT_NAME = 'System Administrator';

// First-time setup check
$admin_count = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM admins");
if ($result) {
    $row = $result->fetch_assoc();
    $admin_count = (int)$row['count'];
    $result->close();
}

// First-time setup
if ($admin_count === 0 && isset($_GET['setup_key']) && hash_equals(SETUP_KEY, $_GET['setup_key'])) {
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    
    // Fixed: Use variables for binding
    $username = DEFAULT_USERNAME;
    $name = DEFAULT_NAME;
    
    $stmt = $conn->prepare("INSERT INTO admins (username, password, name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $name);
    
    if ($stmt->execute()) {
        echo "<div style='padding:20px;background:#e0ffe0;border:2px solid green;margin:20px auto;width:80%;'>
            <h3>✅ Setup Complete</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> $temp_password</p>
            <p style='color:red;'>⚠️ Save this password immediately!</p>
            <a href='admin_login.php'>Proceed to Login</a>
        </div>";
        exit();
    } else {
        die("Setup failed: " . $conn->error);
    }
}

// Handle login
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Both fields are required";
    } else {
        // Fixed: Proper variable binding
        $stmt = $conn->prepare("SELECT id, username, password, name FROM admins WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username); // $username is now a variable
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --danger-color: #e74c3c;
            --text-color: #333;
            --light-gray: #f5f5f5;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            margin: 0 0 0.5rem;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            background-color: var(--primary-color);
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
        
        .error-message {
            color: var(--danger-color);
            background-color: #fde8e8;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .setup-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .setup-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .setup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Access your administration dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <?php if ($admin_count === 0): ?>
            <div class="setup-link">
                <a href="admin_login.php?setup_key=<?= SETUP_KEY ?>">First-time setup</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>