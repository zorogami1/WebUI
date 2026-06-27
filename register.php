<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'conn.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Gather input from form
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $role = isset($_POST['role']) ? $_POST['role'] : 'customer';

    // Customer-only fields
    $delivery_address = ($role === 'customer') ? trim($_POST['delivery_address']) : null;
    $date_of_birth = ($role === 'customer') ? trim($_POST['date_of_birth']) : null;

    // Validation Checks
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($phone)) {
        $error_message = "Please fill out all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($role === 'customer' && empty($delivery_address)) {
        $error_message = "Delivery address is required for customers.";
    } else {
        // Secure Password Hashing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            if ($role === 'customer') {
                // ===== INSERT INTO CUSTOMERS TABLE =====
                // Check if customer already exists
                $check_sql = "SELECT cid FROM customers WHERE cname = :full_name OR ctel = :phone";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([':full_name' => $full_name, ':phone' => $phone]);

                if ($check_stmt->rowCount() > 0) {
                    $error_message = "User with this name or phone already exists.";
                } else {
                    // Insert into customers table
                    $sql = "INSERT INTO customers (cname, cpassword, ctel, caddr) 
                            VALUES (:full_name, :password, :phone, :delivery_address)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                            ':full_name' => $full_name,
                            ':password' => $hashed_password,
                            ':phone' => $phone,
                            ':delivery_address' => $delivery_address
                    ]);

                    $success_message = "Registration successful!";

                    // Auto-login after registration
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = 'customer';

                    // Redirect to customer dashboard
                    echo "<script>setTimeout(() => { window.location.href = 'customer/dashboard.php'; }, 1500);</script>";
                }
            } else {
                // ===== INSERT INTO STAFFS TABLE =====
                // Check if staff already exists
                $check_sql = "SELECT sid FROM staffs WHERE sname = :full_name OR semail = :email";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([':full_name' => $full_name, ':email' => $email]);

                if ($check_stmt->rowCount() > 0) {
                    $error_message = "Staff with this name or email already exists.";
                } else {
                    // Insert into staffs table - using same fields
                    $sql = "INSERT INTO staffs (sname, semail, spassword) 
                            VALUES (:full_name, :email, :password)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                            ':full_name' => $full_name,
                            ':email' => $email,
                            ':password' => $hashed_password
                    ]);

                    $success_message = "Staff registration successful!";

                    // Auto-login after registration
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = 'staff';

                    // Redirect to staff dashboard
                    echo "<script>setTimeout(() => { window.location.href = 'staff/dashboard.php'; }, 1500);</script>";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== COMPLETE REGISTER STYLES ===== */
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

        .register-container {
            background: var(--cream);
            border-radius: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 580px;
            padding: 2.5rem;
            box-sizing: border-box;
            max-height: 90vh;
            overflow-y: auto;
        }

        .register-container::-webkit-scrollbar {
            width: 6px;
        }

        .register-container::-webkit-scrollbar-track {
            background: var(--wood-bg);
            border-radius: 10px;
        }

        .register-container::-webkit-scrollbar-thumb {
            background: var(--wood-light);
            border-radius: 10px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .register-header .icon {
            font-size: 2.5rem;
            color: var(--wood-light);
            margin-bottom: 0.3rem;
        }

        .register-header h2 {
            color: var(--wood-dark);
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
        }

        .register-header p {
            color: var(--wood-light);
            font-size: 0.9rem;
        }

        /* ===== ROLE RADIO BUTTONS ===== */
        .role-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: var(--wood-bg);
            padding: 0.8rem;
            border-radius: 1rem;
            border: 2px solid rgba(139, 94, 60, 0.15);
        }

        .role-option {
            flex: 1;
            position: relative;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .role-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.8rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            background: white;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--wood-light);
            text-align: center;
        }

        .role-option label i {
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
            color: var(--wood-light);
            transition: all 0.3s;
        }

        .role-option label .role-badge {
            font-size: 0.65rem;
            font-weight: 400;
            color: var(--gray-wood);
            margin-top: 0.2rem;
        }

        .role-option input[type="radio"]:checked + label {
            border-color: var(--accent-gold);
            background: white;
            box-shadow: 0 4px 15px rgba(212, 163, 115, 0.2);
        }

        .role-option input[type="radio"]:checked + label i {
            color: var(--accent-gold);
        }

        .role-option input[type="radio"]:checked + label {
            color: var(--wood-dark);
        }

        .role-option input[type="radio"]:checked + label .role-badge {
            color: var(--wood-light);
        }

        .role-option label:hover {
            border-color: var(--accent-gold);
            transform: translateY(-2px);
        }

        /* ===== FORM ===== */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
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

        .form-group input,
        .form-group textarea,
        .form-group select {
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

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
            border-radius: 0.8rem;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15);
        }

        .form-group .helper-text {
            font-size: 0.75rem;
            color: var(--gray-wood);
            margin-top: 0.2rem;
        }

        /* ===== CONDITIONAL FIELDS ===== */
        .conditional-fields {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .conditional-fields.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== SECTION DIVIDER ===== */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1.5rem 0 1rem;
            color: var(--wood-light);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--input-border);
        }

        .section-divider i {
            color: var(--accent-gold);
        }

        /* ===== BUTTONS ===== */
        .btn-register {
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

        .btn-register:hover {
            background: var(--wood-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(62, 42, 33, 0.3);
        }

        .btn-register:active {
            transform: scale(0.98);
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 0.7rem 1rem;
            border-radius: 0.8rem;
            margin-bottom: 1rem;
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 600px) {
            .register-container {
                padding: 1.5rem;
                border-radius: 1.5rem;
                max-height: 95vh;
            }

            .register-header h2 {
                font-size: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .role-selector {
                flex-direction: column;
                gap: 0.5rem;
            }

            .role-option label {
                flex-direction: row;
                gap: 0.8rem;
                padding: 0.6rem 1rem;
            }

            .role-option label i {
                margin-bottom: 0;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 400px) {
            .register-container {
                padding: 1rem;
            }

            .register-header h2 {
                font-size: 1.2rem;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 0.6rem 0.8rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-header">
        <div class="icon"><i class="fas fa-tree"></i></div>
        <h2>Create Your Account</h2>
        <p>Join the Premium Living family today</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="registerForm">

        <!-- ===== ROLE SELECTOR ===== -->
        <div class="role-selector">
            <div class="role-option">
                <input type="radio" id="role_customer" name="role" value="customer" checked onchange="toggleFields()">
                <label for="role_customer">
                    <i class="fas fa-user"></i>
                    <span>Customer</span>
                    <span class="role-badge">Shop & Track Orders</span>
                </label>
            </div>
            <div class="role-option">
                <input type="radio" id="role_staff" name="role" value="staff" onchange="toggleFields()">
                <label for="role_staff">
                    <i class="fas fa-user-tie"></i>
                    <span>Staff Member</span>
                    <span class="role-badge">Manage & Admin</span>
                </label>
            </div>
        </div>

        <!-- ===== PERSONAL DETAILS ===== -->
        <div class="section-divider">
            <i class="fas fa-user-circle"></i> Personal Information
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="full_name"><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
            </div>

            <div class="form-group" id="dob_group">
                <label for="date_of_birth"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                <div class="helper-text">Optional for customers</div>
            </div>
        </div>

        <!-- ===== PASSWORD ===== -->
        <div class="section-divider">
            <i class="fas fa-lock"></i> Security
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
                <div class="helper-text">Must be at least 6 characters</div>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
        </div>

        <!-- ===== CUSTOMER FIELDS ===== -->
        <div id="customer_fields" class="conditional-fields active">
            <div class="section-divider">
                <i class="fas fa-home"></i> Customer Details
            </div>

            <div class="form-group">
                <label for="delivery_address"><i class="fas fa-map-marker-alt"></i> Delivery Address <span class="required">*</span></label>
                <textarea id="delivery_address" name="delivery_address" placeholder="Enter your full delivery address" rows="2"><?php echo isset($_POST['delivery_address']) ? htmlspecialchars($_POST['delivery_address']) : ''; ?></textarea>
            </div>
        </div>

        <!-- ===== STAFF FIELDS ===== -->
        <div id="staff_fields" class="conditional-fields">
            <div class="section-divider">
                <i class="fas fa-briefcase"></i> Staff Details
            </div>

            <div class="form-group">
                <label><i class="fas fa-info-circle"></i> Staff Registration</label>
                <div style="padding: 0.7rem 1rem; background: var(--wood-bg); border-radius: 0.8rem; color: var(--wood-light); font-size: 0.9rem;">
                    <i class="fas fa-check-circle" style="color: var(--accent-gold);"></i>
                    You are registering as a staff member. Your email and phone will be used for staff records.
                </div>
            </div>
        </div>

        <!-- ===== SUBMIT ===== -->
        <button type="submit" class="btn-register">
            <i class="fas fa-user-plus"></i> Create Account
        </button>
    </form>

    <div class="form-footer">
        Already have an account? <a href="login.php">Log In Here</a>
    </div>
</div>

<script>
    function toggleFields() {
        const role = document.querySelector('input[name="role"]:checked').value;
        const customerFields = document.getElementById('customer_fields');
        const staffFields = document.getElementById('staff_fields');
        const dobGroup = document.getElementById('dob_group');

        if (role === 'customer') {
            customerFields.classList.add('active');
            staffFields.classList.remove('active');
            // Make customer fields required
            document.getElementById('delivery_address').required = true;
            document.getElementById('date_of_birth').required = false;
            // Show DOB group
            dobGroup.style.display = 'block';
        } else {
            customerFields.classList.remove('active');
            staffFields.classList.add('active');
            // Make customer fields not required
            document.getElementById('delivery_address').required = false;
            document.getElementById('date_of_birth').required = false;
            // Hide DOB group
            dobGroup.style.display = 'none';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>

</body>
</html>