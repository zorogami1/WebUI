<?php
// 1. Initialize server session tracking at the absolute top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = "";

// 2. Intercept form submission dynamically
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Database credentials configuration
        $servername = "localhost";
        $db_username = "root";
        $db_password = "";
        $dbname = "createprojectdb";

        // Establish connection
        $conn = new mysqli($servername, $db_username, $db_password, $dbname);

        if ($conn->connect_error) {
            $error_message = "Database connection failure: " . $conn->connect_error;
        } else {
            $user_found = false;
            $user_data = null;
            $role = '';

            // First, check if user is a CUSTOMER
            $stmt = $conn->prepare("SELECT cid as id, cname as full_name, cpassword as password, 'customer' as role FROM customers WHERE cname = ? OR ctel = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                $user_found = true;
                $role = 'customer';
            }
            $stmt->close();

            // If not found as customer, check if user is STAFF
            if (!$user_found) {
                $stmt = $conn->prepare("SELECT sid as id, sname as full_name, spassword as password, 'staff' as role FROM staffs WHERE sname = ? OR semail = ?");
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user_data = $result->fetch_assoc();
                    $user_found = true;
                    $role = 'staff';
                }
                $stmt->close();
            }

            // If user not found in either table
            if (!$user_found) {
                $error_message = "No account found with this username or email. Please register first.";
            } else {
                // ===== FIXED: Support BOTH hashed and plain text passwords =====
                $password_valid = false;

                // First try: password_verify() for hashed passwords
                if (password_verify($password, $user_data['password'])) {
                    $password_valid = true;
                }
                // Second try: Plain text comparison for old passwords
                elseif ($password === $user_data['password']) {
                    // If plain text password matches, re-hash it for security
                    $password_valid = true;

                    // Update the password to hashed version
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    if ($role === 'customer') {
                        $update_stmt = $conn->prepare("UPDATE customers SET cpassword = ? WHERE cid = ?");
                    } else {
                        $update_stmt = $conn->prepare("UPDATE staffs SET spassword = ? WHERE sid = ?");
                    }
                    $update_stmt->bind_param("si", $hashed_password, $user_data['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                if ($password_valid) {
                    // Store session data
                    $_SESSION['user_id'] = intval($user_data['id']);
                    $_SESSION['full_name'] = $user_data['full_name'];
                    $_SESSION['role'] = $role;

                    // ===== ROLE-BASED REDIRECT =====
                    if ($role === 'staff') {
                        header("Location: staff/dashboard.php");
                    } else {
                        header("Location: customer/dashboard.php");
                    }
                    exit();
                } else {
                    $error_message = "Invalid password. Please try again.";
                }
            }
            $conn->close();
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== COMPLETE LOGIN STYLES ===== */
        :root {
            --wood-dark: #3e2a21;
            --wood-medium: #5c3d2e;
            --wood-light: #8b5e3c;
            --wood-bg: #f5efe6;
            --cream: #fdf8f0;
            --accent-gold: #d4a373;
            --input-border: #d4c4a8;
            --gray-wood: #a89f91;
            --radius-card: 1.25rem;
            --radius-btn: 2rem;
            --shadow-soft: 0 8px 30px rgba(0,0,0,0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #2b1d16 0%, #4a3222 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        .login-container {
            background: var(--cream);
            border-radius: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            box-sizing: border-box;
            position: relative;
        }

        /* ===== HOME BUTTON ===== */
        .home-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: var(--wood-light);
            font-size: 1.2rem;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 0.8rem;
            background: rgba(139, 94, 60, 0.08);
        }

        .home-btn:hover {
            color: var(--wood-dark);
            background: rgba(139, 94, 60, 0.15);
            transform: translateY(-2px);
        }

        .home-btn i {
            font-size: 1rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            margin-top: 0.5rem;
        }

        .login-header .icon {
            font-size: 2.5rem;
            color: var(--wood-light);
            margin-bottom: 0.3rem;
        }

        .login-header h2 {
            color: var(--wood-dark);
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
        }

        .login-header p {
            color: var(--wood-light);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--wood-dark);
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .form-group label i {
            color: var(--accent-gold);
            margin-right: 0.4rem;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid var(--input-border);
            border-radius: 0.8rem;
            background: #ffffff;
            color: var(--wood-dark);
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15);
        }

        .form-group .helper-text {
            font-size: 0.75rem;
            color: var(--gray-wood);
            margin-top: 0.2rem;
        }

        .btn-login {
            width: 100%;
            background: var(--wood-medium);
            color: #ffffff;
            border: none;
            padding: 0.9rem;
            border-radius: var(--radius-btn);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            background: var(--wood-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(62, 42, 33, 0.3);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 0.7rem 1rem;
            border-radius: 0.8rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        .alert-danger {
            background: #fde8e8;
            color: #9d6b53;
            border: 1px solid #9d6b53;
        }

        .alert-success {
            background: #e6f4ea;
            color: #2d6a4f;
            border: 1px solid #2d6a4f;
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--wood-light);
        }

        .form-footer a {
            color: var(--wood-dark);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-footer a:hover {
            color: var(--accent-gold);
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
            color: var(--wood-light);
            font-size: 0.8rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--input-border);
        }

        @media (max-width: 600px) {
            .login-container {
                padding: 1.5rem;
                border-radius: 1.5rem;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }

            .home-btn {
                top: 0.8rem;
                left: 0.8rem;
                font-size: 1rem;
                padding: 0.3rem 0.6rem;
            }

            .home-btn span {
                display: none;
            }
        }

        @media (max-width: 400px) {
            .login-container {
                padding: 1rem;
            }

            .login-header h2 {
                font-size: 1.2rem;
            }

            .form-group input {
                padding: 0.6rem 0.8rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- ===== HOME BUTTON ===== -->
    <a href="index.php" class="home-btn" title="Back to Home">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>

    <div class="login-header">
        <div class="icon"><i class="fas fa-tree"></i></div>
        <h2>Welcome Back</h2>
        <p>Sign in to your Premium Living account</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Username / Email <span class="required">*</span></label>
            <input type="text" id="username" name="username" placeholder="Enter your full name or email" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <div class="helper-text">Minimum 6 characters</div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="divider">
        <span>New here?</span>
    </div>

    <div class="form-footer">
        Don't have an account? <a href="register.php">Create Account →</a>
    </div>
</div>

</body>
</html>