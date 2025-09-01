<?php
include("auth.php");
include("database_connectivity.php");

if (isset($_POST['save'])) {
    $transId = $_POST['save'];
    $newMode = $_POST['pay_mode'][$transId];
    $newStatus = $_POST['pay_status'][$transId];

    $stmt = $conn->prepare("UPDATE Trans SET Pay_Mode = ?, Pay_Status = ? WHERE Trans_id = ?");
    $stmt->bind_param("sss", $newMode, $newStatus, $transId);

    if ($stmt->execute()) {
        header("Location: transactions.php?updated=1");
        exit;
    } else {
        echo "Error updating transaction.";
    }

    $stmt->close();
}

$conn->close();
?>