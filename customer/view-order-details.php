<?php
// Ensure session tracking is running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== FIXED: Check for user_id instead of cid =====
if (!isset($_SESSION['user_id'])) {
    die("<h3>Access Denied: You must be logged in to view this page.</h3><p><a href='login.php'>Go to Login</a></p>");
}

// Include database connection file
require_once '../conn.php';

// ===== FIXED: Use user_id from session =====
$customer_id = $_SESSION['user_id'];

// Capture and sanitize the Order ID passed from the dashboard URL parameter (?oid=X)
$order_id = isset($_GET['oid']) ? intval($_GET['oid']) : 0;

if ($order_id <= 0) {
    die("<h3>Invalid Order Reference ID</h3><p><a href='dashboard.php'>Return to Dashboard</a></p>");
}

try {
    // 2. FETCH PARENT ORDER RECORD & VALIDATE ACCOUNT OWNERSHIP
    $orderQuery = "SELECT * FROM orders WHERE oid = :oid AND cid = :cid";
    $stmtOrder = $pdo->prepare($orderQuery);
    $stmtOrder->execute(['oid' => $order_id, 'cid' => $customer_id]);
    $orderMaster = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$orderMaster) {
        die("<h3>Error: Order #" . htmlspecialchars($order_id) . " not found or access is denied.</h3><p><a href='dashboard.php'>Return to Dashboard</a></p>");
    }

    // 3. INNER JOIN TO FETCH ITEMS IN THIS SPECIFIC SHIPMENT
    $itemsQuery = "SELECT of.oqty, f.fname, f.fprice, f.fdesc, (of.oqty * f.fprice) as subtotal
                   FROM orderfurnitures of
                   INNER JOIN furnitures f ON of.fid = f.fid
                   WHERE of.oid = :oid";
    $stmtItems = $pdo->prepare($itemsQuery);
    $stmtItems->execute(['oid' => $order_id]);
    $basket_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
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

        /* ===== BACK BUTTON ===== */
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--wood-light);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            background: var(--cream);
            border: 1px solid var(--input-border);
        }

        .back-link:hover {
            color: var(--wood-dark);
            background: var(--wood-bg);
            transform: translateX(-3px);
        }

        .back-link i {
            margin-right: 0.5rem;
            color: var(--accent-gold);
        }

        /* ===== ORDER SUMMARY ===== */
        .order-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            padding: 1.5rem;
            background: var(--cream);
            border-radius: 0.8rem;
            margin: 0 2rem 2rem 2rem;
            border: 1px solid var(--input-border);
        }

        .order-summary .summary-item {
            text-align: center;
        }

        .order-summary .summary-item h4 {
            margin: 0 0 0.5rem 0;
            color: var(--wood-dark);
            font-size: 0.85rem;
        }

        .order-summary .summary-item h4 i {
            color: var(--accent-gold);
            margin-right: 0.4rem;
        }

        .order-summary .summary-item p {
            margin: 0;
            color: var(--wood-light);
            font-size: 0.95rem;
        }

        .order-summary .summary-item p strong {
            color: var(--wood-dark);
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

        /* ===== TOTAL ROW ===== */
        .total-row {
            background-color: var(--cream);
            font-size: 1.1rem;
            border-top: 2px solid var(--accent-gold);
        }

        .total-row td {
            font-weight: 700;
            padding: 1rem 1.2rem;
        }

        .total-row .total-label {
            text-align: right;
            color: var(--wood-dark);
        }

        .total-row .total-amount {
            text-align: right;
            color: var(--wood-dark);
            font-size: 1.2rem;
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

        /* ===== SECTION TITLE ===== */
        .section-title {
            padding: 0 2rem;
            margin-bottom: 1rem;
            color: var(--wood-dark);
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
        }

        .section-title i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--wood-dark);
            color: rgba(255, 255, 255, 0.7);
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

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .order-summary {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                margin: 0 1rem 1.5rem 1rem;
                padding: 1rem;
            }

            .order-summary .summary-item {
                text-align: left;
                display: flex;
                justify-content: space-between;
                padding: 0.3rem 0;
                border-bottom: 1px solid var(--input-border);
            }

            .order-summary .summary-item:last-child {
                border-bottom: none;
            }

            .order-summary .summary-item h4 {
                margin: 0;
                font-size: 0.8rem;
            }

            .order-summary .summary-item p {
                font-size: 0.85rem;
            }

            .section-title {
                padding: 0 1rem;
                font-size: 1rem;
            }

            .table-container table {
                min-width: 500px;
            }

            .total-row td {
                padding: 0.6rem 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .back-link {
                font-size: 0.85rem;
                padding: 0.3rem 0.8rem;
            }

            .card-header h2 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <h1><a href="dashboard.php"><i class="fas fa-tree"></i> Premium Living</a></h1>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="make-order.php"><i class="fas fa-shopping-cart"></i> Make Order</a></li>
        <li><a href="view-orders.php"><i class="fas fa-list"></i> Orders</a></li>
        <li><a href="update-profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <!-- Back Button -->
    <a href="view-orders.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-file-invoice"></i> Order #<?php echo $order_id; ?></h2>
            <p>Placed on: <strong><?php echo date('Y-m-d H:i', strtotime($orderMaster['odate'])); ?></strong></p>
        </div>

        <!-- ===== ORDER SUMMARY WITH CUSTOMER ID ===== -->
        <div class="order-summary">
            <div class="summary-item">
                <h4><i class="fas fa-user"></i> Customer ID</h4>
                <p><strong>#<?php echo $orderMaster['cid']; ?></strong></p>
            </div>
            <div class="summary-item">
                <h4><i class="fas fa-truck"></i> Shipping Destination</h4>
                <p><?php echo htmlspecialchars($orderMaster['odeliveraddress']); ?></p>
            </div>
            <div class="summary-item">
                <h4><i class="fas fa-calendar-alt"></i> Delivery Date</h4>
                <p><strong><?php echo date('Y-m-d', strtotime($orderMaster['odeliverydate'])); ?></strong></p>
                <p style="font-size: 0.8rem; color: var(--gray-wood); margin-top: 0.2rem;">
                    <i class="fas fa-info-circle"></i> Status:
                    <span style="font-weight: 600; color: var(--wood-dark);"><?php echo htmlspecialchars($orderMaster['status']); ?></span>
                </p>
            </div>
        </div>

        <!-- ===== ORDER ITEMS TABLE ===== -->
        <div class="section-title">
            <i class="fas fa-cube"></i> Handcrafted Furniture Breakdown
        </div>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th style="text-align: left;">Product Name</th>
                    <th style="text-align: center;">Unit Price</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($basket_items)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <h3>No Items Found</h3>
                                <p>No items found for this order record.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($basket_items as $product): ?>
                        <tr>
                            <td style="text-align: left;">
                                <strong><?php echo htmlspecialchars($product['fname']); ?></strong>
                                <div style="font-size: 0.8rem; color: var(--gray-wood); margin-top: 2px;"><?php echo htmlspecialchars($product['fdesc']); ?></div>
                            </td>
                            <td style="text-align: center;">$<?php echo number_format($product['fprice'], 2); ?></td>
                            <td style="text-align: center;"><strong><?php echo $product['oqty']; ?></strong></td>
                            <td style="text-align: right; color: var(--accent-gold); font-weight: bold;">$<?php echo number_format($product['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" class="total-label">Total Order Cost:</td>
                        <td class="total-amount">$<?php echo number_format($orderMaster['ototalamount'], 2); ?></td>
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