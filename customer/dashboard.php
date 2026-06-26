<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Route Protection Guard
if (!isset($_SESSION['cid'])) {
    header("Location: login.php");
    exit();
}

require_once '../conn.php';
$customer_id = $_SESSION['cid'];

try {
    // 1. Calculate Summary Metric Values dynamically
    // Total Orders count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as total_orders FROM Orders WHERE cid = :cid");
    $stmtCount->execute(['cid' => $customer_id]);
    $total_orders = $stmtCount->fetch(PDO::FETCH_ASSOC)['total_orders'] ?? 0;

    // Total Cash Spent sum
    $stmtSum = $pdo->prepare("SELECT SUM(ototalamount) as total_spent FROM Orders WHERE cid = :cid");
    $stmtSum->execute(['cid' => $customer_id]);
    $total_spent = $stmtSum->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0.00;

    // In Progress / Processing count (where status = 1 [Open] or 2 [Processing])
    $stmtProg = $pdo->prepare("SELECT COUNT(*) as in_progress FROM Orders WHERE cid = :cid AND ostatus IN (1, 2)");
    $stmtProg->execute(['cid' => $customer_id]);
    $in_progress = $stmtProg->fetch(PDO::FETCH_ASSOC)['in_progress'] ?? 0;

    // 2. Fetch the 5 most recent orders for the table view
    $stmtRecent = $pdo->prepare("SELECT * FROM Orders WHERE cid = :cid ORDER BY odate DESC LIMIT 5");
    $stmtRecent->execute(['cid' => $customer_id]);
    $recent_orders = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error loading customer dashboard summary metrics: " . $e->getMessage());
}

// Convert numbers into matching styling badges
function getDashboardStatusBadge($status) {
    switch($status) {
        case 1: return '<span class="badge-warning" style="background:#e8dccc; color:#5c3d2e; padding:0.25rem 0.75rem; border-radius:4px; font-size:0.8rem;">Open</span>';
        case 2: return '<span class="badge-warning" style="background:#fef3e2; color:#b47c2e; padding:0.25rem 0.75rem; border-radius:4px; font-size:0.8rem;">Processing</span>';
        case 3: return '<span class="badge-success" style="background:#e6f4ea; color:#2d6a4f; padding:0.25rem 0.75rem; border-radius:4px; font-size:0.8rem;">Approved</span>';
        default: return '<span class="badge-success" style="background:#d4e2d4; color:#1b4d3e; padding:0.25rem 0.75rem; border-radius:4px; font-size:0.8rem;">Completed</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Premium Living</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="make-order.php"><i class="fas fa-shopping-cart"></i> Make Order</a></li>
        <li><a href="view-orders.php"><i class="fas fa-list"></i> Orders</a></li>
        <li><a href="update-profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Welcome Back, <?php echo htmlspecialchars($_SESSION['cname']); ?>!</h2>
            <p>Customer Account ID Reference: <strong>#<?php echo $customer_id; ?></strong></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $total_orders; ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card"><div class="stat-number">$<?php echo number_format($total_spent, 2); ?></div><div class="stat-label">Total Spent</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $in_progress; ?></div><div class="stat-label">In Progress</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-clock"></i> Recent Orders</h2></div>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date Placed</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #777; padding: 1.5rem;">No recent furniture orders found. Click "Make Order" to get started!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $row): ?>
                        <tr>
                            <td><strong>#<?php echo $row['oid']; ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($row['odate'])); ?></td>
                            <td><strong>$<?php echo number_format($row['ototalamount'], 2); ?></strong></td>
                            <td><?php echo getDashboardStatusBadge($row['ostatus']); ?></td>
                            <td>
                                <a href="view-order-details.php?oid=<?php echo $row['oid']; ?>" class="btn btn-secondary" style="padding: 0.3rem 1rem; text-decoration: none; font-size: 0.85rem;">View Items</a>
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
    <p>&copy; 2026 Premium Living Furniture | Sustainably Crafted</p>
</footer>
</body>
</html>