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

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Customer';

// ===== SORTING PARAMETERS =====
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'oid';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Allowed sort columns (for security)
$allowed_sort = ['oid', 'odate', 'odeliverydate', 'status', 'ototalamount', 'fname'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'oid';
}
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// ===== USE conn.php =====
require_once '../conn.php';

$orders = [];
try {
    // Fetch orders with ALL required fields
    $query = "SELECT o.oid, o.cid, o.status, o.odate, o.odeliverydate, of.fid, of.oqty, f.fname, f.fprice, o.ototalamount
              FROM orders o
              JOIN orderfurnitures of ON o.oid = of.oid
              JOIN furnitures f ON of.fid = f.fid
              WHERE o.cid = ? 
              ORDER BY $sort_by $sort_order";
    $stmt = $pdo->prepare($query);
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
    <title>Your Orders - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== COMPLETE STYLES ===== */
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--wood-bg);
            color: var(--wood-dark);
            line-height: 1.5;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* ===== NAVBAR ===== */
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

        .logo h1 {
            font-size: 1.3rem;
            margin: 0;
        }

        .logo a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo a i {
            color: var(--accent-gold);
            font-size: 1.2rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 0.4rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .nav-links a:hover {
            background: rgba(212, 163, 115, 0.2);
            color: var(--accent-gold);
        }

        .nav-links a.active {
            background: rgba(212, 163, 115, 0.15);
            color: var(--accent-gold);
        }

        /* ===== CARD ===== */
        .card {
            background: white;
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.2rem 2rem;
            border-bottom: 2px solid var(--accent-gold);
            background: var(--cream);
        }

        .card-header h2 {
            font-size: 1.3rem;
            color: var(--wood-dark);
            font-family: 'Playfair Display', serif;
            margin: 0;
        }

        .card-header h2 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .card-header p {
            color: var(--gray-wood);
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        /* ===== SORT CONTROLS ===== */
        .sort-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 2rem;
            background: var(--cream);
            border-bottom: 1px solid var(--input-border);
        }

        .sort-controls label {
            font-weight: 600;
            color: var(--wood-dark);
            font-size: 0.85rem;
        }

        .sort-controls label i {
            color: var(--accent-gold);
            margin-right: 0.3rem;
        }

        .sort-controls select {
            padding: 0.4rem 0.8rem;
            border: 1.5px solid var(--input-border);
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            color: var(--wood-dark);
            background: white;
            outline: none;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 200px;
        }

        .sort-controls select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15);
        }

        /* ===== TABLE ===== */
        .table-container {
            overflow-x: auto;
            padding: 0;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container thead {
            background: var(--wood-dark);
            color: white;
        }

        .table-container th {
            padding: 0.8rem 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .table-container td {
            padding: 0.8rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--wood-dark);
            font-size: 0.8rem;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background: rgba(212, 163, 115, 0.05);
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-pending { background: #fef3e2; color: #8a5a2a; }
        .badge-open { background: #f1f3f4; color: #5f6368; }
        .badge-processing { background: #fef7e0; color: #b06000; }
        .badge-delivered { background: #e6f4ea; color: #137333; }
        .badge-completed { background: #e8f0fe; color: #1a73e8; }
        .badge-cancelled { background: #fce4e4; color: #cc0000; }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: var(--radius-btn);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.75rem;
            text-align: center;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: var(--accent-gold);
            color: var(--wood-dark);
        }

        .btn-primary:hover {
            background: #c49363;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 163, 115, 0.4);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray-wood);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--accent-gold);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            color: var(--wood-dark);
            margin-bottom: 0.5rem;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--wood-dark);
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--accent-gold);
        }

        footer i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .table-container table {
                min-width: 900px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
                padding: 0.8rem 1rem;
            }

            .nav-links {
                justify-content: center;
                gap: 0.3rem;
            }

            .nav-links a {
                font-size: 0.75rem;
                padding: 0.2rem 0.5rem;
            }

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .sort-controls {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 0.5rem;
            }

            .sort-controls select {
                width: 100%;
                min-width: unset;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }
        }
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
        <li><a href="view-orders.php" class="active"><i class="fas fa-list"></i> Orders</a></li>
        <li><a href="update-profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-boxes"></i> Your Order History</h2>
            <p>View all your past and current orders</p>
        </div>

        <!-- ===== SORT CONTROLS ===== -->
        <div class="sort-controls">
            <div>
                <i class="fas fa-sort"></i>
                <label for="sortSelect">Sort by:</label>
            </div>
            <select id="sortSelect" onchange="window.location.href=this.value">
                <option value="?sort=oid&order=DESC" <?php echo ($sort_by === 'oid' && $sort_order === 'DESC') ? 'selected' : ''; ?>>Order ID (Newest First)</option>
                <option value="?sort=oid&order=ASC" <?php echo ($sort_by === 'oid' && $sort_order === 'ASC') ? 'selected' : ''; ?>>Order ID (Oldest First)</option>
                <option value="?sort=odate&order=DESC" <?php echo ($sort_by === 'odate' && $sort_order === 'DESC') ? 'selected' : ''; ?>>Date (Newest First)</option>
                <option value="?sort=odate&order=ASC" <?php echo ($sort_by === 'odate' && $sort_order === 'ASC') ? 'selected' : ''; ?>>Date (Oldest First)</option>
                <option value="?sort=odeliverydate&order=DESC" <?php echo ($sort_by === 'odeliverydate' && $sort_order === 'DESC') ? 'selected' : ''; ?>>Delivery Date (Latest First)</option>
                <option value="?sort=odeliverydate&order=ASC" <?php echo ($sort_by === 'odeliverydate' && $sort_order === 'ASC') ? 'selected' : ''; ?>>Delivery Date (Earliest First)</option>
                <option value="?sort=fname&order=ASC" <?php echo ($sort_by === 'fname' && $sort_order === 'ASC') ? 'selected' : ''; ?>>Product (A-Z)</option>
                <option value="?sort=fname&order=DESC" <?php echo ($sort_by === 'fname' && $sort_order === 'DESC') ? 'selected' : ''; ?>>Product (Z-A)</option>
                <option value="?sort=status&order=ASC" <?php echo ($sort_by === 'status' && $sort_order === 'ASC') ? 'selected' : ''; ?>>Status (A-Z)</option>
                <option value="?sort=status&order=DESC" <?php echo ($sort_by === 'status' && $sort_order === 'DESC') ? 'selected' : ''; ?>>Status (Z-A)</option>
                <option value="?sort=ototalamount&order=DESC" <?php echo ($sort_by === 'ototalamount' && $sort_order === 'DESC') ? 'selected' : ''; ?>>Amount (Highest First)</option>
                <option value="?sort=ototalamount&order=ASC" <?php echo ($sort_by === 'ototalamount' && $sort_order === 'ASC') ? 'selected' : ''; ?>>Amount (Lowest First)</option>
            </select>
        </div>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Delivery Date</th>
                    <th>Product</th>
                    <th>Furniture ID</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Orders Yet</h3>
                                <p>You haven't placed any orders yet.</p>
                                <a href="make-order.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; padding: 0.6rem 1.5rem; font-size: 0.9rem;">
                                    <i class="fas fa-shopping-cart"></i> Start Shopping
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $status = strtolower($order['status'] ?? 'open');
                        $badge_class = 'badge-' . $status;
                        if ($status === 'pending' || $status === 'open') $badge_class = 'badge-pending';
                        if ($status === 'completed') $badge_class = 'badge-completed';
                        if ($status === 'processing') $badge_class = 'badge-processing';
                        if ($status === 'delivered') $badge_class = 'badge-delivered';
                        if ($status === 'cancelled') $badge_class = 'badge-cancelled';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['oid']; ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($order['odate'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($order['odeliverydate'])); ?></td>
                            <td><?php echo htmlspecialchars($order['fname']); ?></td>
                            <td>#<?php echo $order['fid']; ?></td>
                            <td><?php echo $order['oqty']; ?></td>
                            <td><strong>$<?php echo number_format($order['ototalamount'], 2); ?></strong></td>
                            <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                            </td>
                            <td>
                                <a href="view-order-details.php?oid=<?php echo $order['oid']; ?>" class="btn btn-primary">
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