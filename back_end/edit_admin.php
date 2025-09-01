<?php
include("auth.php");
include("database_connectivity.php");

$error = '';
$admin = [];

// Fetch admin data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, name, username FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin) {
        $error = "Admin not found";
    }
} else {
    $error = "Invalid admin ID";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    
    if (empty($name) || empty($username)) {
        $error = "Name and username are required";
    } else {
        // Check for duplicate username (excluding current admin)
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $username, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Password update logic
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE admins SET name=?, username=?, password=? WHERE id=?");
                $update_stmt->bind_param("sssi", $name, $username, $password, $id);
            } else {
                $update_stmt = $conn->prepare("UPDATE admins SET name=?, username=? WHERE id=?");
                $update_stmt->bind_param("ssi", $name, $username, $id);
            }
            
            if ($update_stmt->execute()) {
                header("Location: admin.php");
                exit();
            } else {
                $error = "Error updating admin. Please try again.";
            }
        }
    }
}

$pageTitle = "Edit Admin";
include('partials/header.php'); 
?>

<!-- [Rest of your HTML remains exactly the same] -->
 <div class="form-container">
    <h2>Edit Admin</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($admin)): ?>
    <form method="POST" class="clean-form">
        <div class="input-group">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" required>
        </div>
        
        <div class="input-group">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
        </div>
        
        <div class="input-group">
            <label>New Password (leave blank to keep current):</label>
            <input type="password" name="password">
        </div>
        
        <div class="button-group">
            <a href="admin.php" class="cancel-btn">Cancel</a>
            <button type="submit" class="submit-btn">Update Admin</button>
        </div>
    </form>
    <?php endif; ?>
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

.button-group {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.submit-btn, .cancel-btn {
    flex: 1;
    padding: 12px;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
}

.submit-btn {
    background: #4CAF50;
    color: white;
    border: none;
}

.submit-btn:hover {
    background: #388E3C;
}

.cancel-btn {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.cancel-btn:hover {
    background: #e0e0e0;
}
</style>



<?php 
$conn->close();
include('partials/footer.php'); 
?>