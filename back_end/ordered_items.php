<?php
include("auth.php");
include("database_connectivity.php");

$pageTitle = "Ordered Items";
include('partials/header.php');

// Fetch ordered items
$query = "
    SELECT 
        oi.Order_item_id,
        oi.Ord_id,
        oi.Food_id,
        oi.FoodName,
        oi.Unit_Price,
        oi.Quantity,
        oi.Dis_Amount,
        oi.Tax_percent,
        oi.Final_item_price
    FROM Ordered_Items oi
    ORDER BY oi.Order_item_id DESC
";

$result = $conn->query($query);
?>

<div class="container">
    <h2>Ordered Items</h2>

    <table class="ordered-items-table">
        <thead>
            <tr>
                <th>Item ID</th>
                <th>Order ID</th>
                <th>Food ID</th>
                <th>Food Name</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Discount</th>
                <th>Tax %</th>
                <th>Final Price</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($item = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['Order_item_id']; ?></td>
                    <td><?php echo $item['Ord_id']; ?></td>
                    <td><?php echo $item['Food_id']; ?></td>
                    <td><?php echo htmlspecialchars($item['FoodName']); ?></td>
                    <td><?php echo number_format((float)$item['Unit_Price'], 2); ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td><?php echo number_format((float)$item['Dis_Amount'], 2); ?></td>
                    <td><?php echo number_format((float)$item['Tax_percent'], 2); ?>%</td>
                    <td><?php echo number_format((float)$item['Final_item_price'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:20px; color:#888;">
                        No ordered items found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.ordered-items-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.ordered-items-table th,
.ordered-items-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.ordered-items-table th {
    background-color: #2c3e50;
    color: white;
    font-weight: 600;
}

.ordered-items-table tr:hover {
    background-color: #f5f5f5;
}
</style>

<?php
$conn->close();
include('partials/footer.php');
?>