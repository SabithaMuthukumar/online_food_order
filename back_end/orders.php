<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "View Orders";
include('partials/header.php');

// Fetch all orders
$orders_result = $conn->query("SELECT Ord_id, Ord_number, Cust_id, Ord_date, Status, Total_Tax, Delivery_charge, Grand_Tot FROM Orders ORDER BY Ord_date DESC");
?>

<div class="container">
    <h2>Order List</h2>

    <form method="post" action="update_order_status.php">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Number</th>
                    <th>Customer ID</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Total Tax</th>
                    <th>Delivery Charge</th>
                    <th>Grand Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $order['Ord_id']; ?></td>
                        <td><?php echo $order['Ord_number']; ?></td>
                        <td><?php echo $order['Cust_id']; ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($order['Ord_date'])); ?></td>
                        <td>
                            <select name="status[<?php echo $order['Ord_id']; ?>]" class="status-dropdown">
                                <?php
                                $statuses = ['Pending', 'Confirmed', 'Delivered', 'Cancelled'];
                                foreach ($statuses as $status) {
                                    $selected = ($order['Status'] === $status) ? 'selected' : '';
                                    echo "<option value=\"$status\" $selected>$status</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td><?php echo number_format((float)$order['Total_Tax'], 2); ?></td>
                        <td><?php echo number_format((float)$order['Delivery_charge'], 2); ?></td>
                        <td><?php echo number_format((float)$order['Grand_Tot'], 2); ?></td>
                        <td>
                            <button type="submit" name="save" value="<?php echo $order['Ord_id']; ?>" class="save-btn">Save</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:20px; color:#888;">
                            No orders found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.orders-table th,
.orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.orders-table th {
    background-color: #3498db;
    color: white;
    font-weight: 600;
}

.orders-table tr:hover {
    background-color: #f5f5f5;
}

.status-dropdown {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f8f9fa;
}

.save-btn {
    padding: 6px 12px;
    background-color: #2ecc71;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.save-btn:hover {
    background-color: #27ae60;
}
</style>

<?php
$conn->close();
include('partials/footer.php');
?>