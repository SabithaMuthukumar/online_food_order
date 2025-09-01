<?php
include("auth.php");
include("database_connectivity.php");

$error = '';
$success = '';

// Get the next available Cat_ID
$next_id_result = $conn->query("SELECT MAX(Cat_ID) + 1 as next_id FROM categories");
$next_id_row = $next_id_result->fetch_assoc();
$next_id = $next_id_row['next_id'] ? $next_id_row['next_id'] : 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cat_id = $_POST['cat_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $active = $_POST['active'];
    
    // Check if Cat_ID already exists
    $check_stmt = $conn->prepare("SELECT Cat_ID FROM categories WHERE Cat_ID = ?");
    $check_stmt->bind_param("i", $cat_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Category ID $cat_id already exists. Please choose a different ID.";
    } else {
        // Handle image upload
        $imageName = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $imageName = $_FILES['image']['name'];
                $uploadPath = 'images/categories/' . $imageName;
                
                // Create categories directory if it doesn't exist
                if (!is_dir('images/categories')) {
                    mkdir('images/categories', 0777, true);
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.";
            }
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO categories (Cat_ID, Title, Description, Image, Active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $cat_id, $title, $description, $imageName, $active);
            
            if ($stmt->execute()) {
                $success = "Category added successfully!";
                header("Location: categories.php");
                exit();
            } else {
                $error = "Error adding category: " . $conn->error;
            }
        }
    }
}

$pageTitle = "Add New Category";
include('partials/header.php');
?>

<div class="container">
    <h2>Add New Category</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="category-form">
        <div class="form-group">
            <label for="cat_id">Category ID:</label>
            <input type="number" id="cat_id" name="cat_id" value="<?php echo $next_id; ?>" min="1" required>
            <small>Unique identifier for the category</small>
        </div>
        
        <div class="form-group">
            <label for="title">Category Title:</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Category Image:</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            <small>Recommended size: 300x300 pixels</small>
        </div>
        
        <div class="form-group">
            <label for="active">Active:</label>
            <select id="active" name="active" required>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Category</button>
            <a href="categories.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<style>
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.category-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    font-family: inherit;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group small {
    color: #666;
    font-size: 14px;
    display: block;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.alert {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-error {
    background-color: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.alert-success {
    background-color: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.btn {
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-size: 16px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-primary {
    background-color: #3498db;
}

.btn-cancel {
    background-color: #95a5a6;
}
</style>

<?php
$conn->close();
include('partials/footer.php');
?>