<?php
// Ensure session tracking is running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ROUTE SECURITY GUARD CHECK
if (!isset($_SESSION['cid'])) {
    die("<h3>Access Denied: You must be logged in to view this page.</h3><p><a href='login.php'>Go to Login</a></p>");
}

// Include database connection file
require_once '../conn.php';

$customer_id = $_SESSION['cid'];

// Capture and sanitize the Order ID passed from the dashboard URL parameter (?oid=X)
$order_id = isset($_GET['oid']) ? intval($_GET['oid']) : 0;

if ($order_id <= 0) {
    die("<h3>Invalid Order Reference ID</h3><p><a href='dashboard.php'>Return to Dashboard</a></p>");
}

try {
    // 2. FETCH PARENT ORDER RECORD & VALIDATE ACCOUNT OWNERSHIP
    // Matches exact table columns from your createProjectDB.sql: 'oid', 'cid', 'odate', etc.
    $orderQuery = "SELECT * FROM Orders WHERE oid = :oid AND cid = :cid";
    $stmtOrder = $pdo->prepare($orderQuery);
    $stmtOrder->execute(['oid' => $order_id, 'cid' => $customer_id]);
    $orderMaster = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$orderMaster) {
        die("<h3>Error: Order #" . htmlspecialchars($order_id) . " not found or access is denied.</h3><p><a href='dashboard.php'>Return to Dashboard</a></p>");
    }

    // 3. INNER JOIN TO FETCH ITEMS IN THIS SPECIFIC SHIPMENT
    // Joins 'OrderFurnitures' (of) and 'Furnitures' (f) on 'fid'
    $itemsQuery = "SELECT of.oqty, f.fname, f.fprice, f.fdesc, (of.oqty * f.fprice) as subtotal
                   FROM OrderFurnitures of
                   INNER JOIN Furnitures f ON of.fid = f.fid
                   WHERE of.oid = :oid";
    $stmtItems = $pdo->prepare($itemsQuery);
    $stmtItems->execute(['oid' => $order_id]);
    $basket_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Prints out the exact database error on screen if the query fails
    die("<h3>Database Query Error:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><p><a href='dashboard.php'>Return to Dashboard</a></p>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Premium Living</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="make-order.php">Make Order</a></li>
        <li><a href="view-orders.php">Orders</a></li>
        <li><a href="update-profile.php">Profile</a></li>
        <li><a href="../index.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <a href="dashboard.php" style="color: #8b5e3c; text-decoration: none; font-weight: bold;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard Overview
        </a>
    </div>

    <div class="card">
        <div class="card-header" style="border-bottom: 2px solid #eaeaea; padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h2><i class="fas fa-file-invoice"></i> Items inside Order #<?php echo $order_id; ?></h2>
            <p>Placed on: <strong><?php echo date('Y-m-d H:i', strtotime($orderMaster['odate'])); ?></strong></p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; background: #faf8f5; padding: 1.5rem; border-radius: 8px; border: 1px solid #eee;">
            <div>
                <h4 style="margin: 0 0 0.5rem 0; color: #5c3d2e;"><i class="fas fa-truck"></i> Shipping Destination</h4>
                <p style="margin: 0; color: #555; line-height: 1.4;"><?php echo htmlspecialchars($orderMaster['odeliveraddress']); ?></p>
            </div>
            <div>
                <h4 style="margin: 0 0 0.5rem 0; color: #5c3d2e;"><i class="fas fa-calendar-alt"></i> Delivery Date</h4>
                <p style="margin: 0; color: #555;"><strong><?php echo date('Y-m-d', strtotime($orderMaster['odeliverydate'])); ?></strong></p>
            </div>
        </div>

        <h3>Handcrafted Furniture Breakdown</h3>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                <tr style="background-color: #8b5e3c; color: white;">
                    <th style="padding: 10px; text-align: left;">Product Name</th>
                    <th style="padding: 10px; text-align: center;">Unit Price</th>
                    <th style="padding: 10px; text-align: center;">Quantity</th>
                    <th style="padding: 10px; text-align: right;">Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($basket_items)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 1.5rem; color: #777;">No items found for this order record.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($basket_items as $product): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; text-align: left;">
                                <strong><?php echo htmlspecialchars($product['fname']); ?></strong>
                                <div style="font-size: 0.8rem; color: #777; margin-top: 2px;"><?php echo htmlspecialchars($product['fdesc']); ?></div>
                            </td>
                            <td style="padding: 12px; text-align: center;">$<?php echo number_format($product['fprice'], 2); ?></td>
                            <td style="padding: 12px; text-align: center;"><strong><?php echo $product['oqty']; ?></strong></td>
                            <td style="padding: 12px; text-align: right; color: #8b5e3c; font-weight: bold;">$<?php echo number_format($product['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f5ece1; font-size: 1.1rem; border-top: 2px solid #8b5e3c;">
                        <td colspan="3" style="text-align: right; font-weight: bold; padding: 12px;">Total Order Cost:</td>
                        <td style="text-align: right; font-weight: bold; color: #5c3d2e; padding: 12px;">$<?php echo number_format($orderMaster['ototalamount'], 2); ?></td>
                    </tr>
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