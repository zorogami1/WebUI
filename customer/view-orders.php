<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cid'])) {
    header("Location: login.php");
    exit();
}

require_once '../conn.php';

$customer_id = $_SESSION['cid'];

try {
    // Pull full matching transaction records owned by this individual account holder
    $sql = "SELECT * FROM Orders WHERE cid = :cid ORDER BY odate DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cid' => $customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database transaction retrieval error: " . $e->getMessage());
}

// Map database status integer values to clean, matching visual badge components
function getStatusBadge($statusNum) {
    switch($statusNum) {
        case 1:  return '<span class="status-badge" style="background:#e8dccc; color:#5c3d2e; padding:4px 10px; border-radius:12px; font-size:0.75rem;">Open</span>';
        case 2:  return '<span class="status-badge" style="background:#fef3e2; color:#b47c2e; padding:4px 10px; border-radius:12px; font-size:0.75rem;">Processing</span>';
        case 3:  return '<span class="status-badge" style="background:#e6f4ea; color:#2d6a4f; padding:4px 10px; border-radius:12px; font-size:0.75rem;">Approved</span>';
        default: return '<span class="status-badge" style="background:#fce4d6; color:#9d6b53; padding:4px 10px; border-radius:12px; font-size:0.75rem;">Pending</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Premium Living</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php">🏠 Premium Living</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="make-order.php">Make Order</a></li>
        <li><a href="view-orders.php" class="active">View Orders</a></li>
        <li><a href="update-profile.php">My Profile</a></li>
        <li><a href="delete-order.php">Delete Order</a></li>
        <li><a href="../index.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>My Order Records</h2>
            <p>Track history statements and execution processing flags metrics below.</p>
        </div>

        <div class="table-container">
            <table id="ordersTable">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Scheduled Delivery</th>
                    <th>Shipping Destination Destination Address</th>
                    <th>Total Amount</th>
                    <th>Processing Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #777;">You haven't placed any artisanal furniture orders yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td><strong>#<?php echo $row['oid']; ?></strong></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['odate'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['odeliverydate'])); ?></td>
                            <td><?php echo htmlspecialchars($row['odeliveraddress']); ?></td>
                            <td><strong style="color: #8b5e3c;">$<?php echo number_format($row['ototalamount'], 2); ?></strong></td>
                            <td><?php echo getStatusBadge($row['ostatus']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>