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
$msg = "";

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid = intval($_POST['oid']);
    $new_status = trim($_POST['status']);

    try {
        $stmt = $pdo->prepare("UPDATE Orders SET status = :status WHERE oid = :oid");
        $stmt->execute(['status' => $new_status, 'oid' => $oid]);
        $msg = "<div style='background-color:#e6f4ea; color:#137333; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>✅ Order #{$oid} status updated to '{$new_status}' successfully!</div>";
    } catch (PDOException $e) {
        $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>⚠️ Failed to update order: " . $e->getMessage() . "</div>";
    }
}

// Fetch orders with correct column names
try {
    $query = "SELECT o.oid, o.odate, o.odeliverydate, o.odeliveraddress, o.status, o.ototalamount,
                     c.cname, c.ctel
              FROM Orders o
              LEFT JOIN Customers c ON o.cid = c.cid
              ORDER BY o.odate DESC";
    $orders = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }
        .status-open {
            background-color: #f1f3f4;
            color: #5f6368;
        }
        .status-processing {
            background-color: #fef7e0;
            color: #b06000;
        }
        .status-delivered {
            background-color: #e6f4ea;
            color: #137333;
        }
        .status-completed {
            background-color: #e8f0fe;
            color: #1a73e8;
        }
        .status-cancelled {
            background-color: #fce4e4;
            color: #cc0000;
        }
        .action-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin: 0;
        }
        .status-select {
            padding: 0.4rem 0.6rem;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 0.85rem;
            background: white;
        }
        .order-details {
            font-size: 0.8rem;
            color: #666;
        }
        .order-details strong {
            color: var(--wood-dark);
        }
        .btn-update {
            padding: 0.3rem 0.75rem;
            font-size: 0.85rem;
            background: var(--wood-medium);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-update:hover {
            background: var(--wood-dark);
        }
        .btn-update i {
            margin-right: 0;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Staff Portal</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="insert-furniture.php">Insert Furniture</a></li>
        <li><a href="insert-material.php">Insert Material</a></li>
        <li><a href="manage-orders.php" class="active">Manage Orders</a></li>
        <li><a href="generate-report.php">Generate Report</a></li>
        <li><a href="delete-furniture.php">Delete Furniture</a></li>
        <li><a href="login.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-boxes"></i> Customer Orders Fulfillment Pipeline</h2>
            <p>Manage and update order statuses</p>
        </div>

        <?php echo $msg; ?>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Order Date</th>
                    <th>Delivery Date</th>
                    <th>Address</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem; color: #999;">
                            <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No customer orders found in the system.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $row):
                        $status_clean = strtolower($row['status'] ?? 'open');
                        $badge_class = 'status-open';
                        if ($status_clean === 'processing') $badge_class = 'status-processing';
                        if ($status_clean === 'delivered') $badge_class = 'status-delivered';
                        if ($status_clean === 'completed') $badge_class = 'status-completed';
                        if ($status_clean === 'cancelled') $badge_class = 'status-cancelled';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $row['oid']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['cname'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['ctel'] ?? 'N/A'); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['odate'])); ?></td>
                            <td><?php echo !empty($row['odeliverydate']) ? date('Y-m-d', strtotime($row['odeliverydate'])) : 'Not Set'; ?></td>
                            <td style="max-width: 150px; font-size: 0.85rem;">
                                <?php
                                $address = $row['odeliveraddress'] ?? '';
                                echo htmlspecialchars(substr($address, 0, 30)) . (strlen($address) > 30 ? '...' : '');
                                ?>
                            </td>
                            <td><strong>$<?php echo number_format($row['ototalamount'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($row['status'] ?? 'Open'); ?>
                                </span>
                            </td>
                            <td>
                                <form action="manage-orders.php" method="POST" class="action-form">
                                    <input type="hidden" name="oid" value="<?php echo $row['oid']; ?>">
                                    <select name="status" class="status-select">
                                        <option value="Open" <?php echo ($row['status'] ?? '') == 'Open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="Processing" <?php echo ($row['status'] ?? '') == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Delivered" <?php echo ($row['status'] ?? '') == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Completed" <?php echo ($row['status'] ?? '') == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo ($row['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-update">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
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
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>