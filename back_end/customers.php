<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "Customer Details";
include('partials/header.php');

// Fetch customer records
$result = $conn->query("SELECT Cust_id, Name, Phno, Address, Email, Gender FROM customer_det ORDER BY Cust_id DESC");
?>

<div class="table-container">
    <h2>Customer Details</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Gender</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Cust_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Phno']); ?></td>
                        <td><?php echo htmlspecialchars($row['Address']); ?></td>
                        <td><?php echo htmlspecialchars($row['Email']); ?></td>
                        <td><?php echo htmlspecialchars($row['Gender']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No customer records found.</div>
    <?php endif; ?>
</div>

<style>
.table-container {
    max-width: 1000px;
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
</style>

<?php 
$conn->close();
include('partials/footer.php'); 
?>