<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../conn.php';

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid_input = trim($_POST['sid'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($sid_input) && !empty($password)) {
        try {
            // Check Staff system records (sid is an automated identifier integer)
            $stmt = $pdo->prepare("SELECT * FROM Staffs WHERE sid = :sid");
            $stmt->execute(['sid' => $sid_input]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($staff && $password === $staff['spassword']) {
                session_regenerate_id(true);
                $_SESSION['sid'] = $staff['sid'];
                $_SESSION['sname'] = $staff['sname'];
                $_SESSION['srole'] = $staff['srole'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error_msg = "Invalid Staff ID credentials or password.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database lookup structural failure: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in all authorization inputs.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--wood-light);"></i>
            <h1>Staff Portal</h1>
            <p>Authorized administration access only</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div style="background-color: #fce4e4; color: #cc0000; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; border-left: 4px solid #cc0000;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label><i class="fas fa-id-badge"></i> Staff ID Number</label>
                <input type="text" name="sid" required placeholder="e.g., 1" value="<?php echo isset($_POST['sid']) ? htmlspecialchars($_POST['sid']) : ''; ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-key"></i> Password</label>
                <input type="password" name="password" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-sign-in-alt"></i> Access Dashboard</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem;"><a href="../customer/login.php" style="color: var(--wood-light); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Return to Customer Site</a></p>
    </div>
</div>
</body>
</html>