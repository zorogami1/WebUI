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
$full_name = $_SESSION['full_name'] ?? 'Valued Customer';

// ===== USE conn.php INSTEAD OF DIRECT CONNECTION =====
require_once '../conn.php';

// Product Catalog Mapping
$products_catalog = [
        1 => ["name" => "Oak Dining Chair", "price" => 450],
        2 => ["name" => "Large Dining Table", "price" => 2500],
        3 => ["name" => "3-Seater Fabric Sofa", "price" => 3800],
        4 => ["name" => "Wooden Wardrobe", "price" => 1800],
        5 => ["name" => "Industrial Bookshelf", "price" => 1200],
        6 => ["name" => "Queen Size Bed Frame", "price" => 2200]
];

$total_orders = 0;
$total_spent = 0;
$pending_count = 0;
$recent_orders = [];

try {
    // Fetch all orders for this user
    $metrics_query = "SELECT oid, status FROM orders WHERE cid = ?";
    $stmt = $pdo->prepare($metrics_query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $total_orders++;

        if ($row['status'] === 'Pending' || $row['status'] === 'Open') {
            $pending_count++;
        }
    }

    // Fetch Recent Orders with product details
    $table_query = "SELECT o.oid, o.status, of.fid, of.oqty, f.fname, f.fprice 
                    FROM orders o
                    JOIN orderfurnitures of ON o.oid = of.oid
                    JOIN furnitures f ON of.fid = f.fid
                    WHERE o.cid = ? 
                    ORDER BY o.oid DESC 
                    LIMIT 5";
    $stmt = $pdo->prepare($table_query);
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total spent
    $spent_query = "SELECT SUM(of.oqty * f.fprice) as total_spent 
                    FROM orders o
                    JOIN orderfurnitures of ON o.oid = of.oid
                    JOIN furnitures f ON of.fid = f.fid
                    WHERE o.cid = ?";
    $stmt = $pdo->prepare($spent_query);
    $stmt->execute([$user_id]);
    $spent_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_spent = $spent_result['total_spent'] ?? 0;

} catch (PDOException $e) {
    // Handle error silently or log it
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Premium Living</title>
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

        .card-body {
            padding: 1.5rem 2rem;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            padding: 1.5rem 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-card);
            text-align: center;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s;
            border: 1px solid rgba(139,94,60,0.08);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-warm);
        }

        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--wood-dark);
        }

        .stat-card .stat-label {
            color: var(--wood-light);
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.3rem;
        }

        /* ===== TABLE ===== */
        .table-container {
            overflow-x: auto;
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
            padding: 0.8rem 1.2rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .table-container td {
            padding: 0.8rem 1.2rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--wood-dark);
            font-size: 0.9rem;
        }

        .table-container tbody tr:hover {
            background: rgba(212, 163, 115, 0.05);
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef3e2;
            color: #8a5a2a;
        }

        .badge-completed {
            background: #e6f4ea;
            color: #2d6a4f;
        }

        .badge-open {
            background: #f1f3f4;
            color: #5f6368;
        }

        .badge-processing {
            background: #fef7e0;
            color: #b06000;
        }

        .badge-delivered {
            background: #e6f4ea;
            color: #137333;
        }

        .badge-cancelled {
            background: #fce4e4;
            color: #cc0000;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: var(--radius-btn);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.85rem;
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

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                padding: 1rem;
            }

            .stat-card .stat-number {
                font-size: 2rem;
            }

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .table-container table {
                min-width: 500px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
        <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="make-order.php"><i class="fas fa-shopping-cart"></i> Make Order</a></li>
        <li><a href="view-orders.php"><i class="fas fa-list"></i> Orders</a></li>
        <li><a href="update-profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <!-- Welcome Card -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-leaf"></i> Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($total_spent); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Card -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Recent Orders</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No orders yet</h3>
                                <p>Start shopping to see your orders here!</p>
                                <a href="make-order.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">
                                    <i class="fas fa-shopping-cart"></i> Make Your First Order
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order):
                        $total = $order['oqty'] * $order['fprice'];
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
                            <td><?php echo htmlspecialchars($order['fname']); ?></td>
                            <td><?php echo $order['oqty']; ?></td>
                            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                            </td>
                            <td>
                                <a href="view-order-details.php?oid=<?php echo $order['oid']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.75rem;">
                                    <i class="fas fa-eye"></i> View
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