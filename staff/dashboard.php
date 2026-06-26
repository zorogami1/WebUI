<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.html"><i class="fas fa-tree"></i> Staff Portal</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.html">Dashboard</a></li><li><a href="insert-furniture.html">Add Furniture</a></li>
        <li><a href="insert-material.html">Add Material</a></li><li><a href="manage-orders.html">Manage Orders</a></li>
        <li><a href="generate-report.html">Reports</a></li><li><a href="delete-furniture.html">Delete Item</a></li>
        <li><a href="../index.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card"><div class="card-header"><h2><i class="fas fa-chart-simple"></i> Workshop Overview</h2></div>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number">6</div><div class="stat-label">Furniture Pieces</div></div>
            <div class="stat-card"><div class="stat-number">4</div><div class="stat-label">Raw Materials</div></div>
            <div class="stat-card"><div class="stat-number">15</div><div class="stat-label">Pending Orders</div></div>
            <div class="stat-card"><div class="stat-number">$42.5k</div><div class="stat-label">Monthly Revenue</div></div>
        </div></div>

    <div class="card"><div class="card-header"><h2><i class="fas fa-tasks"></i> Quick Actions</h2></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="insert-furniture.html" class="btn btn-primary"><i class="fas fa-plus"></i> New Furniture</a>
            <a href="insert-material.html" class="btn btn-success"><i class="fas fa-cube"></i> Add Material</a>
            <a href="manage-orders.html" class="btn btn-warning"><i class="fas fa-clipboard-list"></i> Manage Orders</a>
            <a href="generate-report.html" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Reports</a>
        </div></div>
</div>
<footer><p>&copy; 2024 Premium Living | <i class="fas fa-tree"></i> Artisan Management</p></footer>
</body>
</html>