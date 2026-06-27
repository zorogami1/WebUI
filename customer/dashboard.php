<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Valued Customer';

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "createprojectdb";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Product Catalog Mapping
$products_catalog = [
        1 => ["name" => "Oak Dining Chair", "price" => 450],
        2 => ["name" => "Large Dining Table", "price" => 2500],
        3 => ["name" => "3-Seater Fabric Sofa", "price" => 3800],
        4 => ["name" => "Wooden Wardrobe", "price" => 1800],
        5 => ["name" => "Industrial Bookshelf", "price" => 1200],
        6 => ["name" => "Queen Size Bed Frame", "price" => 2200]
];

$total_orders = 0;
$total_spent = 0;
$pending_count = 0;
$recent_orders = [];

if (!$conn->connect_error) {
    // Fetch all orders for this user - using correct column names
    $metrics_query = "SELECT oid, status FROM orders WHERE cid = ?";
    $stmt = $conn->prepare($metrics_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $total_orders++;

        if ($row['status'] === 'Pending' || $row['status'] === 'Open') {
            $pending_count++;
        }
    }
    $stmt->close();

    // Fetch Recent Orders with product details
    $table_query = "SELECT o.oid, o.status, of.fid, of.oqty, f.fname, f.fprice 
                    FROM orders o
                    JOIN orderfurnitures of ON o.oid = of.oid
                    JOIN furnitures f ON of.fid = f.fid
                    WHERE o.cid = ? 
                    ORDER BY o.oid DESC 
                    LIMIT 5";
    $stmt = $conn->prepare($table_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calculate total spent
    $spent_query = "SELECT SUM(of.oqty * f.fprice) as total_spent 
                    FROM orders o
                    JOIN orderfurnitures of ON o.oid = of.oid
                    JOIN furnitures f ON of.fid = f.fid
                    WHERE o.cid = ?";
    $stmt = $conn->prepare($spent_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $spent_result = $stmt->get_result()->fetch_assoc();
    $total_spent = $spent_result['total_spent'] ?? 0;
    $stmt->close();

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="dashboard.php">
            <i class="fas fa-tree"></i>
            <h1>Premium Living</h1>
        </a>
    </div>
    <ul class="nav-links">
        <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="make-order.php"><i class="fas fa-shopping-cart"></i> Make Order</a></li>
        <li><a href="view-orders.php"><i class="fas fa-list"></i> Orders</a></li>
        <li><a href="update-profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-leaf"></i> Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($total_spent); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Recent Orders</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #a89f91; padding: 3rem;">
                            <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No recent orders found. Get started on making an item order!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order):
                        $total = $order['oqty'] * $order['fprice'];
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['oid']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['fname']); ?></td>
                            <td><?php echo $order['oqty']; ?></td>
                            <td><span style="color: var(--wood-dark); font-weight: 600;">$<?php echo number_format($total, 2); ?></span></td>
                            <td>
                                <span class="badge-success <?php echo ($order['status'] === 'Pending' || $order['status'] === 'Open') ? 'badge-warning' : 'badge-completed'; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view-order-details.php?oid=<?php echo $order['oid']; ?>" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem; text-decoration: none;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>

</body>
</html>