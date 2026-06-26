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

$success_msg = "";
$error_msg = "";

// Handle Deletion Request Form POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $order_id = intval($_POST['oid'] ?? 0);

    try {
        // 1. Fetch order details and target delivery date while ensuring it belongs to the logged-in customer
        $stmtOrder = $pdo->prepare("SELECT odeliverydate, ostatus FROM Orders WHERE oid = :oid AND cid = :cid");
        $stmtOrder->execute(['oid' => $order_id, 'cid' => $customer_id]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $error_msg = "Order record not found or access denied.";
        } elseif (intval($order['ostatus']) === 3) {
            $error_msg = "Order has already been Approved/Shipped and cannot be deleted.";
        } else {
            // 2. Core Business Rule Validation: Check if the current time is at least 2 days before delivery
            $delivery_timestamp = strtotime($order['odeliverydate']);
            $current_timestamp = time();
            $seconds_in_two_days = 2 * 24 * 60 * 60;

            if (($delivery_timestamp - $current_timestamp) < $seconds_in_two_days) {
                $error_msg = "⚠️ Cancellation Rejected: Orders can only be deleted at least 2 days prior to the scheduled delivery date.";
            } else {
                // Everything is valid! Start an InnoDB transaction to perform the deletion and restock
                $pdo->beginTransaction();

                // 3. Find all items inside this order to compute materials restocking amounts
                $stmtItems = $pdo->prepare("SELECT fid, oqty FROM OrderFurnitures WHERE oid = :oid");
                $stmtItems->execute(['oid' => $order_id]);
                $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    $fid = $item['fid'];
                    $oqty = $item['oqty'];

                    // Fetch recipe requirements for this furniture item
                    $stmtRecipe = $pdo->prepare("SELECT mid, pmqty FROM FurnitureMaterials WHERE fid = :fid");
                    $stmtRecipe->execute(['fid' => $fid]);
                    $recipes = $stmtRecipe->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($recipes as $recipe) {
                        $mid = $recipe['mid'];
                        $restock_qty = $recipe['pmqty'] * $oqty;

                        // Add the materials back to the inventory warehouse stock
                        $updateMat = $pdo->prepare("UPDATE Materials SET mqty = mqty + :qty WHERE mid = :mid");
                        $updateMat->execute(['qty' => $restock_qty, 'mid' => $mid]);
                    }
                }

                // 4. Remove the dependencies from OrderFurnitures first
                $deleteItems = $pdo->prepare("DELETE FROM OrderFurnitures WHERE oid = :oid");
                $deleteItems->execute(['oid' => $order_id]);

                // 5. Delete the parent order record
                $deleteOrder = $pdo->prepare("DELETE FROM Orders WHERE oid = :oid");
                $deleteOrder->execute(['oid' => $order_id]);

                $pdo->commit();
                $success_msg = "Order #$order_id has been successfully canceled and its raw materials have been restocked into the inventory database.";
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

// Fetch all live current orders belonging to this customer for table layout display
try {
    $stmtList = $pdo->prepare("SELECT * FROM Orders WHERE cid = :cid ORDER BY odate DESC");
    $stmtList->execute(['cid' => $customer_id]);
    $my_orders = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading orders list: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Order - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Modal Window Styling matching the legacy system layout */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fdfaf4; padding: 2rem; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.2); text-align: center; margin: 15% auto; }
        .close { float: right; font-size: 1.5rem; font-weight: bold; cursor: pointer; color: #aaa; }
        .close:hover { color: #000; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php">🏠 Premium Living</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="make-order.php">Make Order</a></li>
        <li><a href="view-orders.php">Orders</a></li>
        <li><a href="update-profile.php">Profile</a></li>
        <li><a href="delete-order.php" class="active">Delete Order</a></li>
        <li><a href="../index.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Delete Order Record</h2>
            <p style="color: #e74c3c; font-weight: bold;">⚠️ Safety Policy: Orders can only be deleted up to two days (48 hours) before the scheduled delivery date.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div style="background-color: #fce4e4; color: #cc0000; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 5px solid #cc0000;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div style="background-color: #e6f4ea; color: #137333; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 5px solid #137333;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Delivery Date</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($my_orders)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: #777;">No transaction records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($my_orders as $row): ?>
                        <tr>
                            <td><strong>#<?php echo $row['oid']; ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($row['odate'])); ?></td>
                            <td><span style="color: #bc6c25; font-weight: 600;"><?php echo date('Y-m-d', strtotime($row['odeliverydate'])); ?></span></td>
                            <td><strong>$<?php echo number_format($row['ototalamount'], 2); ?></strong></td>
                            <td>
                                <button class="btn btn-danger" style="padding: 0.3rem 1rem; font-size: 0.85rem;" onclick="openDeleteModal(<?php echo $row['oid']; ?>)">
                                    <i class="fas fa-trash-alt"></i> Cancel Order
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h3 style="color: #3e2a21;"><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Confirm Deletion</h3>
        <p>Are you sure you want to cancel and permanently delete order <strong id="modalOrderRef">#0</strong>?</p>
        <p style="color: #e74c3c; font-size: 0.85rem; background: #fff1f0; padding: 0.5rem; border-radius: 4px;">⚠️ This action cannot be undone. All matching calculated recipe resources will automatically return into factory warehouse inventory metrics.</p>

        <form action="delete-order.php" method="POST" style="margin-top: 1.5rem;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" id="deleteTargetOid" name="oid" value="">
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1.5rem;">Yes, Delete Order</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.5rem 1.5rem;" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeleteModal(orderId) {
        document.getElementById('deleteTargetOid').value = orderId;
        document.getElementById('modalOrderRef').innerText = '#' + orderId;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }
</script>

<footer>
    <p>&copy; 2026 Premium Living Furniture | Sustainably Crafted</p>
</footer>
</body>
</html>