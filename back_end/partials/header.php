
<?php
$pageTitle = isset($pageTitle) ? $pageTitle : "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wow Food - <?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        min-height: 100vh;
        font-size: 16px;
        display: flex;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 250px;
        background: #2c3e50;
        color: white;
        height: 100vh;
        position: fixed;
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header img {
        height: 50px;
        display: block;
        margin: 0 auto;
    }

    .sidebar-menu {
        padding: 20px 0;
    }

    .sidebar-menu ul {
        list-style: none;
    }

    .sidebar-menu li {
        margin-bottom: 5px;
    }

    .sidebar-menu a {
        color: #ecf0f1;
        text-decoration: none;
        display: block;
        padding: 12px 20px;
        transition: all 0.3s;
    }

    .sidebar-menu a:hover, 
    .sidebar-menu a.active {
        background: #34495e;
    }

    .sidebar-menu i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        margin-left: 250px;
        padding: 30px;
    }

    .header {
        margin-bottom: 30px;
    }

    .page-title h1 {
        font-size: 28px;
        color: #2c3e50;
    }

    /* Dashboard Cards */
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
    }

    .card {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .card-title {
        font-size: 16px;
        color: #7f8c8d;
    }

    .card-value {
        font-size: 32px;
        font-weight: bold;
        color: #2c3e50;
    }

    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }

    .bg-primary { background: #3498db; }
    .bg-success { background: #2ecc71; }
    .bg-warning { background: #f39c12; }
    .bg-danger { background: #e74c3c; }
    .bg-info { background: #1abc9c; }
    .bg-purple { background: #9b59b6; }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }
        .sidebar-header span, 
        .sidebar-menu a span {
            display: none;
        }
        .sidebar-menu i {
            margin-right: 0;
            font-size: 20px;
        }
        .main-content {
            margin-left: 70px;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Wow Food Logo">
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> <span>Admin</span></a></li>
                <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> <span>Categories</span></a></li>
                <li><a href="foods.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'foods.php' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> <span>Foods</span></a></li>
                <li><a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Customers</span></a></li>
                <li><a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> <span>Transactions</span></a></li>
                <li><a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
                <li><a href="ordered_items.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ordered_items.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> <span>Ordered Items</span></a></li>
                <li><a href="delivery_person.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'delivery_person.php' ? 'active' : ''; ?>"><i class="fas fa-motorcycle"></i> <span>Delivery Person</span></a></li>
                <li><a href="delivery.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'delivery.php' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> <span>Delivery</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="page-title">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>
        </div>