<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is customer (not staff)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
    header("Location: ../staff/dashboard.php");
    exit();
}

$message = "";
$message_type = "";
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Customer';

// ===== USE conn.php =====
require_once '../conn.php';

// Process Order Cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['oid'])) {
    $order_id = intval($_POST['oid']);

    try {
        // Check order details (status, delivery date)
        $check_stmt = $pdo->prepare("SELECT status, odeliverydate FROM orders WHERE oid = ? AND cid = ?");
        $check_stmt->execute([$order_id, $user_id]);
        $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $message = "Order not found.";
            $message_type = "alert-warning";
        } elseif ($order['status'] !== 'Open' && $order['status'] !== 'Pending') {
            $message = "This order cannot be cancelled because it has already been processed.";
            $message_type = "alert-warning";
        } else {
            // ===== CHECK 2-DAY DELIVERY RULE =====
            $delivery_date = new DateTime($order['odeliverydate']);
            $today = new DateTime();
            $diff = $today->diff($delivery_date)->days;

            if ($delivery_date < $today) {
                $message = "This order has already passed its delivery date. Cannot cancel.";
                $message_type = "alert-warning";
            } elseif ($diff < 2) {
                $message = "This order cannot be cancelled because delivery is less than 2 days away. (Delivery: " . date('Y-m-d', strtotime($order['odeliverydate'])) . ")";
                $message_type = "alert-warning";
            } else {
                // ===== PROCEED WITH DELETION AND STOCK RESTORATION =====
                $pdo->beginTransaction();

                // 1. Get furniture items and quantities from the order
                $items_stmt = $pdo->prepare("SELECT fid, oqty FROM orderfurnitures WHERE oid = ?");
                $items_stmt->execute([$order_id]);
                $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Restore material stock for each item
                foreach ($order_items as $item) {
                    $mat_stmt = $pdo->prepare("SELECT mid, pmqty FROM furniturematerials WHERE fid = ?");
                    $mat_stmt->execute([$item['fid']]);
                    $materials = $mat_stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($materials as $mat) {
                        $stock_to_add = $mat['pmqty'] * $item['oqty'];
                        $update_stmt = $pdo->prepare("UPDATE materials SET mqty = mqty + ? WHERE mid = ?");
                        $update_stmt->execute([$stock_to_add, $mat['mid']]);
                    }
                }

                // 3. Delete from orderfurnitures
                $stmt1 = $pdo->prepare("DELETE FROM orderfurnitures WHERE oid = ?");
                $stmt1->execute([$order_id]);

                // 4. Delete from orders
                $stmt2 = $pdo->prepare("DELETE FROM orders WHERE oid = ? AND cid = ?");
                $stmt2->execute([$order_id, $user_id]);

                if ($stmt2->rowCount() > 0) {
                    $pdo->commit();
                    $message = "Order #$order_id was successfully cancelled and removed. Stock has been restored.";
                    $message_type = "alert-success";
                } else {
                    $pdo->rollBack();
                    $message = "Could not delete order. It may have already been processed or removed.";
                    $message_type = "alert-warning";
                }
            }
        }
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $message = "Error cancelling order: " . $e->getMessage();
        $message_type = "alert-danger";
    }
}

// Fetch Active Orders with delivery date
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT o.oid, o.odeliverydate, of.fid, f.fname 
                            FROM orders o
                            JOIN orderfurnitures of ON o.oid = of.oid
                            JOIN furnitures f ON of.fid = f.fid
                            WHERE o.cid = ? AND (o.status = 'Open' OR o.status = 'Pending')
                            ORDER BY o.oid DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Order - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --wood-dark: #3e2a21;
            --wood-medium: #5c3d2e;
            --wood-light: #8b5e3c;
            --wood-bg: #f5efe6;
            --cream: #fdf8f0;
            --accent-gold: #d4a373;
            --gray-wood: #a89f91;
            --shadow-soft: 0 8px 30px rgba(0,0,0,0.08);
            --shadow-warm: 0 12px 28px rgba(62, 42, 33, 0.12);
            --radius-card: 1.25rem;
            --radius-btn: 2rem;
            --input-border: #d4c4a8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--wood-bg); color: var(--wood-dark); line-height: 1.5; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }

        .navbar {
            background: var(--wood-dark);
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            border-bottom: 3px solid var(--accent-gold);
            margin-bottom: 2rem;
        }
        .logo h1 { font-size: 1.3rem; margin: 0; }
        .logo a { color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .logo a i { color: var(--accent-gold); font-size: 1.2rem; }
        .nav-links { display: flex; list-style: none; gap: 0.4rem; flex-wrap: wrap; align-items: center; }
        .nav-links a { color: rgba(255,255,255,0.85); text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 6px; transition: all 0.3s; font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem; }
        .nav-links a:hover { background: rgba(212,163,115,0.2); color: var(--accent-gold); }
        .nav-links a.active { background: rgba(212,163,115,0.15); color: var(--accent-gold); }

        .card { background: white; border-radius: var(--radius-card); box-shadow: var(--shadow-soft); margin-bottom: 2rem; overflow: hidden; }
        .card-header { padding: 1.2rem 2rem; border-bottom: 2px solid var(--accent-gold); background: var(--cream); }
        .card-header h2 { font-size: 1.3rem; color: var(--wood-dark); font-family: 'Playfair Display', serif; margin: 0; }
        .card-header h2 i { color: var(--accent-gold); margin-right: 0.5rem; }
        .card-header .warning-text { color: #e74c3c; font-size: 0.9rem; margin-top: 0.3rem; }
        .card-header .info-text { color: var(--wood-light); font-size: 0.85rem; margin-top: 0.2rem; }

        .table-container { overflow-x: auto; padding: 0; }
        .table-container table { width: 100%; border-collapse: collapse; }
        .table-container thead { background: var(--wood-dark); color: white; }
        .table-container th { padding: 0.8rem 1.2rem; text-align: center; font-weight: 600; font-size: 0.85rem; }
        .table-container td { padding: 0.8rem 1.2rem; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--wood-dark); font-size: 0.9rem; }
        .table-container tbody tr:hover { background: rgba(212,163,115,0.05); }

        .btn { display: inline-block; padding: 0.5rem 1.2rem; border: none; border-radius: var(--radius-btn); cursor: pointer; font-weight: 600; text-decoration: none; transition: all 0.3s ease; font-size: 0.85rem; text-align: center; font-family: 'Inter', sans-serif; }
        .btn-primary { background: var(--accent-gold); color: var(--wood-dark); }
        .btn-primary:hover { background: #c49363; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(212,163,115,0.4); }
        .btn-danger { background: #cc0000; color: white; }
        .btn-danger:hover { background: #a00000; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(204,0,0,0.4); }
        .btn-disabled { background: var(--gray-wood); color: white; cursor: not-allowed; opacity: 0.6; }
        .btn-disabled:hover { transform: none; box-shadow: none; }

        .alert { padding: 0.8rem 1rem; border-radius: 0.8rem; margin: 1.5rem 2rem 0; font-size: 0.9rem; text-align: center; font-weight: 500; }
        .alert-success { background: #e6f4ea; color: #2d6a4f; border: 1px solid #2d6a4f; }
        .alert-danger { background: #fde8e8; color: #9d6b53; border: 1px solid #9d6b53; }
        .alert-warning { background: #fef3e2; color: #8a5a2a; border: 1px solid #e9b35f; }

        .empty-state { text-align: center; padding: 3rem 2rem; color: var(--gray-wood); }
        .empty-state i { font-size: 3rem; color: var(--accent-gold); opacity: 0.5; margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.2rem; color: var(--wood-dark); margin-bottom: 0.5rem; }

        .delivery-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
        .delivery-soon { background: #fef3e2; color: #b06000; }
        .delivery-ok { background: #e6f4ea; color: #137333; }
        .delivery-passed { background: #fce4e4; color: #cc0000; }

        footer { background: var(--wood-dark); color: rgba(255,255,255,0.7); text-align: center; padding: 1.5rem; margin-top: 2rem; border-top: 3px solid var(--accent-gold); }
        footer i { color: var(--accent-gold); margin-right: 0.5rem; }

        @media (max-width: 768px) {
            .navbar { flex-direction: column; text-align: center; padding: 0.8rem 1rem; }
            .nav-links { justify-content: center; gap: 0.3rem; }
            .nav-links a { font-size: 0.75rem; padding: 0.2rem 0.5rem; }
            .card-header { padding: 1rem; }
            .card-header h2 { font-size: 1.1rem; }
            .alert { margin: 1rem 1rem 0; }
            .table-container table { min-width: 500px; }
        }
        @media (max-width: 480px) { .container { padding: 0 1rem; } .btn-danger { padding: 0.4rem 0.8rem; font-size: 0.75rem; } }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="../index.php">
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
        <li><a href="delete-order.php" class="active"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-trash-alt"></i> Cancel Pending Orders</h2>
            <p class="warning-text"><i class="fas fa-exclamation-triangle"></i> Only orders with status "Open" or "Pending" can be cancelled.</p>
            <p class="info-text"><i class="fas fa-clock"></i> Orders can only be cancelled if delivery date is at least 2 days away.</p>
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
                    <th>Delivery Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <h3>No Orders to Cancel</h3>
                                <p>You don't have any active or pending orders to cancel.</p>
                                <a href="make-order.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">
                                    <i class="fas fa-shopping-cart"></i> Make an Order
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $delivery_date = new DateTime($order['odeliverydate']);
                        $today = new DateTime();
                        $diff = $today->diff($delivery_date)->days;
                        $can_delete = ($delivery_date > $today && $diff >= 2);
                        $is_past = ($delivery_date < $today);

                        $badge_class = 'delivery-ok';
                        $badge_text = date('Y-m-d', strtotime($order['odeliverydate'])) . ' ✅';
                        if ($is_past) {
                            $badge_class = 'delivery-passed';
                            $badge_text = date('Y-m-d', strtotime($order['odeliverydate'])) . ' (Passed)';
                        } elseif (!$can_delete) {
                            $badge_class = 'delivery-soon';
                            $badge_text = date('Y-m-d', strtotime($order['odeliverydate'])) . ' (' . $diff . ' days)';
                        }
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['oid']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['fname']); ?></td>
                            <td>
                                    <span class="delivery-badge <?php echo $badge_class; ?>">
                                        <i class="fas <?php echo $is_past ? 'fa-times-circle' : ($can_delete ? 'fa-check-circle' : 'fa-clock'); ?>"></i>
                                        <?php echo $badge_text; ?>
                                    </span>
                            </td>
                            <td>
                                <?php if ($can_delete): ?>
                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This will restore material stock and cannot be undone.');">
                                        <input type="hidden" name="oid" value="<?php echo $order['oid']; ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel Order
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>
                                        <i class="fas fa-ban"></i> Cannot Cancel
                                    </button>
                                    <div style="font-size: 0.7rem; color: var(--gray-wood); margin-top: 3px;">
                                        <?php if ($is_past): ?>
                                            Delivery date has passed
                                        <?php else: ?>
                                            Less than 2 days until delivery (<?php echo $diff; ?> days)
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
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