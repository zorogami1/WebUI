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
        $user_id = $_SESSION['user_id'];
        $product_id = intval($_POST['product_id']);

        $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("ii", $user_id, $product_id);

        if ($stmt->execute()) {
            $message = "Order placed successfully! Track its status on your dashboard.";
            $message_type = "alert-success";
        } else {
            $message = "Error creating order record: " . $stmt->error;
            $message_type = "alert-warning";
        }

        $stmt->close();
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
            $products = [
                    1 => ["title" => "Oak Dining Chair", "price" => "$450", "desc" => "Solid oak, timeless elegance", "img" => "1.png"],
                    2 => ["title" => "Large Dining Table", "price" => "$2,500", "desc" => "Seats 6, modern design", "img" => "2.png"],
                    3 => ["title" => "3-Seater Fabric Sofa", "price" => "$3,800", "desc" => "Premium comfort", "img" => "3.png"],
                    4 => ["title" => "Wooden Wardrobe", "price" => "$1,800", "desc" => "Spacious, elegant storage", "img" => "4.png"],
                    5 => ["title" => "Industrial Bookshelf", "price" => "$1,200", "desc" => "Modern steel frame", "img" => "5.png"],
                    6 => ["title" => "Queen Size Bed Frame", "price" => "$2,200", "desc" => "Sturdy, timeless design", "img" => "6.png"]
            ];

            foreach ($products as $id => $p):
                ?>
                <div class="product-card">
                    <div class="product-image"><img src="../images/<?php echo $p['img']; ?>" alt="<?php echo $p['title']; ?>"></div>
                    <div class="product-info">
                        <div class="product-title"><?php echo $p['title']; ?></div>
                        <div class="product-price"><?php echo $p['price']; ?></div>
                        <div class="product-desc"><?php echo $p['desc']; ?></div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="place_order">
                            <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-cart-plus"></i> Buy Now</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>
</body>
</html>