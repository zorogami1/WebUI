<?php
// Rule 1: Include the shared connection file
require_once 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Gather input from form attributes
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact_number = trim($_POST['contact_number']);
    $delivery_address = trim($_POST['delivery_address']);
    $company_name = !empty(trim($_POST['company_name'])) ? trim($_POST['company_name']) : null;

    // 2. Identify role (Defaults to customer unless sent from staff registration)
    $role = isset($_POST['role']) ? $_POST['role'] : 'customer';

    // 3. Validation Checks
    if (empty($full_name) || empty($password) || empty($confirm_password) || empty($contact_number) || empty($delivery_address)) {
        die("<script>alert('Please fill out all required fields.'); window.history.back();</script>");
    }

    if ($password !== $confirm_password) {
        die("<script>alert('Passwords do not match!'); window.history.back();</script>");
    }

    // 4. Secure Password Hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 5. Insert statement with PDO parameters
        $sql = "INSERT INTO users (full_name, password, contact_number, delivery_address, company_name, role) 
                VALUES (:full_name, :password, :contact_number, :delivery_address, :company_name, :role)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':password' => $hashed_password,
            ':contact_number' => $contact_number,
            ':delivery_address' => $delivery_address,
            ':company_name' => $company_name,
            ':role' => $role
        ]);

        // 6. Dynamic Redirect based on role back to their respective portal logins
        if ($role === 'staff') {
            echo "<script>alert('Staff registration successful!'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Registration successful!'); window.location.href='../WebUI/customer/login.html';</script>";
        }

    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}
?>