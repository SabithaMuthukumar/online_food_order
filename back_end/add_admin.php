<?php
include("auth.php");
include("database_connectivity.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Check if username exists
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (name, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $username, $hashed_password);
            
            if ($stmt->execute()) {
                header("Location: admin.php");
                exit();
            } else {
                $error = "Error adding admin. Please try again.";
            }
        }
        $check_stmt->close();
    }
}

$pageTitle = "Add New Admin";
include('partials/header.php'); 
?>

<!-- [Rest of your HTML remains exactly the same] -->


<div class="form-container">
    <h2>Add New Admin</h2>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="clean-form">
        <div class="input-group">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        
        <div class="input-group">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
        </div>
        
        <div class="input-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Admin
            </button>
            <a href="admin.php" class="btn btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
.form-container {
    max-width: 500px;
    margin: 40px auto;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-container h2 {
    margin-top: 0;
    color: #333;
    text-align: center;
}

.clean-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.input-group label {
    font-weight: 500;
    color: #555;
}

.input-group input {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.error-message {
    color: #d32f2f;
    background: #fde8e8;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 15px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background-color: #4CAF50;
}

.btn-primary:hover {
    background-color: #388E3C;
}

.btn-cancel {
    background-color: #6c757d;
}

.btn-cancel:hover {
    background-color: #5a6268;
}
</style>


<?php 
$conn->close();
include('partials/footer.php'); 
?>