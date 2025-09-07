<?php
include("auth.php");
include("database_connectivity.php");
$pageTitle = "Dashboard";
include('partials/header.php');

// Fetch data from database
// Total Orders
$totalOrdersQuery = "SELECT COUNT(Ord_id) as total_orders FROM orders";
$totalOrdersResult = mysqli_query($conn, $totalOrdersQuery);
$totalOrders = mysqli_fetch_assoc($totalOrdersResult)['total_orders'];

// Total Revenue (only paid orders)
$totalRevenueQuery = "SELECT SUM(t.Amount) as total_revenue 
                      FROM trans t 
                      WHERE t.Pay_Status = 'Completed'";
$totalRevenueResult = mysqli_query($conn, $totalRevenueQuery);
$totalRevenue = mysqli_fetch_assoc($totalRevenueResult)['total_revenue'];
if (!$totalRevenue) $totalRevenue = 0;

// Pending Orders
$pendingOrdersQuery = "SELECT COUNT(Ord_id) as pending_orders 
                       FROM orders 
                       WHERE Status = 'pending'";
$pendingOrdersResult = mysqli_query($conn, $pendingOrdersQuery);
$pendingOrders = mysqli_fetch_assoc($pendingOrdersResult)['pending_orders'];

// Completed Orders
$completedOrdersQuery = "SELECT COUNT(Ord_id) as completed_orders 
                         FROM orders 
                         WHERE Status = 'delivered'";
$completedOrdersResult = mysqli_query($conn, $completedOrdersQuery);
$completedOrders = mysqli_fetch_assoc($completedOrdersResult)['completed_orders'];

// New Customers (registered today)
$today = date('Y-m-d');
$newCustomersQuery = "SELECT COUNT(Cust_ID) as new_customers 
                      FROM customer_det 
                      WHERE DATE(Registered_date) = '$today'";
$newCustomersResult = mysqli_query($conn, $newCustomersQuery);
$newCustomers = mysqli_fetch_assoc($newCustomersResult)['new_customers'];

// Food Items
$foodItemsQuery = "SELECT COUNT(Food_id) as food_items FROM foods";
$foodItemsResult = mysqli_query($conn, $foodItemsQuery);
$foodItems = mysqli_fetch_assoc($foodItemsResult)['food_items'];
?>
<style>
    .dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 20px;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #6c757d;
}

.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.card-value {
    font-size: 24px;
    font-weight: 700;
    color: #343a40;
}

.bg-primary { background-color: #007bff; }
.bg-success { background-color: #28a745; }
.bg-warning { background-color: #ffc107; }
.bg-info { background-color: #17a2b8; }
.bg-danger { background-color: #dc3545; }
</style>

<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Total Orders</div>
            <div class="card-icon bg-primary">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($totalOrders); ?></div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Total Revenue</div>
            <div class="card-icon bg-success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="card-value">$<?php echo number_format($totalRevenue, 2); ?></div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Pending Orders</div>
            <div class="card-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($pendingOrders); ?></div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Completed Orders</div>
            <div class="card-icon bg-info">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($completedOrders); ?></div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">New Customers</div>
            <div class="card-icon bg-info">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($newCustomers); ?></div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Food Items</div>
            <div class="card-icon bg-danger">
                <i class="fas fa-utensils"></i>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($foodItems); ?></div>
    </div>
</div>

<?php include('partials/footer.php'); ?>