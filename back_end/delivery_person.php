<?php
include("auth.php");
include("database_connectivity.php");

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete the delivery person record
    $stmt = $conn->prepare("DELETE FROM delivery_persons WHERE Del_person_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: delivery_person.php");
        exit();
    }
}

// Fetch all delivery persons
$query = "SELECT Del_person_id, Name, Phno, Status FROM delivery_persons ORDER BY Del_person_id DESC";
$result = $conn->query($query);

$pageTitle = "Delivery Persons Management";
include('partials/header.php');
?>

<div class="table-container">
    <div class="header-actions">
        <a href="add_delivery_person.php" class="btn btn-primary">Add New Delivery Person</a>
    </div>

    <h2>Delivery Persons Management</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Del_person_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['Name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Phno']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                            <?php echo htmlspecialchars($row['Status']); ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="edit_delivery_person.php?id=<?php echo $row['Del_person_id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="delivery_person.php?delete=<?php echo $row['Del_person_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this delivery person?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No delivery persons found.</div>
    <?php endif; ?>
</div>

<style>
.table-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-container h2 {
    margin-top: 0;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

.header-actions {
    margin-bottom: 20px;
    text-align: right;
}

.styled-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
    text-align: left;
}

.styled-table th, .styled-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
}

.styled-table th {
    background-color: #f2f2f2;
    color: #333;
    font-weight: 600;
}

.styled-table tr:hover {
    background-color: #f9f9f9;
}

.no-data {
    text-align: center;
    color: #777;
    font-size: 16px;
    padding: 20px;
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

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.status-busy {
    background-color: #fff3cd;
    color: #856404;
}

.status-available {
    background-color: #cce5ff;
    color: #004085;
}
</style>

<?php 
$conn->close();
include('partials/footer.php'); 
?>