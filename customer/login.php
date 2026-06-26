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
        $dbname = "createprojectdb"; // Matches your exact system database name

        // Establish connection
        $conn = new mysqli($servername, $db_username, $db_password, $dbname);

        if ($conn->connect_error) {
            $error_message = "Database connection failure: " . $conn->connect_error;
        } else {
            // Prepared statement to safely lookup the user by their name or email identifier
            // (Assuming full_name or email matches the $username input in your setup)
            $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE full_name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_num_rows === 0) {
                // FALLBACK MECHANISM FOR TESTING: If the user doesn't exist, automatically register them!
                // This keeps your testing flow fast while ensuring they get a REAL valid ID in the database.
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $conn->prepare("INSERT INTO users (full_name, password, contact_number, delivery_address, role) VALUES (?, ?, '94463393', 'hi', 'customer')");
                $insert_stmt->bind_param("ss", $username, $hashed_password);
                $insert_stmt->execute();

                // Get the real, freshly generated database auto-incremented ID
                $new_id = $insert_stmt->insert_id;

                $_SESSION['user_id'] = $new_id;
                $_SESSION['full_name'] = $username;
                $_SESSION['role'] = "customer";

                $insert_stmt->close();
                header("Location: dashboard.php");
                exit();
            } else {
                // If user is found, grab their actual row array properties
                $row = $result->fetch_assoc();

                // Verify the password security hash
                if (password_verify($password, $row['password'])) {
                    // DYNAMIC ASSIGNMENT: Pulling the exact real ID from the database row record
                    $_SESSION['user_id'] = intval($row['id']);
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['role'] = $row['role'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid password. Please try again.";
                }
            }
            $stmt->close();
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

    <style>
        /* FULL CUSTOM STYLING TO FIX LAYOUT DISCREPANCIES */
        :root {
            --bg-cream: #fdfbf7;
            --card-brown: #5c3d2e;
            --text-dark: #3e2a21;
            --accent-gold: #d4a373;
            --input-border: #d4c4a8;
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-cream);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Layout matching reference */
        .navbar {
            background-color: var(--card-brown);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar .logo h1 a {
            color: #fff;
            text-decoration: none;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar .nav-links {
            list-style: none;
        }

        .navbar .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .navbar .nav-links a:hover {
            color: var(--accent-gold);
        }

        /* Container Main Section Alignment */
        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        /* Login Card Frame Structure */
        .card {
            background: #fff;
            width: 100%;
            max-width: 460px;
            padding: 2.5rem 2rem;
            border-radius: var(--radius);
            box-shadow: 0 10px 30px rgba(92, 61, 46, 0.08);
            border: 1px solid rgba(212, 163, 115, 0.15);
        }

        .card-header h2 {
            font-size: 1.8rem;
            color: var(--card-brown);
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Input Form Elements Group Styles */
        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .input-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 8px;
            font-size: 1rem;
            color: var(--text-dark);
            background-color: #fff;
            outline: none;
            transition: all 0.2s ease-in-out;
        }

        .input-group input:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 4px rgba(212, 163, 115, 0.15);
        }

        /* Submit Interactive Button Framework */
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background-color: var(--card-brown);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            margin-top: 0.5rem;
        }

        .btn-primary:hover {
            background-color: #4a3125;
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        /* Standalone Error styling */
        .error-alert {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Footer Element */
        footer {
            background-color: var(--card-brown);
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 1.5rem;
            font-size: 0.9rem;
            margin-top: auto;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <h1><a href="../index.php"><i class="fas fa-tree"></i> Premium Living</a></h1>
    </div>
    <ul class="nav-links">
        <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-sign-in-alt"></i> Portal Login</h2>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-alert"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-group">
                <label for="username">Username / Email</label>
                <input type="text" id="username" name="username" required placeholder="Enter your email address">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2026 Premium Living | <i class="fas fa-tree"></i> Woodcraft Excellence</p>
</footer>

</body>
</html>