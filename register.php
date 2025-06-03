<?php
session_start();
require 'connect_db.php'; // Assumes $conn is your mysqli connection

// Registration
if (isset($_POST['signUp'])) {
    $email = strtolower(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "Email already registered.";
        exit;
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password, $role);

    if ($stmt->execute()) {
        $_SESSION['user'] = [
            'email' => $email,
            'role' => $role
        ];
        header("Location: borrowed_books.php");
        exit;
    } else {
        echo "Registration failed. Try again.";
        exit;
    }
}

// Login
if (isset($_POST['signIn'])) {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($hashedPassword, $dbRole);

    if ($stmt->fetch() && password_verify($password, $hashedPassword) && $dbRole === $role) {
        $_SESSION['user'] = [
            'email' => $email,
            'role' => $role
        ];
        header("Location: " . ($role === 'student' ? "borrowed_books.php" : "librarian_dashboard.php"));
        exit;
    } else {
        echo "Invalid credentials.";
        exit;
    }
}
?>
