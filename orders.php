Order filter 
<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "View Orders";
include('partials/header.php');

// Capture filter inputs
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$cust_filter = isset($_GET['customer']) ? trim($_GET['customer']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Base query
$sql = "SELECT Ord_id, Ord_number, Cust_id, Ord_date, Status, Total_Tax, Delivery_charge, Grand_Tot 
        FROM Orders WHERE 1=1";

// Apply filters
if ($status_filter != '') {
    $sql .= " AND Status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($cust_filter != '') {
    $sql .= " AND Cust_id = '" . $conn->real_escape_string($cust_filter) . "'";
}
if ($from_date != '' && $to_date != '') {
    $sql .= " AND DATE(Ord_date) BETWEEN '" . $conn->real_escape_string($from_date) . "' 
              AND '" . $conn->real_escape_string($to_date) . "'";
}

$sql .= " ORDER BY Ord_date DESC";
$orders_result = $conn->query($sql);
?>

<div class="container">
    <h2>Order List</h2>

    <!-- Filter Form -->
    <form method="GET" action="" class="filter-form filter-row">
        <div class="filter-group">
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="">-- All --</option>
                <option value="Pending" <?= ($status_filter=='Pending'?'selected':''); ?>>Pending</option>
                <option value="Confirmed" <?= ($status_filter=='Confirmed'?'selected':''); ?>>Confirmed</option>
                <option value="Delivered" <?= ($status_filter=='Delivered'?'selected':''); ?>>Delivered</option>
                <option value="Cancelled" <?= ($status_filter=='Cancelled'?'selected':''); ?>>Cancelled</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="customer">Customer ID:</label>
            <input type="text" id="customer" name="customer" value="<?= htmlspecialchars($cust_filter); ?>">
        </div>
        <div class="filter-group">
            <label for="from_date">From:</label>
            <input type="date" id="from_date" name="from_date" value="<?= $from_date; ?>">
        </div>
        <div class="filter-group">
            <label for="to_date">To:</label>
            <input type="date" id="to_date" name="to_date" value="<?= $to_date; ?>">
        </div>
        <div class="filter-group filter-actions">
            <button type="submit">Apply Filters</button>
            <a href="orders.php" class="reset-btn">Reset</a>
        </div>
    </form>

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
                        <td><?= $order['Ord_id']; ?></td>
                        <td><?= $order['Ord_number']; ?></td>
                        <td><?= $order['Cust_id']; ?></td>
                        <td>
                        <?php
                        $ordDate = $order['Ord_date'];
                        if (!empty($ordDate) && $ordDate !== '0000-00-00 00:00:00' && strtotime($ordDate) !== false) {
                            echo date('M j, Y H:i', strtotime($ordDate));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                        </td>
                        <td>
                            <select name="status[<?= $order['Ord_id']; ?>]" class="status-dropdown">
                                <?php
                                $statuses = ['Pending', 'Confirmed', 'Delivered', 'Cancelled'];
                                foreach ($statuses as $status) {
                                    $selected = ($order['Status'] === $status) ? 'selected' : '';
                                    echo "<option value=\"$status\" $selected>$status</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td><?= number_format((float)$order['Total_Tax'], 2); ?></td>
                        <td><?= number_format((float)$order['Delivery_charge'], 2); ?></td>
                        <td><?= number_format((float)$order['Grand_Tot'], 2); ?></td>
                        <td>
                            <button type="submit" name="save" value="<?= $order['Ord_id']; ?>" class="save-btn">Save</button>
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
/* Filter form row alignment */
.filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 18px;
    margin-bottom: 20px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}
.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 120px;
}
.filter-group label {
    font-weight: 600;
    margin-bottom: 3px;
}
.filter-group input,
.filter-group select {
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
}
.filter-actions {
    flex-direction: row;
    align-items: flex-end;
    gap: 8px;
    margin-top: 18px;
}
.filter-actions button {
    padding: 6px 12px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.filter-actions .reset-btn {
    padding: 6px 12px;
    background-color: #e74c3c;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-left: 10px;
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