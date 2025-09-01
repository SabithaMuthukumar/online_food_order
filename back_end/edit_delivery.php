<?php
include("auth.php");
include("database_connectivity.php");

// Check if we're editing an existing delivery
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: delivery.php");
    exit();
}

$id = (int)$_GET['id'];
$pageTitle = "Edit Delivery";

// Fetch the delivery record
$stmt = $conn->prepare("SELECT * FROM delivery WHERE Del_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: delivery.php");
    exit();
}

$delivery = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ord_id = $_POST['ord_id'];
    $cust_id = $_POST['cust_id'];
    $del_address = $_POST['del_address'];
    $del_date = $_POST['del_date'];
    $del_person_id = $_POST['del_person_id'];
    $del_status = $_POST['del_status'];
    
    // Update existing delivery
    $stmt = $conn->prepare("UPDATE delivery SET Ord_id=?, Cust_id=?, Del_address=?, Del_date=?, Del_person_id=?, Del_status=? WHERE Del_ID=?");
    $stmt->bind_param("iissisi", $ord_id, $cust_id, $del_address, $del_date, $del_person_id, $del_status, $id);
    
    if ($stmt->execute()) {
        header("Location: delivery.php");
        exit();
    } else {
        $error = "Error updating delivery: " . $conn->error;
    }
}

// Fetch orders, customers, and delivery persons for dropdowns
$orders = $conn->query("SELECT Ord_id, Ord_number FROM orders ORDER BY Ord_id DESC");
$customers = $conn->query("SELECT Cust_id, Name FROM customer_det ORDER BY Name ASC");
$delivery_persons = $conn->query("SELECT Del_person_id, Name FROM delivery_persons ORDER BY Name ASC");

include('partials/header.php');
?>

<div class="form-container">
    <div class="header-actions">
        <a href="delivery.php" class="btn btn-back">Back to Deliveries</a>
    </div>

    <h2>Edit Delivery #<?php echo $delivery['Del_ID']; ?></h2>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="edit_delivery.php?id=<?php echo $id; ?>" method="post">
        <div class="form-row">
            <div class="form-group">
                <label for="ord_id">Order <span class="required">*</span></label>
                <select class="form-control" id="ord_id" name="ord_id" required>
                    <option value="">Select an Order</option>
                    <?php while($order = $orders->fetch_assoc()): ?>
                        <option value="<?php echo $order['Ord_id']; ?>" 
                            <?php if ($order['Ord_id'] == $delivery['Ord_id']) echo 'selected'; ?>>
                            #<?php echo $order['Ord_number'] . ' (ID: ' . $order['Ord_id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cust_id">Customer <span class="required">*</span></label>
                <select class="form-control" id="cust_id" name="cust_id" required>
                    <option value="">Select a Customer</option>
                    <?php while($customer = $customers->fetch_assoc()): ?>
                        <option value="<?php echo $customer['Cust_id']; ?>" 
                            <?php if ($customer['Cust_id'] == $delivery['Cust_id']) echo 'selected'; ?>>
                            <?php echo $customer['Name'] . ' (ID: ' . $customer['Cust_id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="del_address">Delivery Address <span class="required">*</span></label>
            <textarea class="form-control" id="del_address" name="del_address" rows="3" required><?php echo htmlspecialchars($delivery['Del_address']); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="del_date">Delivery Date <span class="required">*</span></label>
                <input type="datetime-local" class="form-control" id="del_date" name="del_date" 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($delivery['Del_date'])); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="del_person_id">Delivery Person <span class="required">*</span></label>
                <select class="form-control" id="del_person_id" name="del_person_id" required>
                    <option value="">Select a Delivery Person</option>
                    <?php while($person = $delivery_persons->fetch_assoc()): ?>
                        <option value="<?php echo $person['Del_person_id']; ?>" 
                            <?php if ($person['Del_person_id'] == $delivery['Del_person_id']) echo 'selected'; ?>>
                            <?php echo $person['Name'] . ' (ID: ' . $person['Del_person_id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="del_status">Delivery Status <span class="required">*</span></label>
            <select class="form-control" id="del_status" name="del_status" required>
                <option value="Pending" <?php if ($delivery['Del_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Processing" <?php if ($delivery['Del_status'] == 'Processing') echo 'selected'; ?>>Processing</option>
                <option value="Shipped" <?php if ($delivery['Del_status'] == 'Shipped') echo 'selected'; ?>>Shipped</option>
                <option value="Out for Delivery" <?php if ($delivery['Del_status'] == 'Out for Delivery') echo 'selected'; ?>>Out for Delivery</option>
                <option value="Delivered" <?php if ($delivery['Del_status'] == 'Delivered') echo 'selected'; ?>>Delivered</option>
                <option value="Cancelled" <?php if ($delivery['Del_status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-submit">Update Delivery</button>
    </form>
    
    <div class="delivery-history">
        <h3>Delivery History</h3>
        <p>Last modified: <?php echo date('F j, Y, g:i a', strtotime($delivery['Del_date'])); ?></p>
        <p>Current status: 
            <span class="status-badge status-<?php echo strtolower($delivery['Del_status']); ?>">
                <?php echo $delivery['Del_status']; ?>
            </span>
        </p>
    </div>
</div>

<style>
.form-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-container h2 {
    margin-top: 0;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    flex: 1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border 0.3s;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
}

.btn-back {
    background-color: #7f8c8d;
}

.btn-submit {
    background-color: #f39c12;
    padding: 12px 20px;
    font-size: 16px;
    width: 100%;
}

.error-message {
    background-color: #ffecec;
    color: #e74c3c;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #e74c3c;
}

.required {
    color: #e74c3c;
}

.delivery-history {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.delivery-history h3 {
    margin-bottom: 10px;
    color: #2c3e50;
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

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<script>
// Set minimum datetime for delivery date to current time
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('del_date').min = localDateTime;
});
</script>

<?php
$conn->close();
include('partials/footer.php');
?>