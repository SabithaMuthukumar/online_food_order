Transaction filter
<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "Transaction List";
include('partials/header.php');

// Default query
$query = "SELECT Trans_id, Ord_id, Pay_Mode, Pay_Status, Amount, date FROM Trans WHERE 1=1";

// Apply filters if submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['ord_id'])) {
        $ord_id = $conn->real_escape_string($_GET['ord_id']);
        $query .= " AND Ord_id LIKE '%$ord_id%'";
    }

    if (!empty($_GET['pay_mode'])) {
        $pay_mode = $conn->real_escape_string($_GET['pay_mode']);
        $query .= " AND Pay_Mode = '$pay_mode'";
    }

    if (!empty($_GET['pay_status'])) {
        $pay_status = $conn->real_escape_string($_GET['pay_status']);
        $query .= " AND Pay_Status = '$pay_status'";
    }

    if (!empty($_GET['min_amount']) && is_numeric($_GET['min_amount'])) {
        $min_amount = (float)$_GET['min_amount'];
        $query .= " AND Amount >= $min_amount";
    }

    if (!empty($_GET['max_amount']) && is_numeric($_GET['max_amount'])) {
        $max_amount = (float)$_GET['max_amount'];
        $query .= " AND Amount <= $max_amount";
    }

    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $from_date = $conn->real_escape_string($_GET['from_date']);
        $to_date = $conn->real_escape_string($_GET['to_date']);
        $query .= " AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
    }
}

$query .= " ORDER BY Trans_id DESC";
$result = $conn->query($query);
?>

<div class="container">
    <h2>Transaction Records</h2>

    <!-- Filter Form -->
    <form method="get" class="filter-form">
        <input type="text" name="ord_id" placeholder="Search by Order ID" value="<?php echo isset($_GET['ord_id']) ? htmlspecialchars($_GET['ord_id']) : ''; ?>">

        <select name="pay_mode">
            <option value="">All Payment Modes</option>
            <option value="Cash on Delivery" <?php if(isset($_GET['pay_mode']) && $_GET['pay_mode']=='Cash on Delivery') echo 'selected'; ?>>Cash on Delivery</option>
            <option value="Mobile Banking" <?php if(isset($_GET['pay_mode']) && $_GET['pay_mode']=='Mobile Banking') echo 'selected'; ?>>Mobile Banking</option>
        </select>

        <select name="pay_status">
            <option value="">All Statuses</option>
            <option value="Pending" <?php if(isset($_GET['pay_status']) && $_GET['pay_status']=='Pending') echo 'selected'; ?>>Pending</option>
            <option value="Paid" <?php if(isset($_GET['pay_status']) && $_GET['pay_status']=='Paid') echo 'selected'; ?>>Paid</option>
            <option value="Failed" <?php if(isset($_GET['pay_status']) && $_GET['pay_status']=='Failed') echo 'selected'; ?>>Failed</option>
            <option value="Refunded" <?php if(isset($_GET['pay_status']) && $_GET['pay_status']=='Refunded') echo 'selected'; ?>>Refunded</option>
        </select>

        <input type="number" step="0.01" name="min_amount" placeholder="Min Amount" value="<?php echo isset($_GET['min_amount']) ? htmlspecialchars($_GET['min_amount']) : ''; ?>">
        <input type="number" step="0.01" name="max_amount" placeholder="Max Amount" value="<?php echo isset($_GET['max_amount']) ? htmlspecialchars($_GET['max_amount']) : ''; ?>">

        <input type="date" name="from_date" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>" placeholder="From Date">
        <input type="date" name="to_date" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>" placeholder="To Date">

        <button type="submit">Filter</button>
        <a href="transactions.php" class="reset-btn">Reset</a>
    </form>

    <!-- Transaction Table -->
    <form method="post" action="update_transaction.php">
        <table class="trans-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Order ID</th>
                    <th>Payment Mode</th>
                    <th>Payment Status</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Trans_id']; ?></td>
                        <td><?php echo $row['Ord_id']; ?></td>
                        <td>
                            <select name="pay_mode[<?php echo $row['Trans_id']; ?>]" class="dropdown">
                                <?php
                                $modes = ['Cash on Delivery', 'Mobile Banking'];
                                foreach ($modes as $mode) {
                                    $selected = ($row['Pay_Mode'] === $mode) ? 'selected' : '';
                                    echo "<option value=\"$mode\" $selected>$mode</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <select name="pay_status[<?php echo $row['Trans_id']; ?>]" class="dropdown">
                                <?php
                                $statuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
                                foreach ($statuses as $status) {
                                    $selected = ($row['Pay_Status'] === $status) ? 'selected' : '';
                                    echo "<option value=\"$status\" $selected>$status</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td><?php echo number_format((float)$row['Amount'], 2); ?></td>
                        <td><?php echo isset($row['date']) ? date('Y-m-d H:i', strtotime($row['date'])) : ''; ?></td>
                        <td>
                            <button type="submit" name="save" value="<?php echo $row['Trans_id']; ?>" class="save-btn">Save</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:#888;">
                            No transaction records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<style>
.container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.filter-form {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.filter-form input,
.filter-form select {
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.filter-form button {
    padding: 6px 12px;
    background-color: #2980b9;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.filter-form .reset-btn {
    padding: 6px 12px;
    background-color: #7f8c8d;
    color: white;
    border-radius: 4px;
    text-decoration: none;
}

.trans-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.trans-table th,
.trans-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.trans-table th {
    background-color: #34495e;
    color: white;
    font-weight: 600;
}

.trans-table tr:hover {
    background-color: #f9f9f9;
}

.dropdown {
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

.save-btn {
    padding: 6px 12px;
    background-color: #27ae60;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.save-btn:hover {
    background-color: #219150;
}
</style>

<?php
$conn->close();
include('partials/footer.php');
?>