<?php
include("auth.php");
include("database_connectivity.php");

$error = '';
$success = '';

// Get category data
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT Cat_ID, Title, Description, Image, Active FROM categories WHERE Cat_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cat_id = $_POST['cat_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $active = $_POST['active'];
    $current_image = $_POST['current_image'];
    
    // Check if Cat_ID is being changed to one that already exists (if different from original)
    if ($cat_id != $id) {
        $check_stmt = $conn->prepare("SELECT Cat_ID FROM categories WHERE Cat_ID = ?");
        $check_stmt->bind_param("i", $cat_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Category ID $cat_id already exists. Please choose a different ID.";
        }
    }
    
    if (empty($error)) {
        // Handle image upload
        $imageName = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Delete old image if it exists
                if (!empty($current_image) && file_exists('images/categories/' . $current_image)) {
                    unlink('images/categories/' . $current_image);
                }
                
                $imageName = 'cat-' . $cat_id . '.' . $fileExtension;
                $uploadPath = 'images/categories/' . $imageName;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.";
            }
        } else {
            // If Cat_ID changed but image not changed, rename the existing image
            if ($cat_id != $id && !empty($current_image)) {
                $imageName = $current_image;
                
                if (file_exists('images/categories/' . $current_image)) {
                    rename('images/categories/' . $current_image, 'images/categories/' . $newImageName);
                    $imageName = $newImageName;
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE categories SET Cat_ID = ?, Title = ?, Description = ?, Image = ?, Active = ? WHERE Cat_ID = ?");
            $stmt->bind_param("issssi", $cat_id, $title, $description, $imageName, $active, $id);
            
            if ($stmt->execute()) {
                $success = "Category updated successfully!";
                header("Location: categories.php");
                exit();
            } else {
                $error = "Error updating category: " . $conn->error;
            }
        }
    }
}

$pageTitle = "Edit Category";
include('partials/header.php');
?>

<div class="container">
    <h2>Edit Category</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="category-form">
        <input type="hidden" name="current_image" value="<?php echo $category['Image']; ?>">
        
        <div class="form-group">
            <label for="cat_id">Category ID:</label>
            <input type="number" id="cat_id" name="cat_id" value="<?php echo $category['Cat_ID']; ?>" min="1" required>
            <small>Unique identifier for the category</small>
        </div>
        
        <div class="form-group">
            <label for="title">Category Title:</label>
            <input type="text" id="title" name="title" value="<?php echo $category['Title']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($category['Description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Category Image:</label>
            <?php if (!empty($category['Image']) && file_exists('images/categories/' . $category['Image'])): ?>
                <div class="current-image" style="margin-bottom: 15px;">
                    <img src="images/categories/<?php echo $category['Image']; ?>" 
                         alt="Current image" 
                         style="max-width: 200px; height: auto; border-radius: 4px; border: 1px solid #ddd;">
                    <p style="margin: 5px 0 0; font-size: 14px; color: #666;">Current Image</p>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <small>Upload a new image to replace the current one</small>
        </div>
        
        <div class="form-group">
            <label for="active">Active:</label>
            <select id="active" name="active" required>
                <option value="Yes" <?php echo $category['Active'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                <option value="No" <?php echo $category['Active'] == 'No' ? 'selected' : ''; ?>>No</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Category</button>
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