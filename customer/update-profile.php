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

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "createprojectdb";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Process Profile Form Updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $contact_number = trim($_POST['contact_number']);
    $delivery_address = trim($_POST['delivery_address']);

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact_number = ?, delivery_address = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $contact_number, $delivery_address, $user_id);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $message = "Profile saved details successfully!";
        $message_type = "alert-success";
    } else {
        $message = "Failed to update record details.";
        $message_type = "alert-warning";
    }
    $stmt->close();
}

// Read current fields from Database row
$stmt = $conn->prepare("SELECT full_name, contact_number, delivery_address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Premium Living</title>
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
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header">
            <h2><i class="fas fa-user-cog"></i> Profile Settings</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="delivery_address">Delivery Address</label>
                <textarea id="delivery_address" name="delivery_address" rows="4" required><?php echo htmlspecialchars($user['delivery_address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i class="fas fa-save"></i> Update Profile Details</button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | Woodcraft Excellence</p>
</footer>
</body>
</html>