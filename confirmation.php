<?php
session_start();
include("back_end/database_connectivity.php");


// Check if confirmation details exist
if (!isset($_SESSION['confirmation'])) {
    header("Location: index.html");
    exit();
}

$confirmation = $_SESSION['confirmation'];
$orderNumber = $confirmation['order_number'];
$paymentMethod = $confirmation['payment_method'];
$deliveryPerson = $confirmation['delivery_person'];

// Clear confirmation from session
unset($_SESSION['confirmation']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation - Food Order System</title>
</head>
<body>
    <h2>Order Confirmed!</h2>
    <p>Thank you for your order. Your order number is: <?php echo $orderNumber; ?></p>
    <p>Payment Method: <?php echo $paymentMethod; ?></p>
    
    <?php if ($deliveryPerson): ?>
    <p>Your order will be delivered by: <?php echo $deliveryPerson; ?></p>
    <?php else: ?>
    <p>We're arranging delivery for your order. You'll receive a notification soon.</p>
    <?php endif; ?>
    
    <a href="index.php">Place Another Order</a>
</body>
</html>