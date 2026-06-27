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
    // Fetch orders with product details
    $stmt = $conn->prepare("SELECT o.oid, o.status, o.odate, of.fid, of.oqty, f.fname, f.fprice 
                            FROM orders o
                            JOIN orderfurnitures of ON o.oid = of.oid
                            JOIN furnitures f ON of.fid = f.fid
                            WHERE o.cid = ? 
                            ORDER BY o.oid DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - Premium Living</title>
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
            <h2><i class="fas fa-boxes"></i> Your Order History</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #a89f91; padding: 2rem;">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $total = $order['oqty'] * $order['fprice'];
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['oid']; ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($order['odate'])); ?></td>
                            <td><?php echo htmlspecialchars($order['fname']); ?></td>
                            <td><?php echo $order['oqty']; ?></td>
                            <td>$<?php echo number_format($order['fprice'], 2); ?></td>
                            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            <td>
                                <span class="badge-success <?php echo ($order['status'] === 'Pending' || $order['status'] === 'Open') ? 'badge-warning' : 'badge-completed'; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view-order-details.php?oid=<?php echo $order['oid']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; text-decoration: none;">
                                    <i class="fas fa-eye"></i>
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