<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "createprojectdb";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
$orders = [];

if (!$conn->connect_error) {
    $stmt = $conn->prepare("SELECT order_id, product_id, status FROM orders WHERE user_id = ? ORDER BY order_id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
}

// Product Catalog Mapping Array
$products_catalog = [
        1 => "Oak Dining Chair",
        2 => "Large Dining Table",
        3 => "3-Seater Fabric Sofa",
        4 => "Wooden Wardrobe",
        5 => "Industrial Bookshelf",
        6 => "Queen Size Bed Frame"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Forces absolute center alignment across all table elements */
        table th, table td {
            text-align: center !important;
            vertical-align: middle;
            padding: 1.2rem 1.5rem;
        }

        /* Center-align the status badge container wrapper */
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
            <h2><i class="fas fa-boxes"></i> Your Order History</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th style="width: 25%;">Order ID</th>
                    <th style="width: 50%;">Product Name</th>
                    <th style="width: 25%;">Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--gray-wood); padding: 2rem;">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $p_id = intval($order['product_id']);
                        $product_name = isset($products_catalog[$p_id]) ? $products_catalog[$p_id] : "Premium Piece (Item ID: " . $p_id . ")";
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($product_name); ?></td>
                            <td>
                                <div class="badge-container">
                                        <span class="badge-success <?php echo ($order['status'] === 'Pending') ? 'badge-warning' : 'badge-completed'; ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                </div>
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