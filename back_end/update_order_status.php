<?php
include("auth.php");
include("database_connectivity.php");

if (isset($_POST['save'])) {
    $orderId = $_POST['save'];
    $newStatus = $_POST['status'][$orderId];

    $stmt = $conn->prepare("UPDATE Orders SET Status = ? WHERE Ord_id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);

    if ($stmt->execute()) {
        header("Location: orders.php?updated=1");
        exit;
    } else {
        echo "Error updating status.";
    }

    $stmt->close();
}

$conn->close();
?>