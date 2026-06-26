<?php
// Start session at the absolute top of the page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../conn.php';

$error_msg = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($login_input) && !empty($password)) {
        try {
            // Check if input is a raw number (Customer ID)
            if (is_numeric($login_input)) {
                $stmt = $pdo->prepare("SELECT * FROM Customers WHERE cid = :cid");
                $stmt->execute(['cid' => $login_input]);
            } else {
                // Fallback check matching name or criteria if your app uses email,
                // but standard schema uses cid. We'll search by exact Name matching as secondary.
                $stmt = $pdo->prepare("SELECT * FROM Customers WHERE cname = :cname");
                $stmt->execute(['cname' => $login_input]);
            }

            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            // Plain-text validation matching the createProjectDB.sql format
            if ($customer && $password === $customer['cpassword']) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['cid'] = $customer['cid'];
                $_SESSION['cname'] = $customer['cname'];

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error_msg = "Invalid Customer ID/Name or Password.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Premium Living</title>
    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css\">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-tree" style="font-size: 3rem; color: var(--wood-light);"></i>
            <h1>Premium Living</h1>
            <p>Welcome to our wooden sanctuary</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div style="background-color: #fce4e4; color: #cc0000; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; border-left: 4px solid #cc0000;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Customer ID or Name</label>
                <input type="text" name="login_id" required placeholder="Enter ID or Full Name" value="<?php echo isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : ''; ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-arrow-right"></i> Login</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem;">New customer? <a href="register.php" style="color: var(--wood-light); font-weight: bold;">Create an Account</a></p>
        <p style="text-align: center; margin-top: 0.5rem;"><a href="../staff/login.php" style="font-size: 0.85rem; color: #a18262;">Staff Portal Access <i class="fas fa-shield-alt"></i></a></p>
    </div>
</div>
</body>
</html>