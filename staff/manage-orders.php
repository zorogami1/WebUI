<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== FIXED: Check for user_id and staff role =====
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
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
        $msg = "<div class='alert alert-success'>✅ Order #{$oid} status updated to '{$new_status}' successfully!</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>⚠️ Failed to update order: " . $e->getMessage() . "</div>";
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
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .table-container td {
            padding: 0.8rem 1.2rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--wood-dark);
            font-size: 0.85rem;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background: rgba(212, 163, 115, 0.05);
        }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
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

        /* ===== ACTION FORM ===== */
        .action-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            margin: 0;
            flex-wrap: wrap;
        }

        .status-select {
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            border: 1.5px solid var(--input-border);
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
            background: white;
            color: var(--wood-dark);
            outline: none;
            transition: all 0.3s;
            min-width: 110px;
        }

        .status-select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15);
        }

        .btn-update {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
            background: var(--wood-medium);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        .btn-update:hover {
            background: var(--wood-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(62, 42, 33, 0.3);
        }

        .btn-update i {
            margin: 0;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 0.8rem;
            margin: 1.5rem 2rem 0;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        .alert-success {
            background: #e6f4ea;
            color: #2d6a4f;
            border: 1px solid #2d6a4f;
        }

        .alert-danger {
            background: #fde8e8;
            color: #9d6b53;
            border: 1px solid #9d6b53;
        }

        .alert-warning {
            background: #fef3e2;
            color: #8a5a2a;
            border: 1px solid #e9b35f;
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

            .alert {
                margin: 1rem 1rem 0;
            }

            .action-form {
                flex-direction: column;
                gap: 0.3rem;
            }

            .status-select {
                width: 100%;
                min-width: unset;
            }

            .btn-update {
                width: 100%;
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
        <h1><a href="dashboard.php"><i class="fas fa-tree"></i> Staff Portal</a></h1>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="insert-furniture.php"><i class="fas fa-plus-circle"></i> Insert Furniture</a></li>
        <li><a href="insert-material.php"><i class="fas fa-warehouse"></i> Insert Material</a></li>
        <li><a href="manage-orders.php" class="active"><i class="fas fa-clipboard-list"></i> Manage Orders</a></li>
        <li><a href="generate-report.php"><i class="fas fa-file-alt"></i> Generate Report</a></li>
        <li><a href="delete-furniture.php"><i class="fas fa-trash"></i> Delete Furniture</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Orders Found</h3>
                                <p>No customer orders found in the system.</p>
                            </div>
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
                            <td style="max-width: 150px; font-size: 0.8rem;">
                                <?php
                                $address = $row['odeliveraddress'] ?? '';
                                echo htmlspecialchars(substr($address, 0, 25)) . (strlen($address) > 25 ? '...' : '');
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