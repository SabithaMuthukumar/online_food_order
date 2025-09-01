<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "Transaction List";
include('partials/header.php');

// Fetch all transactions
$query = "SELECT Trans_id, Ord_id, Pay_Mode, Pay_Status, Tot_paid_amt FROM Trans ORDER BY Trans_id DESC";
$result = $conn->query($query);
?>

<div class="container">
    <h2>Transaction Records</h2>

    <form method="post" action="update_transaction.php">
        <table class="trans-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Order ID</th>
                    <th>Payment Mode</th>
                    <th>Payment Status</th>
                    <th>Total Paid</th>
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
                                $modes = ['Cash', 'Card', 'UPI', 'Net Banking'];
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
                        <td><?php echo number_format((float)$row['Tot_paid_amt'], 2); ?></td>
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