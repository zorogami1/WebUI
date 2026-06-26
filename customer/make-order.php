<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Route security guard protection
if (!isset($_SESSION['cid'])) {
    header("Location: login.php");
    exit();
}

require_once '../conn.php';

try {
    // Fetch live furniture catalog data from database
    $stmt = $pdo->prepare("SELECT * FROM Furnitures ORDER BY fid ASC");
    $stmt->execute();
    $furnitures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading premium catalog products: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Collection - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Premium Living</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="make-order.php" class="active">Make Order</a></li>
        <li><a href="view-orders.php">Orders</a></li>
        <li><a href="update-profile.php">Profile</a></li>
        <li><a href="delete-order.php">Delete Order</a></li>
        <li><a href="../index.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-cart-plus"></i> Our Handcrafted Collection</h2>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['cname']); ?></strong>! Select items below to build your custom artisanal order.</p>
        </div>

        <form action="place-order.php" method="POST">
            <div style="margin-bottom: 2rem; background: #fdfaf4; padding: 1.5rem; border-radius: 8px; border: 1px dashed #8b5e3c;">
                <h3 style="margin-top: 0; color: #3e2a21;"><i class="fas fa-truck"></i> Delivery Specifications</h3>
                <div class="form-group">
                    <label for="delivery_date">Scheduled Delivery Date *</label>
                    <input type="date" id="delivery_date" name="delivery_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group">
                    <label for="delivery_address">Delivery Address for This Order *</label>
                    <textarea id="delivery_address" name="delivery_address" rows="2" required placeholder="Defaults to profile profile address or enter new destination structural target"></textarea>
                </div>
            </div>

            <div class="product-grid">
                <?php if (empty($furnitures)): ?>
                    <p>No artisanal item collections are currently available in our system database.</p>
                <?php else: ?>
                    <?php foreach ($furnitures as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="../images/<?php echo $item['fid']; ?>.png" alt="<?php echo htmlspecialchars($item['fname']); ?>" onerror="this.src='../images/placeholder.png';">
                            </div>
                            <div class="product-info">
                                <div class="product-title"><?php echo htmlspecialchars($item['fname']); ?></div>
                                <div class="product-price">$<?php echo number_format($item['fprice'], 2); ?></div>
                                <div class="product-desc"><?php echo htmlspecialchars($item['fdesc']); ?></div>

                                <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <label style="font-size: 0.85rem; color: #555;">Qty:</label>
                                    <input type="number" name="quantity[<?php echo $item['fid']; ?>]" value="0" min="0" max="20" style="width: 60px; padding: 0.25rem; text-align: center; border-radius: 4px; border: 1px solid #ccc;">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 2.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <i class="fas fa-file-invoice-dollar"></i> Review and Place Order
                </button>
            </div>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living Furniture | Sustainably Crafted</p>
</footer>
</body>
</html>