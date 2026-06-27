<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== FIXED: Check for user_id instead of sid =====
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ===== FIXED: Verify user is staff =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../customer/dashboard.php");
    exit();
}

// ===== FIXED: Correct path to conn.php =====
require_once '../conn.php';

$full_name = $_SESSION['full_name'] ?? 'Staff Member';
$total_furniture = 0;
$total_materials = 0;
$pending_orders = 0;
$total_customers = 0;
$total_revenue = 0;

try {
    // ===== FIXED: Correct table names (lowercase) =====
    $total_furniture = $pdo->query("SELECT COUNT(*) FROM furnitures")->fetchColumn();
    $total_materials = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

    // ===== FIXED: Check if orders table exists =====
    $order_check = $pdo->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    if (!empty($order_check)) {
        $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) = 'pending' OR LOWER(status) = 'open' OR LOWER(status) = 'processing'")->fetchColumn();
        $total_revenue = $pdo->query("SELECT SUM(ototalamount) FROM orders WHERE status != 'Cancelled'")->fetchColumn() ?: 0;
    }
} catch (PDOException $e) {
    $db_warning = "Database synchronization tracking notice: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Premium Living Furniture</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== COMPLETE STAFF DASHBOARD STYLES ===== */
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

        /* ===== WELCOME BANNER ===== */
        .welcome-banner {
            background: linear-gradient(135deg, #8b5e3c, #6d442b);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-card);
            margin-bottom: 2rem;
        }

        .welcome-banner h2 {
            font-size: 1.8rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.3rem;
        }

        .welcome-banner p {
            color: rgba(255,255,255,0.85);
            font-size: 1rem;
        }

        .welcome-banner i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        /* ===== STATS GRID ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dash-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            border-left: 5px solid var(--accent-gold);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }

        .dash-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-warm);
        }

        .dash-card i {
            font-size: 2.5rem;
            color: var(--accent-gold);
        }

        .dash-metric {
            font-size: 2rem;
            font-weight: 700;
            color: var(--wood-dark);
        }

        .dash-label {
            color: var(--gray-wood);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
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

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: var(--radius-btn);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
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

        .btn-secondary {
            background: var(--wood-light);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--wood-medium);
            transform: translateY(-2px);
        }

        .btn-success {
            background: #2a9d8f;
            color: white;
        }

        .btn-success:hover {
            background: #21867a;
            transform: translateY(-2px);
        }

        /* ===== ALERT ===== */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1.5rem 2rem;
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

            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .dash-metric {
                font-size: 1.5rem;
            }

            .welcome-banner {
                padding: 1.5rem;
            }

            .welcome-banner h2 {
                font-size: 1.4rem;
            }

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .quick-actions {
                padding: 1rem;
                flex-direction: column;
            }

            .quick-actions .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

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
        <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="insert-furniture.php"><i class="fas fa-plus-circle"></i> Insert Furniture</a></li>
        <li><a href="insert-material.php"><i class="fas fa-warehouse"></i> Insert Material</a></li>
        <li><a href="manage-orders.php"><i class="fas fa-clipboard-list"></i> Manage Orders</a></li>
        <li><a href="generate-report.php"><i class="fas fa-file-alt"></i> Generate Report</a></li>
        <li><a href="delete-furniture.php"><i class="fas fa-trash"></i> Delete Furniture</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="welcome-banner">
        <h2><i class="fas fa-user-tie"></i> Welcome Back, <?php echo htmlspecialchars($full_name); ?>!</h2>
        <p>Premium Living Internal Management System Platform Dashboard</p>
    </div>

    <?php if (isset($db_warning)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($db_warning); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="dash-card" style="border-left-color: var(--accent-gold);">
            <div>
                <div class="dash-metric"><?php echo $total_furniture; ?></div>
                <div class="dash-label">Catalog Products</div>
            </div>
            <i class="fas fa-chair"></i>
        </div>
        <div class="dash-card" style="border-left-color: #2a9d8f;">
            <div>
                <div class="dash-metric"><?php echo $total_materials; ?></div>
                <div class="dash-label">Material Types</div>
            </div>
            <i class="fas fa-boxes"></i>
        </div>
        <div class="dash-card" style="border-left-color: #e76f51;">
            <div>
                <div class="dash-metric"><?php echo $pending_orders; ?></div>
                <div class="dash-label">Active Orders</div>
            </div>
            <i class="fas fa-truck"></i>
        </div>
        <div class="dash-card" style="border-left-color: #2a9d8f;">
            <div>
                <div class="dash-metric"><?php echo $total_customers; ?></div>
                <div class="dash-label">Total Customers</div>
            </div>
            <i class="fas fa-users"></i>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-bolt"></i> Quick Actions Console</h2>
        </div>
        <div class="quick-actions">
            <a href="insert-furniture.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Furniture</a>
            <a href="insert-material.php" class="btn btn-secondary"><i class="fas fa-warehouse"></i> Stock Materials</a>
            <a href="manage-orders.php" class="btn btn-success"><i class="fas fa-clipboard-list"></i> Fulfill Orders</a>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>