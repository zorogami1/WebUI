<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "createprojectdb";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id > 0 && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);

        if ($stmt->execute()) {
            echo "<script>alert('Order placed successfully!'); window.location.href='view-orders.php';</script>";
        } else {
            echo "<script>alert('Error placing order: " . $stmt->error . "'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid product or quantity.'); window.history.back();</script>";
    }
}

$conn->close();
?>