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

// Product Catalog Mapping & Price Sheet Matrix
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
    // 1. Fetch all items matching this user to calculate accurate metric values dynamically
    // Fixed: Using order_id instead of id
    $metrics_query = "SELECT order_id, product_id, status FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($metrics_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $total_orders++;

        if ($row['status'] === 'Pending') {
            $pending_count++;
        }

        // Sum prices using our catalog map matrix
        $p_id = intval($row['product_id']);
        if (isset($products_catalog[$p_id])) {
            $total_spent += $products_catalog[$p_id]['price'];
        }
    }
    $stmt->close();

    // 2. Fetch Recent Orders for the view table layout preview
    // Fixed: Ordering and selecting by order_id
    $table_query = "SELECT order_id, product_id, status FROM orders WHERE user_id = ? ORDER BY order_id DESC LIMIT 5";
    $stmt = $conn->prepare($table_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <style>
        /* Forces standard centralized grid alignment from your requested specs */
        table th, table td {
            text-align: center !important;
            vertical-align: middle;
            padding: 1.2rem 1.5rem;
        }
        .badge-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
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
                    <th style="width: 15%;">Order ID</th>
                    <th style="width: 25%;">Product Name</th>
                    <th style="width: 20%;">Total</th>
                    <th style="width: 20%;">Status</th>
                    <th style="width: 20%;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--gray-wood); padding: 3rem;">
                            <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No recent orders found. Get started on making an item order!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order):
                        $p_id = intval($order['product_id']);
                        $product_name = isset($products_catalog[$p_id]) ? $products_catalog[$p_id]['name'] : "Handcrafted Furniture Pieces";
                        $product_cost = isset($products_catalog[$p_id]) ? "$" . number_format($products_catalog[$p_id]['price']) : "N/A";
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($product_name); ?></td>
                            <td><span style="color: var(--wood-dark); font-weight: 600;"><?php echo $product_cost; ?></span></td>
                            <td>
                                <div class="badge-container">
                                        <span class="badge-success <?php echo ($order['status'] === 'Pending') ? 'badge-warning' : 'badge-completed'; ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                </div>
                            </td>
                            <td>
                                <a href="view-orders.php" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem; text-decoration: none;">
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