<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "createprojectdb";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        $message = "Database connection failure: " . $conn->connect_error;
        $message_type = "alert-warning";
    } else {
        $fid = intval($_POST['fid']);
        $oqty = intval($_POST['quantity'] ?? 1);

        // Get product price
        $price_stmt = $conn->prepare("SELECT fprice FROM furnitures WHERE fid = ?");
        $price_stmt->bind_param("i", $fid);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        $product = $price_result->fetch_assoc();
        $price_stmt->close();

        if ($product) {
            $total_amount = $product['fprice'] * $oqty;
            $delivery_date = date('Y-m-d', strtotime('+7 days'));

            // Get customer's delivery address
            $addr_stmt = $conn->prepare("SELECT caddr FROM customers WHERE cid = ?");
            $addr_stmt->bind_param("i", $user_id);
            $addr_stmt->execute();
            $addr_result = $addr_stmt->get_result();
            $customer = $addr_result->fetch_assoc();
            $addr_stmt->close();

            $delivery_address = $customer['caddr'] ?? 'Please update your address';

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert into orders
                $stmt = $conn->prepare("INSERT INTO orders (cid, ototalamount, odeliverydate, odeliveraddress, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("idss", $user_id, $total_amount, $delivery_date, $delivery_address);
                $stmt->execute();
                $oid = $stmt->insert_id;
                $stmt->close();

                // Insert into orderfurnitures
                $stmt2 = $conn->prepare("INSERT INTO orderfurnitures (oid, fid, oqty) VALUES (?, ?, ?)");
                $stmt2->bind_param("iii", $oid, $fid, $oqty);
                $stmt2->execute();
                $stmt2->close();

                $conn->commit();

                $message = "Order placed successfully! Order #$oid has been created.";
                $message_type = "alert-success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error creating order: " . $e->getMessage();
                $message_type = "alert-warning";
            }
        } else {
            $message = "Product not found.";
            $message_type = "alert-warning";
        }
        $conn->close();
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
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-image .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 3rem;
            color: #d4a373;
            background: #f5efe6;
        }
        .product-image .no-image i {
            font-size: 4rem;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="dashboard.php">
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
        <li><a href="delete-order.php"><i class="fas fa-trash"></i> Delete Order</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-cart-plus"></i> Our Handcrafted Collection</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="product-grid">
            <?php
            $servername = "localhost";
            $db_username = "root";
            $db_password = "";
            $dbname = "createprojectdb";
            $conn = new mysqli($servername, $db_username, $db_password, $dbname);

            $products = $conn->query("SELECT fid, fname, fdesc, fprice, fimage FROM furnitures ORDER BY fid ASC");

            // Default images for fallback (PNG files in images folder)
            $default_images = [
                    1 => "1.png",
                    2 => "2.png",
                    3 => "3.png",
                    4 => "4.png",
                    5 => "5.png",
                    6 => "6.png"
            ];

            while ($p = $products->fetch_assoc()):
                // Determine image path
                $image_path = "";
                $has_image = false;

                // Check if fimage exists and file exists
                if (!empty($p['fimage'])) {
                    // Check in uploads folder (for staff uploaded images)
                    $upload_path = "../" . $p['fimage'];
                    if (file_exists($upload_path)) {
                        $image_path = $upload_path;
                        $has_image = true;
                    }
                }

                // If no image found, check default images folder
                if (!$has_image && isset($default_images[$p['fid']])) {
                    $default_path = "../images/" . $default_images[$p['fid']];
                    if (file_exists($default_path)) {
                        $image_path = $default_path;
                        $has_image = true;
                    }
                }
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if ($has_image): ?>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $p['fname']; ?>">
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
                        <form action="" method="POST" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="place_order">
                            <input type="hidden" name="fid" value="<?php echo $p['fid']; ?>">
                            <input type="number" name="quantity" value="1" min="1" max="10" style="width: 60px; padding: 0.4rem; border: 1.5px solid #e2d5c0; border-radius: 0.5rem; text-align: center;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-cart-plus"></i> Buy Now</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php $conn->close(); ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>
</body>
</html>