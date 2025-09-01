<?php
include("auth.php");
include("database_connectivity.php");

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    }
}

// Fetch all admins
$result = $conn->query("SELECT id, name, username FROM admins ORDER BY id ASC");

$pageTitle = "Admin Management";
include('partials/header.php'); 
?>

<!-- [Rest of your HTML remains exactly the same] -->
 <div class="container">
    <div class="header-actions">
        <a href="add_admin.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Admin
        </a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>••••••••</td>
                        <td class="actions">
                            <a href="edit_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="admin.php?delete=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this admin?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No admin accounts found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
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