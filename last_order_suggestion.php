<?php
session_start();
include("back_end/database_connectivity.php");

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Check if we should show the suggestion (only show once after login)
$showSuggestion = $_SESSION['show_last_order_suggestion'] ?? false;
if (!$showSuggestion) {
    // If not showing suggestion, go directly to transaction page
    header("Location: transaction.php");
    exit();
}

// Initialize variables
$lastOrderItems = [];
$hasLastOrder = false;

// Check if user has a last order
if (isset($_SESSION['last_order_id'])) {
    $orderId = $_SESSION['last_order_id'];
    
    // Get last order items
    $getItems = $conn->prepare("
        SELECT oi.Food_id, oi.FoodName, oi.Quantity, oi.Unit_Price, oi.Final_item_price 
        FROM ordered_items oi 
        WHERE oi.Ord_id = ?
    ");
    $getItems->bind_param("i", $orderId);
    $getItems->execute();
    $itemsResult = $getItems->get_result();
    
    if ($itemsResult->num_rows > 0) {
        $hasLastOrder = true;
        while ($item = $itemsResult->fetch_assoc()) {
            $lastOrderItems[] = $item;
        }
    }
    $getItems->close();
}

// Handle user response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove the flag to prevent showing this page again
    unset($_SESSION['show_last_order_suggestion']);
    
    if (isset($_POST['add_last_order']) && $hasLastOrder) {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Add last order items to cart
        foreach ($lastOrderItems as $item) {
            $foodId = $item['Food_id'];
            $quantity = $item['Quantity'];
            
            // Check if item already in cart
            $itemIndex = -1;
            foreach ($_SESSION['cart'] as $index => $cartItem) {
                if ($cartItem['food_id'] == $foodId) {
                    $itemIndex = $index;
                    break;
                }
            }
            
            if ($itemIndex >= 0) {
                // Update quantity
                $_SESSION['cart'][$itemIndex]['quantity'] += $quantity;
            } else {
                // Add new item
                $_SESSION['cart'][] = array(
                    'food_id' => $foodId,
                    'quantity' => $quantity
                );
            }
        }
        
        // Redirect to foods page
        header("Location: foods.php");
        exit();
    } else {
        // Redirect to transaction page
        header("Location: transaction.php");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Last Order Suggestion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 500px; width: 90%; }
        .suggestion-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 { color: #ff6b6b; margin-bottom: 20px; }
        .order-items {
            margin: 20px 0;
            text-align: left;
        }
        .order-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-yes {
            background: #4CAF50;
            color: white;
        }
        .btn-yes:hover {
            background: #45a049;
        }
        .btn-no {
            background: #f44336;
            color: white;
        }
        .btn-no:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="suggestion-box">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['customer_name']); ?>!</h1>
            
            <?php if ($hasLastOrder): ?>
                <p>Would you like to add your last ordered items to your cart?</p>
                
                <div class="order-items">
                    <h3>Last Order Items:</h3>
                    <?php foreach ($lastOrderItems as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['FoodName']); ?></span>
                            <span>Qty: <?php echo $item['Quantity']; ?></span>
                            <span>₹<?php echo number_format($item['Final_item_price'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" class="buttons">
                    <button type="submit" name="add_last_order" class="btn btn-yes">Yes, Add to Cart</button>
                    <button type="submit" name="skip_last_order" class="btn btn-no">No, Thanks</button>
                </form>
            <?php else: ?>
                <p>Welcome back! You don't have any previous orders.</p>
                <form method="POST">
                    <button type="submit" name="skip_last_order" class="btn btn-yes" style="display: block; width: 100%;">Continue to Food Menu</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>