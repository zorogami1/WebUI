<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['sid'])) {
    header("Location: login.php");
    exit();
}

require_once '../conn.php';

$total_furniture = 0;
$total_materials = 0;
$pending_orders = 0;

try {
    $total_furniture = $pdo->query("SELECT COUNT(*) FROM Furnitures")->fetchColumn();
    $total_materials = $pdo->query("SELECT COUNT(*) FROM Materials")->fetchColumn();

    $order_check = $pdo->query("SHOW TABLES LIKE 'Orders'")->fetchAll();
    if (!empty($order_check)) {
        $pending_orders = $pdo->query("SELECT COUNT(*) FROM Orders WHERE LOWER(status) = 'open' OR LOWER(status) = 'processing'")->fetchColumn();
    }
} catch (PDOException $e) {
    $db_warning = "Database synchronization tracking notice: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Premium Living Furniture</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .welcome-banner { background: linear-gradient(135deg, #8b5e3c, #6d442b); color: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .dash-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #8b5e3c; display: flex; align-items: center; justify-content: space-between; }
        .dash-card i { font-size: 2.5rem; color: #d4a373; }
        .dash-metric { font-size: 2rem; font-weight: bold; color: #333; }
        .dash-label { color: #777; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php">🏠 Staff Portal</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="insert-furniture.php">Insert Furniture</a></li>
        <li><a href="insert-material.php">Insert Material</a></li>
        <li><a href="manage-orders.php">Manage Orders</a></li>
        <li><a href="generate-report.php">Generate Report</a></li>
        <li><a href="delete-furniture.php">Delete Furniture</a></li>
        <li><a href="login.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="welcome-banner">
        <h2>Welcome Back, Operations Team!</h2>
        <p>Premium Living Internal Management System Platform Dashboard</p>
    </div>

    <?php if (isset($db_warning)): ?>
        <div style="background-color: #fff3cd; color: #856404; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; border: 1px solid #ffeeba;">
            ⚠️ <?php echo htmlspecialchars($db_warning); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="dash-card">
            <div>
                <div class="dash-metric"><?php echo $total_furniture; ?></div>
                <div class="dash-label">Catalog Products</div>
            </div>
            <i class="fas fa-chair"></i>
        </div>
        <div class="dash-card" style="border-left-color: #2a9d8f;">
            <div>
                <div class="dash-metric"><?php echo $total_materials; ?></div>
                <div class="dash-label">Material Types</div>
            </div>
            <i class="fas fa-boxes"></i>
        </div>
        <div class="dash-card" style="border-left-color: #e76f51;">
            <div>
                <div class="dash-metric"><?php echo $pending_orders; ?></div>
                <div class="dash-label">Active Orders</div>
            </div>
            <i class="fas fa-truck"></i>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Quick Actions Console</h2></div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
            <!-- Change it to this -->
            <a href="insert-furniture.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Furniture</a>
            <a href="insert-material.php" class="btn btn-secondary"><i class="fas fa-warehouse"></i> Stock Materials</a>
            <a href="manage-orders.php" class="btn btn-success" style="background-color: #2a9d8f;"><i class="fas fa-clipboard-list"></i> Fulfill Orders</a>
        </div>
    </div>
</div>
<footer><p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p></footer>
</body>
</html>