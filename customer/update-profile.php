<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Route Protection Guard
if (!isset($_SESSION['cid'])) {
    header("Location: login.php");
    exit();
}

require_once '../conn.php';
$customer_id = $_SESSION['cid'];

$success_msg = "";
$error_msg = "";

// Handle Profile Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname = trim($_POST['cname'] ?? '');
    $ctel = trim($_POST['contact_number'] ?? '');
    $caddr = trim($_POST['delivery_address'] ?? '');
    $ccompany = trim($_POST['company_name'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($cname) || empty($ctel) || empty($caddr)) {
        $error_msg = "Fields marked with an asterisk (*) are required.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } else {
        try {
            // Start building update query
            if (!empty($new_password)) {
                // Update profile details AND password
                $sql = "UPDATE Customers SET cname = :cname, ctel = :ctel, caddr = :caddr, ccompany = :ccompany, cpassword = :cpassword WHERE cid = :cid";
                $params = [
                        'cname'     => $cname,
                        'ctel'      => $ctel,
                        'caddr'     => $caddr,
                        'ccompany'  => !empty($ccompany) ? $ccompany : null,
                        'cpassword' => $new_password,
                        'cid'       => $customer_id
                ];
            } else {
                // Update profile details only
                $sql = "UPDATE Customers SET cname = :cname, ctel = :ctel, caddr = :caddr, ccompany = :ccompany WHERE cid = :cid";
                $params = [
                        'cname'     => $cname,
                        'ctel'      => $ctel,
                        'caddr'     => $caddr,
                        'ccompany'  => !empty($ccompany) ? $ccompany : null,
                        'cid'       => $customer_id
                ];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Sync the updated name to the active session tracker
            $_SESSION['cname'] = $cname;
            $success_msg = "Your profile information has been successfully updated!";
        } catch (PDOException $e) {
            $error_msg = "Profile update failed: " . $e->getMessage();
        }
    }
}

// Fetch current customer data to pre-fill the form inputs
try {
    $stmt = $pdo->prepare("SELECT * FROM Customers WHERE cid = :cid");
    $stmt->execute(['cid' => $customer_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        die("Error: Customer record profile could not be verified.");
    }
} catch (PDOException $e) {
    die("Database fetch error: " . $e->getMessage());
}
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
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Premium Living</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="make-order.php">Make Order</a></li>
        <li><a href="view-orders.php">Orders</a></li>
        <li><a href="update-profile.php" class="active">Profile</a></li>
        <li><a href="delete-order.php">Delete Order</a></li>
        <li><a href="../index.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header">
            <h2><i class="fas fa-user-edit"></i> Account Profile Settings</h2>
            <p>Keep your contact details, password, and physical delivery destinations updated.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div style="background-color: #fce4e4; color: #cc0000; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #cc0000;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div style="background-color: #e6f4ea; color: #137333; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #137333;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <form action="update-profile.php" method="POST">
            <div class="form-group">
                <label>Customer Account ID</label>
                <input type="text" value="<?php echo $profile['cid']; ?>" disabled style="background-color: #eee; cursor: not-allowed; font-weight: bold; color: #555;">
                <small style="color: #888;">Your system identification number cannot be changed.</small>
            </div>

            <div class="form-group">
                <label for="cname">Full Name *</label>
                <input type="text" id="cname" name="cname" required value="<?php echo htmlspecialchars($profile['cname']); ?>">
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number *</label>
                <input type="tel" id="contact_number" name="contact_number" required value="<?php echo htmlspecialchars($profile['ctel']); ?>">
            </div>

            <div class="form-group">
                <label for="delivery_address">Primary Delivery Address *</label>
                <textarea id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars($profile['caddr']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="company_name">Company Name (Optional)</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($profile['ccompany'] ?? ''); ?>">
            </div>

            <hr style="border: 0; border-top: 1px solid #ddd; margin: 2rem 0;">
            <h3 style="color: #3e2a21; margin-bottom: 1rem;"><i class="fas fa-key"></i> Change Password (Optional)</h3>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living Furniture | Sustainably Crafted</p>
</footer>
</body>
</html>