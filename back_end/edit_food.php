<?php
include("auth.php");
include("database_connectivity.php");

$error = '';
$success = '';

// Get food data - UPDATED TO INCLUDE ALL FIELDS
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM foods WHERE Food_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$food = $result->fetch_assoc();

// Get all categories for dropdown and store in array
$categories = [];
$categories_result = $conn->query("SELECT Cat_ID, Title FROM categories WHERE Active = 'Yes' ORDER BY Title");
while($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $food_id = $_POST['food_id'];
    $cat_id = $_POST['cat_id'];
    $food_name = $_POST['food_name'];
    $price = $_POST['price'];
    $discount_percent = $_POST['discount_percent'];
    $tax_percent = $_POST['tax_percent'];
    $description = $_POST['description'];
    $active = $_POST['active'];
    $current_image = $_POST['current_image'];
    
    // Check if Food_id is being changed to one that already exists
    if ($food_id != $id) {
        $check_stmt = $conn->prepare("SELECT Food_id FROM foods WHERE Food_id = ?");
        $check_stmt->bind_param("i", $food_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Food ID $food_id already exists. Please choose a different ID.";
        }
    }
    
    if (empty($error)) {
        // Handle image upload
$imageName = $current_image;

// If a new image is uploaded
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($fileExtension, $allowedExtensions)) {
        // Delete old image if it exists
        if (!empty($current_image) && file_exists('images/foods/' . $current_image)) {
            unlink('images/foods/' . $current_image);
        }
        
        // Use the original filename but sanitize it
        $originalName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName); // Replace special characters with underscores
        $imageName = $sanitizedName . '.' . $fileExtension;
        
        $uploadPath = 'images/foods/' . $imageName;
        
        // Check if file already exists and append number if needed
        $counter = 1;
        $baseName = $sanitizedName;
        while (file_exists($uploadPath)) {
            $imageName = $baseName . '_' . $counter . '.' . $fileExtension;
            $uploadPath = 'images/foods/' . $imageName;
            $counter++;
        }
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $error = "Failed to upload image.";
        }
    } else {
        $error = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.";
    }
} else if ($food_id != $id && !empty($current_image)) {
    // If Food_id changed but no new image uploaded, we don't need to rename the file
    // since we're now using the original filename instead of food-{id} format
    $imageName = $current_image;
}
        
        if (empty($error)) {
            // UPDATED QUERY TO INCLUDE ALL FIELDS
            $stmt = $conn->prepare("UPDATE foods SET Food_id = ?, Cat_id = ?, Food_name = ?, Price = ?, Discount_percent = ?, Description = ?, Image = ?, Tax_percent = ?, Active = ? WHERE Food_id = ?");
            $stmt->bind_param("iisdsssssi", $food_id, $cat_id, $food_name, $price, $discount_percent, $description, $imageName, $tax_percent, $active, $id);
            
            if ($stmt->execute()) {
                $success = "Food updated successfully!";
                // Redirect to foods list page
                header("Location: foods.php");
                exit();
            } else {
                $error = "Error updating food: " . $conn->error;
            }
        }
    }
}

$pageTitle = "Edit Food";
include('partials/header.php');
?>

<div class="container">
    <h2>Edit Food</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="food-form">
        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($food['Image']); ?>">
        
        <div class="form-group">
            <label for="food_id">Food ID:</label>
            <input type="number" id="food_id" name="food_id" value="<?php echo htmlspecialchars($food['Food_id']); ?>" min="1" required>
            <small>Unique identifier for the food</small>
        </div>
        
        <div class="form-group">
            <label for="cat_id">Category:</label>
            <select id="cat_id" name="cat_id" required>
                <option value="">Select Category</option>
                <?php foreach($categories as $category): ?>
                    <option value="<?php echo $category['Cat_ID']; ?>" <?php echo $category['Cat_ID'] == $food['Cat_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['Title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="food_name">Food Name:</label>
            <input type="text" id="food_name" name="food_name" value="<?php echo htmlspecialchars($food['Food_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($food['Price']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="discount_percent">Discount %:</label>
            <input type="number" id="discount_percent" name="discount_percent" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($food['Discount_percent']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="tax_percent">Tax %:</label>
            <input type="number" id="tax_percent" name="tax_percent" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($food['Tax_percent']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($food['Description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Food Image:</label>
            <?php if (!empty($food['Image'])): ?>
                <div class="current-image" style="margin-bottom: 15px;">
                    <?php if (file_exists('images/foods/' . $food['Image'])): ?>
                        <img src="images/foods/<?php echo htmlspecialchars($food['Image']); ?>" 
                             alt="Current image" 
                             style="max-width: 200px; height: auto; border-radius: 4px; border: 1px solid #ddd;">
                    <?php else: ?>
                        <div style="width: 200px; height: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; border: 1px solid #ddd;">
                            Image not found
                        </div>
                    <?php endif; ?>
                    <p style="margin: 5px 0 0; font-size: 14px; color: #666;">
                        Current Image: <?php echo htmlspecialchars($food['Image']); ?>
                    </p>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <small>Upload a new image to replace the current one. If no new image is selected, the current image will be kept.</small>
        </div>
        
        <div class="form-group">
            <label for="active">Active:</label>
            <select id="active" name="active" required>
                <option value="Yes" <?php echo $food['Active'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                <option value="No" <?php echo $food['Active'] == 'No' ? 'selected' : ''; ?>>No</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Food</button>
            <a href="foods.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<style>
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.food-form {
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