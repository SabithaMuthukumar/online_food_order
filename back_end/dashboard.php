
<?php

include("auth.php");
include("database_connectivity.php");
$pageTitle = "Dashboard";
include('partials/header.php');
?>

<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Total Orders</div>
            <div class="card-icon bg-primary">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="card-value">1,245</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Total Revenue</div>
            <div class="card-icon bg-success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="card-value">$24,580</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Pending Orders</div>
            <div class="card-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="card-value">28</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Completed Orders</div>
            <div class="card-icon bg-info">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="card-value">1,150</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">New Customers</div>
            <div class="card-icon bg-info">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>
        <div class="card-value">142</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Food Items</div>
            <div class="card-icon bg-danger">
                <i class="fas fa-utensils"></i>
            </div>
        </div>
        <div class="card-value">78</div>
    </div>
</div>

<?php include('partials/footer.php'); ?>