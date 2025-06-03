<?php
session_start();
require 'connect_db.php';

// Ensure user is logged in as student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Set default timezone (adjust if needed)
date_default_timezone_set('Asia/Manila');

// Get form inputs
$borrowerName = trim($_POST['borrowerName']);
$bookName = trim($_POST['bookName']);
$duration = $_POST['duration'];
$agreeRules = isset($_POST['agreeRules']) ? 1 : 0;

// Input validation
if (empty($borrowerName) || empty($bookName) || empty($duration) || !$agreeRules) {
    $_SESSION['error'] = "Please fill out all required fields and agree to the rules.";
    header("Location: book_borrowing.php");
    exit;
}

// Calculate due date accurately
$dueDate = new DateTime(); // today

switch ($duration) {
    case '1 week':
        $dueDate->modify('+7 days');
        break;
    case '2 weeks':
        $dueDate->modify('+14 days');
        break;
    case '1 month':
        $dueDate->modify('+30 days');
        break;
    default:
        $dueDate->modify('+7 days'); // fallback
}

$dueDateFormatted = $dueDate->format('Y-m-d');

// Insert into database
$stmt = $conn->prepare("INSERT INTO borrowed_books (student_name, book_title, due_date) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $borrowerName, $bookName, $dueDateFormatted);

if ($stmt->execute()) {
    $_SESSION['success'] = "Book borrowing recorded successfully!";
    header("Location: borrowed_books.php");
    exit;
} else {
    $_SESSION['error'] = "Error recording borrowing. Please try again.";
    header("Location: book_borrowing.php");
    exit;
}
?>
