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
$full_name = $_SESSION['full_name'] ?? 'Valued Customer';

// ===== FIXED: Properly include conn.php and check if $pdo exists =====
require_once '../conn.php';

// Check if $pdo is defined
if (!isset($pdo)) {
    die("Database connection failed. Please check your configuration.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $fid = intval($_POST['fid']);
    $oqty = intval($_POST['quantity'] ?? 1);

    try {
        // Get product price
        $price_stmt = $pdo->prepare("SELECT fprice FROM furnitures WHERE fid = ?");
        $price_stmt->execute([$fid]);
        $product = $price_stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $total_amount = $product['fprice'] * $oqty;
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
            $stmt2->execute([$oid, $fid, $oqty]);

            $pdo->commit();

            $message = "Order placed successfully! Order #$oid has been created.";
            $message_type = "alert-success";

            // Redirect after 2 seconds
            echo "<script>setTimeout(() => { window.location.href = 'view-orders.php'; }, 2000);</script>";
        } else {
            $message = "Product not found.";
            $message_type = "alert-warning";
        }
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $message = "Error creating order: " . $e->getMessage();
        $message_type = "alert-warning";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Collection - Premium Living</title>
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

        /* ===== PRODUCT GRID ===== */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }

        .product-card {
            background: white;
            border-radius: var(--radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
            border: 1px solid rgba(139,94,60,0.08);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-warm);
        }

        /* ===== FIXED IMAGE HEIGHT ===== */
        .product-image {
            background: linear-gradient(145deg, #e8dccc, #d4c4a8);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 220px;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-image .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 3rem;
            color: var(--accent-gold);
            background: var(--wood-bg);
        }

        .product-image .no-image i {
            font-size: 4rem;
            opacity: 0.5;
        }

        /* ===== FLEXIBLE PRODUCT INFO LAYOUT ===== */
        .product-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--wood-dark);
            margin-bottom: 0.3rem;
            font-family: 'Playfair Display', serif;
            line-height: 1.3;
        }

        .product-price {
            color: var(--accent-gold);
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.3rem 0;
            flex-shrink: 0;
        }

        .product-desc {
            color: var(--wood-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        /* ===== FIXED META AND BUTTON AREA ===== */
        .product-footer {
            margin-top: auto;
            border-top: 1px solid rgba(139,94,60,0.1);
            padding-top: 0.8rem;
            flex-shrink: 0;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--gray-wood);
            margin-bottom: 0.8rem;
        }

        .product-meta i {
            color: var(--accent-gold);
            margin-right: 0.3rem;
        }

        /* ===== FIXED ORDER FORM ===== */
        .order-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .order-form .qty-wrapper {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--input-border);
            border-radius: 0.5rem;
            overflow: hidden;
            background: white;
            flex-shrink: 0;
        }

        .order-form .qty-wrapper button {
            background: var(--wood-bg);
            border: none;
            padding: 0.4rem 0.8rem;
            cursor: pointer;
            font-size: 1rem;
            color: var(--wood-dark);
            transition: all 0.2s;
            font-weight: 600;
            width: 32px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .order-form .qty-wrapper button:hover {
            background: var(--accent-gold);
            color: white;
        }

        .order-form .qty-wrapper input[type="number"] {
            width: 45px;
            padding: 0.4rem;
            border: none;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: var(--wood-dark);
            outline: none;
            height: 38px;
            -moz-appearance: textfield;
        }

        .order-form .qty-wrapper input[type="number"]::-webkit-inner-spin-button,
        .order-form .qty-wrapper input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .order-form .btn {
            flex: 1;
            min-width: 0;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            white-space: nowrap;
        }

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

            .product-grid {
                grid-template-columns: 1fr 1fr;
                padding: 1rem;
                gap: 1rem;
            }

            .product-image {
                height: 160px;
            }

            .product-title {
                font-size: 1rem;
            }

            .product-price {
                font-size: 1.2rem;
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

            .order-form .btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem;
            }

            .order-form .qty-wrapper input[type="number"] {
                width: 35px;
            }

            .order-form .qty-wrapper button {
                width: 28px;
                height: 32px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .product-image {
                height: 200px;
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
            <h2><i class="fas fa-cart-plus"></i> Our Handcrafted Collection</h2>
            <p>Browse our premium furniture collection and place your order</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="product-grid">
            <?php
            try {
                // Check if $pdo exists before using it
                if (!isset($pdo)) {
                    throw new Exception("Database connection not available");
                }

                $products = $pdo->query("SELECT fid, fname, fdesc, fprice, fimage FROM furnitures ORDER BY fid ASC");

                while ($p = $products->fetch(PDO::FETCH_ASSOC)):
                    $image_path = "";
                    $has_image = false;

                    // Check if fimage exists and file exists
                    if (!empty($p['fimage'])) {
                        $upload_path = "../" . $p['fimage'];
                        if (file_exists($upload_path)) {
                            $image_path = $upload_path;
                            $has_image = true;
                        }
                    }

                    // If no image found, check default images folder
                    if (!$has_image) {
                        $default_paths = [
                                "../images/" . $p['fid'] . ".png",
                                "../images/" . $p['fid'] . ".jpg",
                                "../images/" . $p['fid'] . ".webp"
                        ];
                        foreach ($default_paths as $path) {
                            if (file_exists($path)) {
                                $image_path = $path;
                                $has_image = true;
                                break;
                            }
                        }
                    }
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($has_image): ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo $p['fname']; ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-chair"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars($p['fname']); ?></div>
                            <div class="product-price">$<?php echo number_format($p['fprice'], 2); ?></div>
                            <div class="product-desc"><?php echo htmlspecialchars($p['fdesc']); ?></div>
                            <div class="product-footer">
                                <div class="product-meta">
                                    <span><i class="fas fa-tag"></i> SKU: #<?php echo $p['fid']; ?></span>
                                </div>
                                <form action="" method="POST" class="order-form" onsubmit="return validateQuantity(this)">
                                    <input type="hidden" name="action" value="place_order">
                                    <input type="hidden" name="fid" value="<?php echo $p['fid']; ?>">
                                    <div class="qty-wrapper">
                                        <button type="button" class="qty-btn" data-action="minus">−</button>
                                        <input type="number" name="quantity" class="qty-input" value="1" min="1" max="10">
                                        <button type="button" class="qty-btn" data-action="plus">+</button>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-cart-plus"></i> Buy Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            } catch (Exception $e) {
                echo "<div class='alert alert-warning'>Error loading products: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>

<script>
    // ===== FIXED: Quantity button functionality =====
    document.querySelectorAll('.qty-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('.qty-input');
        const max = parseInt(input.getAttribute('max')) || 10;
        const min = parseInt(input.getAttribute('min')) || 1;

        // Minus button
        const minusBtn = wrapper.querySelector('[data-action="minus"]');
        if (minusBtn) {
            minusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                let currentValue = parseInt(input.value) || min;
                if (currentValue > min) {
                    input.value = currentValue - 1;
                }
            });
        }

        // Plus button
        const plusBtn = wrapper.querySelector('[data-action="plus"]');
        if (plusBtn) {
            plusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                let currentValue = parseInt(input.value) || min;
                if (currentValue < max) {
                    input.value = currentValue + 1;
                }
            });
        }

        // Ensure value stays within bounds when user types
        input.addEventListener('change', function() {
            let value = parseInt(this.value) || min;
            if (value < min) this.value = min;
            if (value > max) this.value = max;
        });
    });

    // Validate quantity before form submission
    function validateQuantity(form) {
        const input = form.querySelector('.qty-input');
        const value = parseInt(input.value) || 1;
        const max = parseInt(input.getAttribute('max')) || 10;
        const min = parseInt(input.getAttribute('min')) || 1;

        if (value < min || value > max) {
            alert('Please enter a quantity between ' + min + ' and ' + max + '.');
            return false;
        }
        return true;
    }
</script>

</body>
</html>