<?php
include("auth.php");
include("database_connectivity.php");

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // First get the image filename
    $stmt = $conn->prepare("SELECT Image FROM categories WHERE Cat_ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    // Delete the category
    $stmt = $conn->prepare("DELETE FROM categories WHERE Cat_ID = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete the image file if it exists
        if (!empty($category['Image']) && file_exists('images/categories/' . $category['Image'])) {
            unlink('images/categories/' . $category['Image']);
        }
        header("Location: categories.php");
        exit();
    }
}

// Fetch all categories
$result = $conn->query("SELECT Cat_ID, Title, Description, Image, Active FROM categories ORDER BY Cat_ID ASC");

$pageTitle = "Categories Management";
include('partials/header.php'); 
?>

<div class="container">
    <div class="header-actions">
        <a href="add_category.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Category
        </a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Cat_ID']); ?></td>
                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                        <td><?php echo htmlspecialchars($row['Description']); ?></td>
                        <td>
                            <?php if (!empty($row['Image']) && file_exists('images/categories/' . $row['Image'])): ?>
                                <img src="images/categories/<?php echo htmlspecialchars($row['Image']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['Title']); ?>" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                    No image
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['Active']); ?></td>
                        <td class="actions">
                            <a href="edit_category.php?id=<?php echo $row['Cat_ID']; ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="categories.php?delete=<?php echo $row['Cat_ID']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No categories found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.header-actions {
    margin-bottom: 20px;
    text-align: right;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.admin-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.admin-table td:nth-child(3) {
    max-width: 300px;
    word-wrap: break-word;
}

.text-center {
    text-align: center;
}

.actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 12px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s;
}

.btn:hover {
    opacity: 0.9;
}

.btn-primary {
    background-color: #3498db;
}

.btn-edit {
    background-color: #f39c12;
}

.btn-delete {
    background-color: #e74c3c;
}

.btn i {
    font-size: 14px;
}
</style>

<?php 
$conn->close();
include('partials/footer.php'); 
?>