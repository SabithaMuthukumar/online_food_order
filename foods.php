<?php 
include("back_end/database_connectivity.php");

// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// IMAGE MAPPING FUNCTION
function getFoodImagePath($foodName, $imageFile) {
    $categoryMap = [
        'burger' => 'cat-burgers',
        'pizza' => 'cat-pizzas', 
        'momo' => 'cat-momos',
        'soup' => 'cat-soups',
        'salad' => 'cat-salads',
        'icecream' => 'cat-icecreams',
        // Add more mappings as needed
    ];
    
    // Default category
    $category = 'cat-other';
    
    // Convert food name to lowercase for case-insensitive matching
    $lowercaseFoodName = strtolower($foodName);
    
    // Find the appropriate category
    foreach ($categoryMap as $key => $folder) {
        if (strpos($lowercaseFoodName, $key) !== false) {
            $category = $folder;
            break;
        }
    }
    
    return "images/$category/$imageFile";
}

// Fetch food items from database
$sql = "SELECT * FROM Foods WHERE Active = 'Yes'";
$result = $conn->query($sql);

$foodItems = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Apply image mapping
        $row['Image'] = getFoodImagePath($row['Food_name'], $row['Image']);
        $foodItems[$row['Food_id']] = $row;
    }
}

// If form is submitted for order processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['place_order'])) {
        // Process order
        // Generate a unique order number
        $orderNumber = time() . rand(100, 999);
        
        // Calculate totals and prepare order details
        $subtotal = 0;
        $totalTax = 0;
        $deliveryCharge = 0;
        $orderItems = array();
        
        foreach ($_SESSION['cart'] as $item) {
            $foodId = $item['food_id'];
            $quantity = $item['quantity'];
            $price = $foodItems[$foodId]['Price'];
            $discountPercent = $foodItems[$foodId]['Discount_percent'];
            $taxPercent = $foodItems[$foodId]['Tax_percent'];
            
            // Calculate individual item values - TAX IS PER UNIT, NOT MULTIPLIED BY QUANTITY
            $unitPrice = $price;
            $discountAmountPerUnit = $price * $discountPercent / 100;
            $taxAmountPerUnit = $price * $taxPercent / 100; // Tax per unit (not multiplied by quantity)
            $finalPricePerUnit = $unitPrice - $discountAmountPerUnit + $taxAmountPerUnit;
            
            // Calculate totals (multiply by quantity for everything except tax amount)
            $itemSubtotal = $finalPricePerUnit * $quantity;
            $totalItemTax = $taxAmountPerUnit; // TAX IS PER UNIT, NOT MULTIPLIED BY QUANTITY
            $totalItemDiscount = $discountAmountPerUnit * $quantity;
            $orderItemId = $foodId . '_' . time() . '_' . rand(1000, 9999);
            
            // Store order item details with all required information
            $orderItems[] = array(
                'order_item_id' => $orderItemId, // ADD THIS LINE
                'food_id' => $foodId,
                'food_name' => $foodItems[$foodId]['Food_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $totalItemDiscount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $totalItemTax, // This is the tax PER UNIT
                'final_unit_price' => $finalPricePerUnit,
                'item_total' => $itemSubtotal
            );
            
            // Update order totals
            $subtotal += $itemSubtotal;
            $totalTax += $totalItemTax; // Add the unit tax (not multiplied by quantity)
        }
        
        // Calculate delivery charge based on subtotal
        if ($subtotal > 500) {
            $deliveryCharge = 0;
        } else if ($subtotal > 300) {
            $deliveryCharge = 30;
        } else if ($subtotal > 100) {
            $deliveryCharge = 50;
        } else {
            $deliveryCharge = 70;
        }
        
        $grandTotal = $subtotal + $totalTax + $deliveryCharge;
        
        // Store order details in session for later use with all required data
        $_SESSION['order_details'] = array(
            'order_number' => $orderNumber,
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'delivery_charge' => $deliveryCharge,
            'grand_total' => $grandTotal,
            'order_items' => $orderItems
        );
        
        // Redirect to register page
        header("Location: register.php");
        exit();
    }
}

// Handle AJAX requests for cart operations
if (isset($_POST['action'])) {
    $response = array('success' => false);
    
    if ($_POST['action'] == 'add_to_cart') {
        $foodId = $_POST['food_id'];
        $quantity = $_POST['quantity'];
        
        // Check if item already in cart
        $itemIndex = -1;
        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['food_id'] == $foodId) {
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
        
        $response['success'] = true;
        $response['cart_count'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    
    if ($_POST['action'] == 'get_cart_count') {
        $response['success'] = true;
        $response['cart_count'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    
    if ($_POST['action'] == 'get_cart_items') {
        $response['success'] = true;
        $response['cart_items'] = $_SESSION['cart'];
    }
    
   if ($_POST['action'] == 'update_quantity') {
    $foodId = $_POST['food_id'];
    $quantity = $_POST['quantity'];

    $found = false;

    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['food_id'] == $foodId) {
            $found = true;
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$index]);
            } else {
                $_SESSION['cart'][$index]['quantity'] = $quantity;
            }
            break;
        }
    }

    if (!$found && $quantity > 0) {
        $_SESSION['cart'][] = array(
            'food_id' => $foodId,
            'quantity' => $quantity
        );
    }

    $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex
    $response['success'] = true;
    $response['cart_count'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
}
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Calculate initial cart count for display
$initialCartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Order System</title>
    <style>
        /* CSS for All */
        * {
            margin: 0 0;
            padding: 0 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 1%;
        }

        .img-responsive {
            height: 300px;
            width: 300px;
        }

        .img-curve {
            border-radius: 15px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-white {
            color: white;
        }

        .clearfix {
            clear: both;
            float: none;
        }

        a {
            color: #ff6b81;
            text-decoration: none;
        }

        a:hover {
            color: #ff4757;
        }

        .btn {
            padding: 1%;
            border: none;
            font-size: 1rem;
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #ff6b81;
            color: white;
            cursor: pointer;
        }

        .btn-primary:hover {
            color: white;
            background-color: #ff4757;
        }

        h2 {
            color: #2f3542;
            font-size: 2rem;
            margin-bottom: 2%;
        }

        h3 {
            font-size: 1.5rem;
        }

        .float-container {
            position: relative;
        }

        .float-text {
            position: absolute;
            bottom: 50px;
            left: 40%;
        }

        fieldset {
            border: 1px solid white;
            margin: 5%;
            padding: 3%;
            border-radius: 5px;
        }

        /* Navbar Styles */
        .navbar {
            background-image: url(images/backgrounds/bg.jpg);
            padding: 23px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .header-footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            float: left;
        }

        .logo img {
            height: 50px;
        }

        .menu {
            float: right;
        }

        .menu ul {
            list-style: none;
            display: flex;
            align-items: center;
        }

        .menu li {
            margin-left: 30px;
        }

        .menu a {
            color: #444;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            position: relative;
            font-size: 1.1rem;
        }

        .menu a:hover {
            color: #e74c3c;
        }

        .menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #e74c3c;
            transition: width 0.3s ease;
        }

        .menu a:hover::after {
            width: 100%;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 0;
        }

        /* Food Menu Section */
        .food-menu {
            background-color: #ececec;
            padding: 4% 0;
        }

        .food-menu-box {
            width: 43%;
            margin: 1%;
            padding: 2%;
            float: left;
            background-color: white;
            border-radius: 15px;
        }

        .food-menu-img {
            width: 20%;
            float: left;
        }

        .food-menu-desc {
            width: 70%;
            float: left;
            margin-left: 43px;
        }

        .food-price {
            font-size: 1.2rem;
            margin: 2% 0;
        }

        .food-detail {
            font-size: 1rem;
            color: #747d8c;
            width: 100%
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 20px;
        }

        .discounted-price {
            color: #ff6b6b;
            font-size: 20px;
        }

        .discount-badge {
            background: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .food-detail {
            color: #666;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .quantity-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #ff5252;
            transform: scale(1.1);
        }

        .quantity-display {
            margin: 0 15px;
            font-size: 1.2rem;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        /* CSS for food_imgs */
        .img {
            height: 100px;
            width: 105px;
            border-radius: 25px;
        }

        /* CSS for cart and order */
        .btn {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff5252, #ff7575);
            transform: translateY(-2px);
        }

        /* Cart Button */
        .cart-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
            display: none;
        }

        .cart-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }

        .cart-count {
            background: white;
            color: #ff6b6b;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-left: 10px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ff6b6b;
        }

        .order-summary {
            margin-top: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-totals {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total-row.final {
            border-top: 2px solid #ff6b6b;
            padding-top: 10px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Social Section */
        .social {
            background: #fff;
            padding: 40px 0;
            text-align: center;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .social ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        .social a svg {
            width: 45px;
            height: 45px;
            transition: transform 0.3s ease;
        }

        .social a svg path {
            stroke: #555;
            transition: stroke 0.3s ease;
        }

        .social a:hover svg {
            transform: scale(1.2);
        }

        .social a:hover svg path {
            stroke: #e74c3c;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            padding: 30px 0;
            text-align: center;
        }

        .footer p {
            margin: 0;
            color: #ecf0f1;
            font-size: 1.1rem;
        }

        .footer a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .menu {
                float: none;
                clear: both;
                margin-top: 15px;
            }

            .menu ul {
                flex-wrap: wrap;
                justify-content: center;
            }

            .menu li {
                margin: 8px 20px;
            }

            .navbar {
                position: relative;
                padding: 20px 0;
            }

            .main-content {
                margin-top: 0;
            }

            .food-menu-box {
                width: 90%;
                padding: 5%;
                margin-bottom: 5%;
            }

            .food-menu-img {
                width: 100%;
                float: none;
                margin-bottom: 15px;
            }

            .food-menu-desc {
                width: 100%;
                float: none;
                margin-left: 0;
            }
        }

        @media only screen and (max-width:768px) {
            .logo {
                width: 80%;
                float: none;
                margin: 1% auto;
            }

            .menu ul {
                text-align: center;
            }

            .food-search input[type="search"] {
                width: 90%;
                padding: 2%;
                margin-bottom: 3%;
            }

            .btn {
                width: 91%;
                padding: 2%;
            }

            .food-search {
                padding: 10% 0;
            }

            .categories {
                padding: 20% 0;
            }

            h2 {
                margin-bottom: 10%;
            }

            .box-3 {
                width: 100%;
                margin: 4% auto;
            }

            .food-menu {
                padding: 20% 0;
            }

            .order {
                width: 100%;
            }
        }
        
        /* Additional styles for the food items grid */
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .food-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .food-card:hover {
            transform: translateY(-5px);
        }
        
        .food-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .food-info {
            padding: 15px 0;
        }
        
        .proceed-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .proceed-btn:hover {
            background: #45a049;
        }
        
        .no-discount-price {
            color: #ff6b6b;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar Section -->
    <section class="navbar">
        <div class="header-footer-container">
            <div class="logo">
                <a href="#" title="Logo">
                    <img src="images/logo.png" alt="Restaurant Logo" class="bg-img">
                </a>
            </div>
            <div class="menu">
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="foods.php">Foods</a></li>
                    <li><a href="about-us.html">About Us</a></li>
                </ul>
            </div>
            <div class="clearfix"></div>
        </div>
    </section>

    <!-- Food Menu Section -->
    <section class="food-menu">
        <div class="container">
            <h2 class="text-center">Food Menu</h2>
            
            <!-- Food items grid -->
            <div class="food-grid">
                <?php foreach ($foodItems as $food): ?>
                <div class="food-card">
                    <img src="<?php echo $food['Image']; ?>" alt="<?php echo $food['Food_name']; ?>" class="food-image">
                    <div class="food-info">
                        <h3><?php echo $food['Food_name']; ?></h3>
                        <p class="food-detail"><?php echo $food['Description']; ?></p>
                        <p class="food-price">
                            <?php if ($food['Discount_percent'] > 0): ?>
                            <span class="original-price">&#8377;<?php echo number_format($food['Price'], 2); ?></span>
                            <span class="discounted-price">&#8377;<?php 
                                $discountedPrice = $food['Price'] * (1 - $food['Discount_percent']/100);
                                echo number_format($discountedPrice, 2);
                            ?></span>
                            <span class="discount-badge"><?php echo $food['Discount_percent']; ?>% OFF</span>
                            <?php else: ?>
                            <span class="no-discount-price">&#8377;<?php echo number_format($food['Price'], 2); ?></span>
                            <?php endif; ?>
                        </p>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="changeQuantity(<?php echo $food['Food_id']; ?>, -1)">-</button>
                            <span class="quantity-display" id="qty-<?php echo $food['Food_id']; ?>">0</span>
                            <button class="quantity-btn" onclick="changeQuantity(<?php echo $food['Food_id']; ?>, 1)">+</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="clearfix"></div>
        </div>
    </section>

    <!-- Place Order Button -->
    <button id="placeOrderBtn" class="cart-button" style="display: none;">
        Place Order
        <span class="cart-count" id="cartCount"><?php echo $initialCartCount; ?></span>
    </button>

    <!-- Order Summary Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2 class="text-center">Order Summary</h2>
            
            <div id="orderItems" class="order-summary">
                <!-- Order items will be added here dynamically -->
                <p class="text-center">Your cart is empty</p>
            </div>
            
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">&#8377;0.00</span>
                </div>
                
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span id="deliveryFee">&#8377;0.00</span>
                </div>
                
                <div class="total-row">
                    <span>Tax:</span>
                    <span id="tax">&#8377;0.00</span>
                </div>
                
                <div class="total-row final">
                    <span>Total:</span>
                    <span id="total">&#8377;0.00</span>
                </div>
            </div>
            
            <form method="POST" action="">
                <button type="submit" name="place_order" class="proceed-btn">Proceed to Checkout</button>
            </form>
        </div>
    </div>

    <!-- Social Media Section -->
    <section class="social">
        <div class="container text-center">
            <ul>
                <li><a href="#"><img src="https://img.icons8.com/fluent/50/000000/facebook-new.png" alt="Facebook"></a></li>
                <li><a href="#"><img src="https://img.icons8.com/fluent/48/000000/instagram-new.png" alt="Instagram"></a></li>
                <li><a href="#"><img src="https://img.icons8.com/fluent/48/000000/twitter.png" alt="Twitter"></a></li>
            </ul>
        </div>
    </section>

    <!-- Footer Section -->
    <section class="footer">
        <div class="container text-center">
            <p>All rights reserved. Designed By <a href="#">YourName</a></p>
        </div>
    </section>
<script>
    // Food items data from PHP
    const foodItems = <?php echo json_encode($foodItems); ?>;

    // Function to update cart via AJAX (returns a Promise)
    function updateCart(itemId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('food_id', itemId);
        formData.append('quantity', quantity);

        return fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCount = data.cart_count;
                document.getElementById('cartCount').textContent = cartCount;
                // Show/hide button based on cart count
                document.getElementById('placeOrderBtn').style.display = cartCount > 0 ? 'block' : 'none';
            }
        });
    }

    // Function to change quantity of an item
    function changeQuantity(itemId, change) {
        const quantityElement = document.getElementById(`qty-${itemId}`);
        let currentQuantity = parseInt(quantityElement.textContent);
        let newQuantity = currentQuantity + change;

        if (newQuantity >= 0) {
            quantityElement.textContent = newQuantity;

            // Wait for cart update before refreshing summary
            updateCart(itemId, newQuantity).then(() => {
                updateOrderSummary();
            });
        }
    }

    // Function to update the order summary
    function updateOrderSummary() {
        const orderItemsContainer = document.getElementById('orderItems');

        const formData = new FormData();
        formData.append('action', 'get_cart_items');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            let subtotal = 0;
            let totalTax = 0;

            orderItemsContainer.innerHTML = '';

            if (!data.success || !data.cart_items || data.cart_items.length === 0) {
                orderItemsContainer.innerHTML = '<p class="text-center">Your cart is empty</p>';
                document.getElementById('placeOrderBtn').style.display = 'none'; // Hide button
            } else {
                data.cart_items.forEach(item => {
                    const foodItem = foodItems[item.food_id];
                    const quantity = parseInt(item.quantity, 10);
                    const price = parseFloat(foodItem.Price);
                    const discountPercent = parseFloat(foodItem.Discount_percent);
                    const taxPercent = parseFloat(foodItem.Tax_percent);

                    // Calculate values - TAX IS PER UNIT, NOT MULTIPLIED BY QUANTITY
                    const discountAmountPerUnit = price * (discountPercent / 100);
                    const discountedPrice = price - discountAmountPerUnit;
                    const taxAmountPerUnit = price * (taxPercent / 100); // Tax per unit (not multiplied by quantity)
                    const finalPricePerUnit = discountedPrice + taxAmountPerUnit;
                    const itemSubtotal = finalPricePerUnit * quantity;
                    const itemTax = taxAmountPerUnit; // TAX IS PER UNIT, NOT MULTIPLIED BY QUANTITY

                    subtotal += itemSubtotal;
                    totalTax += itemTax; // Add the unit tax (not multiplied by quantity)

                    const orderItem = document.createElement('div');
                    orderItem.className = 'order-item';
                    orderItem.innerHTML = `
                        <span class="item-name">${foodItem.Food_name} x${quantity}</span>
                        <span class="item-price">&#8377;${itemSubtotal.toFixed(2)}</span>
                    `;
                    orderItemsContainer.appendChild(orderItem);
                });

                document.getElementById('placeOrderBtn').style.display = 'block'; // Show button
            }

            let deliveryCharge = 0;
            if (subtotal > 500) deliveryCharge = 0;
            else if (subtotal > 300) deliveryCharge = 30;
            else if (subtotal > 100) deliveryCharge = 50;
            else deliveryCharge = 70;

            const total = subtotal + totalTax + deliveryCharge;

            document.getElementById('subtotal').innerHTML = `&#8377;${subtotal.toFixed(2)}`;
            document.getElementById('deliveryFee').innerHTML = `&#8377;${deliveryCharge.toFixed(2)}`;
            document.getElementById('tax').innerHTML = `&#8377;${totalTax.toFixed(2)}`;
            document.getElementById('total').innerHTML = `&#8377;${total.toFixed(2)}`;
        });
    }

    // Function to initialize quantities on page load
    function initializeQuantities() {
        // Get current cart items from session
        const formData = new FormData();
        formData.append('action', 'get_cart_items');
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cart_items) {
                // Update cart count
                const cartCount = array_sum(data.cart_items.map(item => parseInt(item.quantity)));
                document.getElementById('cartCount').textContent = cartCount;
                document.getElementById('placeOrderBtn').style.display = cartCount > 0 ? 'block' : 'none';
                
                // Update quantity displays for each item
                data.cart_items.forEach(item => {
                    const quantityElement = document.getElementById(`qty-${item.food_id}`);
                    if (quantityElement) {
                        quantityElement.textContent = item.quantity;
                    }
                });
            }
        });
    }

    // Helper function to calculate sum of quantities
    function array_sum(array) {
        return array.reduce((sum, value) => sum + value, 0);
    }

    // Modal and button setup
    document.addEventListener('DOMContentLoaded', function () {
        initializeQuantities();
        
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        const orderModal = document.getElementById('orderModal');
        const closeModal = document.getElementById('closeModal');

        placeOrderBtn.addEventListener('click', function () {
            updateOrderSummary();
            orderModal.style.display = 'block';
        });

        closeModal.addEventListener('click', function () {
            orderModal.style.display = 'none';
        });

        window.addEventListener('click', function (event) {
            if (event.target === orderModal) {
                orderModal.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>