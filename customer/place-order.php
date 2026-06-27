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

// ===== USE conn.php =====
require_once '../conn.php';

$message = "";
$message_type = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id > 0 && $quantity > 0) {
        try {
            // Get product price
            $price_stmt = $pdo->prepare("SELECT fprice FROM furnitures WHERE fid = ?");
            $price_stmt->execute([$product_id]);
            $product = $price_stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $total_amount = $product['fprice'] * $quantity;
                $delivery_date = date('Y-m-d', strtotime('+7 days'));

                // Get customer's delivery address
                $addr_stmt = $pdo->prepare("SELECT caddr FROM customers WHERE cid = ?");
                $addr_stmt->execute([$user_id]);
                $customer = $addr_stmt->fetch(PDO::FETCH_ASSOC);
                $delivery_address = $customer['caddr'] ?? 'Please update your address';

                // Start transaction
                $pdo->beginTransaction();

                // Insert into orders
                $stmt = $pdo->prepare("INSERT INTO orders (cid, ototalamount, odeliverydate, odeliveraddress, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->execute([$user_id, $total_amount, $delivery_date, $delivery_address]);
                $oid = $pdo->lastInsertId();

                // Insert into orderfurnitures
                $stmt2 = $pdo->prepare("INSERT INTO orderfurnitures (oid, fid, oqty) VALUES (?, ?, ?)");
                $stmt2->execute([$oid, $product_id, $quantity]);

                $pdo->commit();

                $message = "Order placed successfully! Order #$oid has been created.";
                $message_type = "alert-success";

                // Redirect to view orders after 2 seconds
                echo "<script>setTimeout(() => { window.location.href = 'view-orders.php'; }, 2000);</script>";
            } else {
                $message = "Product not found.";
                $message_type = "alert-warning";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error placing order: " . $e->getMessage();
            $message_type = "alert-danger";
        }
    } else {
        $message = "Invalid product or quantity.";
        $message_type = "alert-warning";
    }
} else {
    // If accessed directly without POST, redirect to make-order
    header("Location: make-order.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Premium Living</title>
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
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
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
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .card-header {
            padding: 1.5rem 2rem;
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
            padding: 2.5rem 2rem;
            text-align: center;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
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

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 0.7rem 2rem;
            border: none;
            border-radius: var(--radius-btn);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
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
            background: var(--gray-wood);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--wood-light);
            transform: translateY(-2px);
        }

        /* ===== STATUS ICON ===== */
        .status-icon {
            font-size: 4rem;
            color: var(--accent-gold);
            margin-bottom: 1rem;
        }

        .status-icon.success {
            color: #2d6a4f;
        }

        .status-icon.error {
            color: #cc0000;
        }

        .status-icon.warning {
            color: #b06000;
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

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .card-body {
                padding: 1.5rem 1rem;
            }

            .status-icon {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.85rem;
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
        <li><a href="make-order.php" class="active"><i class="fas fa-shopping-cart"></i> Make Order</a></li>
        <li><a href="view-orders.php"><i class="fas fa-list"></i> Orders</a></li>
        <li><a href="update-profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-shopping-cart"></i> Order Status</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <?php if ($message_type === 'alert-success'): ?>
                    <div class="status-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                <?php elseif ($message_type === 'alert-danger'): ?>
                    <div class="status-icon error">
                        <i class="fas fa-times-circle"></i>
                    </div>
                <?php else: ?>
                    <div class="status-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                <?php endif; ?>

                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-info-circle"></i> <?php echo $message; ?>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="view-orders.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View My Orders
                    </a>
                    <a href="make-order.php" class="btn btn-secondary">
                        <i class="fas fa-shopping-cart"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="status-icon warning">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <p style="color: var(--gray-wood);">Processing your order...</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>

</body>
</html>