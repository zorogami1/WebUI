<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../conn.php';

$error_msg = "";
$success_msg = "";
$new_cid = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname = trim($_POST['cname'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $ctel = trim($_POST['contact_number'] ?? '');
    $caddr = trim($_POST['delivery_address'] ?? '');
    $ccompany = trim($_POST['company_name'] ?? '');

    if (empty($cname) || empty($password) || empty($ctel) || empty($caddr)) {
        $error_msg = "All fields marked with an asterisk (*) are required.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        try {
            // Check if name already taken to prevent overlapping data names
            $checkStmt = $pdo->prepare("SELECT cid FROM Customers WHERE cname = :cname");
            $checkStmt->execute(['cname' => $cname]);
            if ($checkStmt->fetch()) {
                $error_msg = "This name is already registered. Please choose an alternative variant or login.";
            } else {
                // Proceed with Insert
                $sql = "INSERT INTO Customers (cname, cpassword, ctel, caddr, ccompany) VALUES (:cname, :cpassword, :ctel, :caddr, :ccompany)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'cname'     => $cname,
                    'cpassword' => $password, // Plain-text matching assignment script conventions
                    'ctel'      => $ctel,
                    'caddr'     => $caddr,
                    'ccompany'  => !empty($ccompany) ? $ccompany : null
                ]);

                $new_cid = $pdo->lastInsertId();
                $success_msg = "Account created successfully!";
            }
        } catch (PDOException $e) {
            $error_msg = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Premium Living Family | Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body { background-color: #2b1d16; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 2rem 0; }
        .register-container { background-color: #fdfaf4; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 100%; max-width: 550px; padding: 2.5rem; box-sizing: border-box; }
        .register-header { text-align: center; margin-bottom: 2rem; color: #3e2a21; }
        .register-header i { font-size: 2.5rem; color: #8b5e3c; margin-bottom: 0.5rem; }
        .btn-register { background-color: #8b5e3c; color: white; border: none; padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 600; border-radius: 8px; cursor: pointer; width: 100%; transition: background 0.2s; margin-top: 1rem; }
        .btn-register:hover { background-color: #724c30; }
        .form-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #666; }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <i class="fas fa-user-plus"></i>
        <h1>Create Your Account</h1>
        <p>Join Premium Living Artisanal Furniture System</p>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div style="background-color: #fce4e4; color: #cc0000; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border-left: 4px solid #cc0000;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div style="background-color: #e6f4ea; color: #137333; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 1rem; text-align: center; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle" style="font-size: 2.5rem; margin-bottom: 0.5rem; color: #1b5e20;"></i>
            <h3>Welcome to Premium Living!</h3>
            <p><?php echo htmlspecialchars($success_msg); ?></p>
            <div style="background: white; padding: 1rem; border-radius: 6px; margin: 1rem 0; font-weight: bold; border: 1px dashed #8b5e3c;">
                Your unique Customer ID is: <span style="font-size: 1.4rem; color: #8b5e3c;"><?php echo $new_cid; ?></span>
            </div>
            <p style="font-size: 0.85rem; color: #555;">Please write down this ID number. Use either this ID or your full name to log into the system.</p>
            <a href="login.php" class="btn btn-primary" style="display:inline-block; margin-top:1rem; text-decoration:none; padding:0.6rem 2rem;">Proceed to Login</a>
        </div>
    <?php else: ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="cname">Full Name *</label>
                <input type="text" id="cname" name="cname" placeholder="John Doe" required value="<?php echo isset($_POST['cname']) ? htmlspecialchars($_POST['cname']) : ''; ?>">
            </div>
            <div class="form-row" style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                </div>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number *</label>
                <input type="tel" id="contact_number" name="contact_number" placeholder="e.g., +852 9876 5432" required value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="delivery_address">Delivery Address *</label>
                <textarea id="delivery_address" name="delivery_address" placeholder="Your full structural shipping address" rows="3" required><?php echo isset($_POST['delivery_address']) ? htmlspecialchars($_POST['delivery_address']) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="company_name">Company Name (Optional)</label>
                <input type="text" id="company_name" name="company_name" placeholder="Company name if applicable" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
            </div>
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="form-footer">
            Already have an account? <a href="login.php" style="color: #8b5e3c; font-weight: bold;">Log In Here</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>