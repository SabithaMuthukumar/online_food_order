<?php
include("auth.php");
include("database_connectivity.php");

// Check if we're editing an existing delivery person
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: delivery_person.php");
    exit();
}

$id = (int)$_GET['id'];
$pageTitle = "Edit Delivery Person";
$error = "";

// Fetch the delivery person record
$stmt = $conn->prepare("SELECT * FROM delivery_persons WHERE Del_person_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: delivery_person.php");
    exit();
}

$delivery_person = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phno = trim($_POST['phno']);
    $status = $_POST['status'];
    
    // Validate inputs
    if (empty($name) || empty($phno) || empty($status)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phno)) {
        $error = "Phone number must be exactly 10 digits.";
    } else {
        // Check if phone number already exists (excluding current record)
        $check_stmt = $conn->prepare("SELECT Del_person_id FROM delivery_persons WHERE Phno = ? AND Del_person_id != ?");
        $check_stmt->bind_param("si", $phno, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Phone number already exists in the system.";
        } else {
            // Update existing delivery person
            $stmt = $conn->prepare("UPDATE delivery_persons SET Name=?, Phno=?, Status=? WHERE Del_person_id=?");
            $stmt->bind_param("sisi", $name, $phno, $status, $id);
            
            if ($stmt->execute()) {
                header("Location: delivery_person.php");
                exit();
            } else {
                $error = "Error updating delivery person: " . $conn->error;
            }
        }
    }
}

include('partials/header.php');
?>

<div class="form-container">
    <div class="header-actions">
        <a href="delivery_person.php" class="btn btn-back">Back to Delivery Persons</a>
    </div>

    <h2>Edit Delivery Person #<?php echo $delivery_person['Del_person_id']; ?></h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="edit_delivery_person.php?id=<?php echo $id; ?>" method="post" onsubmit="return validateForm()">
        <div class="form-group">
            <label for="name">Name <span class="required">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($delivery_person['Name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phno">Phone Number <span class="required">*</span></label>
            <input type="text" class="form-control" id="phno" name="phno" value="<?php echo isset($_POST['phno']) ? htmlspecialchars($_POST['phno']) : htmlspecialchars($delivery_person['Phno']); ?>" 
                   pattern="[0-9]{10}" title="Please enter exactly 10 digits" required>
            <small class="form-text">Must be exactly 10 digits (e.g., 9876543210)</small>
        </div>
        
        <div class="form-group">
            <label for="status">Status <span class="required">*</span></label>
            <select class="form-control" id="status" name="status" required>
                <option value="Busy" <?php echo ((isset($_POST['status']) && $_POST['status'] == 'Busy') || $delivery_person['Status'] == 'Busy') ? 'selected' : ''; ?>>Busy</option>
                <option value="Available" <?php echo ((isset($_POST['status']) && $_POST['status'] == 'Available') || $delivery_person['Status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-submit">Update Delivery Person</button>
    </form>
    
    <div class="delivery-person-history">
        <h3>Delivery Person Information</h3>
        <p>ID: <?php echo $delivery_person['Del_person_id']; ?></p>
        <p>Current status: 
            <span class="status-badge status-<?php echo strtolower($delivery_person['Status']); ?>">
                <?php echo $delivery_person['Status']; ?>
            </span>
        </p>
    </div>
</div>

<style>
.form-container {
    max-width: 600px;
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

.form-group {
    margin-bottom: 20px;
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

.delivery-person-history {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.delivery-person-history h3 {
    margin-bottom: 10px;
    color: #2c3e50;
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

.form-text {
    color: #6c757d;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}
</style>

<script>
function validateForm() {
    const phoneInput = document.getElementById('phno');
    const phoneValue = phoneInput.value.trim();
    
    // Validate phone number format (exactly 10 digits)
    const phoneRegex = /^[0-9]{10}$/;
    if (!phoneRegex.test(phoneValue)) {
        alert('Please enter a valid 10-digit phone number.');
        phoneInput.focus();
        return false;
    }
    
    return true;
}

// Add real-time validation
document.getElementById('phno').addEventListener('input', function(e) {
    // Allow only numbers
    this.value = this.value.replace(/[^0-9]/g, '');
    
    // Limit to 10 digits
    if (this.value.length > 10) {
        this.value = this.value.slice(0, 10);
    }
});
</script>

<?php
$conn->close();
include('partials/footer.php');
?>