<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "Add New Delivery";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ord_id = $_POST['ord_id'];
    $cust_id = $_POST['cust_id'];
    $del_address = $_POST['del_address'];
    $del_date = $_POST['del_date'];
    $del_person_id = $_POST['del_person_id'];
    $del_status = $_POST['del_status'];
    
    // Insert new delivery
    $stmt = $conn->prepare("INSERT INTO delivery (Ord_id, Cust_id, Del_address, Del_date, Del_person_id, Del_status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $ord_id, $cust_id, $del_address, $del_date, $del_person_id, $del_status);
    
    if ($stmt->execute()) {
        header("Location: delivery.php");
        exit();
    } else {
        $error = "Error adding delivery: " . $conn->error;
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

    <h2>Add New Delivery</h2>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="add_delivery.php" method="post">
        <div class="form-row">
            <div class="form-group">
                <label for="ord_id">Order <span class="required">*</span></label>
                <select class="form-control" id="ord_id" name="ord_id" required>
                    <option value="">Select an Order</option>
                    <?php while($order = $orders->fetch_assoc()): ?>
                        <option value="<?php echo $order['Ord_id']; ?>">
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
                        <option value="<?php echo $customer['Cust_id']; ?>">
                            <?php echo $customer['Name'] . ' (ID: ' . $customer['Cust_id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="del_address">Delivery Address <span class="required">*</span></label>
            <textarea class="form-control" id="del_address" name="del_address" rows="3" required></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="del_date">Delivery Date <span class="required">*</span></label>
                <input type="datetime-local" class="form-control" id="del_date" name="del_date" required>
            </div>
            
            <div class="form-group">
                <label for="del_person_id">Delivery Person <span class="required">*</span></label>
                <select class="form-control" id="del_person_id" name="del_person_id" required>
                    <option value="">Select a Delivery Person</option>
                    <?php while($person = $delivery_persons->fetch_assoc()): ?>
                        <option value="<?php echo $person['Del_person_id']; ?>">
                            <?php echo $person['Name'] . ' (ID: ' . $person['Del_person_id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="del_status">Delivery Status <span class="required">*</span></label>
            <select class="form-control" id="del_status" name="del_status" required>
                <option value="Pending" selected>Pending</option>
                <option value="Processing">Processing</option>
                <option value="Shipped">Shipped</option>
                <option value="Out for Delivery">Out for Delivery</option>
                <option value="Delivered">Delivered</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-submit">Create Delivery</button>
    </form>
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
    background-color: #2ecc71;
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