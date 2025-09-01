<?php
include("auth.php");
include("database_connectivity.php");

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // First get the image filename
    $stmt = $conn->prepare("SELECT Image FROM foods WHERE Food_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $food = $result->fetch_assoc();
    
    // Delete the food
    $stmt = $conn->prepare("DELETE FROM foods WHERE Food_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete the image file if it exists
        if (!empty($food['Image']) && file_exists('images/foods/' . $food['Image'])) {
            unlink('images/foods/' . $food['Image']);
        }
        header("Location: foods.php");
        exit();
    }
}

// Fetch all foods with category names - UPDATED TO INCLUDE ALL FIELDS
$result = $conn->query("
    SELECT f.*, c.Title as Category 
    FROM foods f 
    LEFT JOIN categories c ON f.Cat_id = c.Cat_ID 
    ORDER BY f.Food_id ASC
");

$pageTitle = "Foods Management";
include('partials/header.php'); 
?>

<div class="container">
    <div class="header-actions">
        <a href="add_food.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Food
        </a>
    </div>

    <div class="table-responsive" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 120px;">Name</th>
                    <th style="width: 100px;">Category</th>
                    <th style="width: 80px;">Price</th>
                    <th style="width: 80px;">Discount %</th>
                    <th style="width: 80px;">Tax %</th>
                    <th style="width: 200px;">Description</th>
                    <th style="width: 100px;">Image</th>
                    <th style="width: 70px;">Active</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr style="height: 100px;"> <!-- Fixed row height -->
                        <td><?php echo htmlspecialchars($row['Food_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['Food_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Category']); ?></td>
                        <td>$<?php echo htmlspecialchars($row['Price']); ?></td>
                        <td><?php echo htmlspecialchars($row['Discount_percent']); ?>%</td>
                        <td><?php echo htmlspecialchars($row['Tax_percent']); ?>%</td>
                        <td title="<?php echo htmlspecialchars($row['Description']); ?>">
                            <?php echo htmlspecialchars(substr($row['Description'], 0, 50)); ?><?php echo strlen($row['Description']) > 50 ? '...' : ''; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['Image']) && file_exists('images/foods/' . $row['Image'])): ?>
                                <img src="images/foods/<?php echo htmlspecialchars($row['Image']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['Food_name']); ?>" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                    No image
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['Active']); ?></td>
                        <td class="actions">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <a href="edit_food.php?id=<?php echo $row['Food_id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="foods.php?delete=<?php echo $row['Food_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this food item?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No foods found</td>
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

.table-responsive {
    overflow-x: auto;
    width: 100%;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    min-width: 1000px;
}

.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle; /* Align content vertically in middle */
    height: 100px; /* Fixed height for all cells */
}

.admin-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    top: 0;
}

.text-center {
    text-align: center;
}

.actions {
    white-space: nowrap;
}

.btn {
    padding: 6px 10px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: background-color 0.3s;
    white-space: nowrap;
    text-align: center;
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
    font-size: 12px;
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .admin-table th, .admin-table td {
        padding: 8px 10px;
        font-size: 14px;
    }
    
    .btn {
        padding: 5px 8px;
        font-size: 11px;
    }
}
</style>

<?php 
$conn->close();
include('partials/footer.php'); 
?>