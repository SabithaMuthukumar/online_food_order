<?php
include("auth.php");
include("database_connectivity.php");

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete the delivery record
    $stmt = $conn->prepare("DELETE FROM delivery WHERE Del_ID = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: delivery.php");
        exit();
    }
}

// Fetch all delivery records with related information
$query = "SELECT d.Del_ID, d.Ord_id, d.Cust_id, d.Del_address, d.Del_date, 
                 d.Del_person_id, d.Del_status, 
                 o.Ord_number as order_number,
                 c.Name as customer_name,
                 dp.Name as delivery_person_name
          FROM delivery d
          LEFT JOIN orders o ON d.Ord_id = o.Ord_id
          LEFT JOIN customer_det c ON d.Cust_id = c.Cust_id
          LEFT JOIN delivery_persons dp ON d.Del_person_id = dp.Del_person_id
          ORDER BY d.Del_date DESC";

$result = $conn->query($query);

$pageTitle = "Delivery Management";
include('partials/header.php');
?>

<div class="table-container">
    <div class="header-actions">
        <a href="add_delivery.php" class="btn btn-primary">Add New Delivery</a>
    </div>

    <h2>Delivery Management</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Number</th>
                    <th>Customer</th>
                    <th>Delivery Address</th>
                    <th>Delivery Date</th>
                    <th>Delivery Person</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Del_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['order_number'] ?? $row['Ord_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name'] ?? $row['Cust_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['Del_address']); ?></td>
                    <td><?php echo !empty($row['Del_date']) ? date('M j, Y H:i', strtotime($row['Del_date'])) : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars($row['delivery_person_name'] ?? $row['Del_person_id']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($row['Del_status']); ?>">
                            <?php echo htmlspecialchars($row['Del_status']); ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="edit_delivery.php?id=<?php echo $row['Del_ID']; ?>" class="btn btn-edit">Edit</a>
                        <a href="delivery.php?delete=<?php echo $row['Del_ID']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this delivery record?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No delivery records found.</div>
    <?php endif; ?>
</div>

<style>
.table-container {
    max-width: 1800px;
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

.status-delivered {
    background-color: #d4edda;
    color: #155724;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-processing {
    background-color: #cce5ff;
    color: #004085;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.status-shipped {
    background-color: #d1ecf1;
    color: #0c5460;
}
</style>

<?php 
$conn->close();
include('partials/footer.php'); 
?>