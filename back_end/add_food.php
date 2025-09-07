<?php
include("auth.php");
include("database_connectivity.php");

$error = '';
$success = '';

// Get the next available Food_id
$next_id_result = $conn->query("SELECT MAX(Food_id) + 1 as next_id FROM foods");
$next_id_row = $next_id_result->fetch_assoc();
$next_id = $next_id_row['next_id'] ? $next_id_row['next_id'] : 1;

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
    
    // Check if Food_id already exists
    $check_stmt = $conn->prepare("SELECT Food_id FROM foods WHERE Food_id = ?");
    $check_stmt->bind_param("i", $food_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Food ID $food_id already exists. Please choose a different ID.";
    } else {
        // Handle image upload
$imageName = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($fileExtension, $allowedExtensions)) {
        // Use the original filename but sanitize it
        $originalName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName); // Replace special characters with underscores
        $imageName = $sanitizedName . '.' . $fileExtension;
        
        $uploadPath = 'images/foods/' . $imageName;
        
        // Create foods directory if it doesn't exist
        if (!is_dir('images/foods')) {
            mkdir('images/foods', 0777, true);
        }
        
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
}
        if (empty($error)) {
            // UPDATED QUERY TO INCLUDE ALL FIELDS
            $stmt = $conn->prepare("INSERT INTO foods (Food_id, Cat_id, Food_name, Price, Discount_percent, Description, Image, Tax_percent, Active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisdsssss", $food_id, $cat_id, $food_name, $price, $discount_percent, $description, $imageName, $tax_percent, $active);
            
            if ($stmt->execute()) {
                $success = "Food added successfully!";
                header("Location: foods.php");
                exit();
            } else {
                $error = "Error adding food: " . $conn->error;
            }
        }
    }
}

$pageTitle = "Add New Food";
include('partials/header.php');
?>

<div class="container">
    <h2>Add New Food</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="food-form">
        <div class="form-group">
            <label for="food_id">Food ID:</label>
            <input type="number" id="food_id" name="food_id" value="<?php echo $next_id; ?>" min="1" required>
            <small>Unique identifier for the food</small>
        </div>
        
        <div class="form-group">
            <label for="cat_id">Category:</label>
            <select id="cat_id" name="cat_id" required>
                <option value="">Select Category</option>
                <?php foreach($categories as $category): ?>
                    <option value="<?php echo $category['Cat_ID']; ?>"><?php echo htmlspecialchars($category['Title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="food_name">Food Name:</label>
            <input type="text" id="food_name" name="food_name" required>
        </div>
        
        <div class="form-group">
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>
        
        <div class="form-group">
            <label for="discount_percent">Discount %:</label>
            <input type="number" id="discount_percent" name="discount_percent" step="0.01" min="0" max="100" value="0" required>
        </div>
        
        <div class="form-group">
            <label for="tax_percent">Tax %:</label>
            <input type="number" id="tax_percent" name="tax_percent" step="0.01" min="0" max="100" value="0" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Food Image:</label>
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
            <button type="submit" class="btn btn-primary">Add Food</button>
            <a href="foods.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<!-- Styles remain the same -->
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