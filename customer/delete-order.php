<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";
$user_id = $_SESSION['user_id'];

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "createprojectdb";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Process Order Cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['oid'])) {
    $order_id = intval($_POST['oid']);

    // Check if order can be deleted (only if status is Open or Pending)
    $check_stmt = $conn->prepare("SELECT status FROM orders WHERE oid = ? AND cid = ?");
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $order = $result->fetch_assoc();
    $check_stmt->close();

    if ($order && ($order['status'] === 'Open' || $order['status'] === 'Pending')) {
        // First delete from orderfurnitures
        $stmt1 = $conn->prepare("DELETE FROM orderfurnitures WHERE oid = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();
        $stmt1->close();

        // Then delete from orders
        $stmt2 = $conn->prepare("DELETE FROM orders WHERE oid = ? AND cid = ?");
        $stmt2->bind_param("ii", $order_id, $user_id);

        if ($stmt2->execute() && $stmt2->affected_rows > 0) {
            $message = "Order #$order_id was successfully cancelled and removed.";
            $message_type = "alert-success";
        } else {
            $message = "Could not delete order. It may have already been processed or removed.";
            $message_type = "alert-warning";
        }
        $stmt2->close();
    } else {
        $message = "This order cannot be cancelled because it has already been processed.";
        $message_type = "alert-warning";
    }
}

// Fetch Active Orders
$orders = [];
if (!$conn->connect_error) {
    $stmt = $conn->prepare("SELECT o.oid, of.fid, f.fname 
                            FROM orders o
                            JOIN orderfurnitures of ON o.oid = of.oid
                            JOIN furnitures f ON of.fid = f.fid
                            WHERE o.cid = ? AND (o.status = 'Open' OR o.status = 'Pending')
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
    <title>Cancel Order - Premium Living</title>
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
            <h2><i class="fas fa-trash-alt"></i> Cancel Pending Orders</h2>
            <p style="color: #e74c3c; font-size: 0.9rem;">Only orders with status "Open" or "Pending" can be cancelled.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #a89f91; padding: 2rem;">
                            <i class="fas fa-folder-open"></i> No active or pending orders found to cancel.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['oid']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['fname']); ?></td>
                            <td>
                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="oid" value="<?php echo $order['oid']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1.4rem; font-size: 0.85rem;">
                                        <i class="fas fa-times"></i> Cancel
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
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>
</body>
</html>