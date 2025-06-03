<?php
// Enable error reporting for debugging (remove on production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'connect_db.php';

// Redirect if user not logged in or not a student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$studentName = $_SESSION['user']['name'];

// Fetch borrowed books for this student
$stmt = $conn->prepare("SELECT book_title, due_date FROM borrowed_books WHERE student_name = ?");
$stmt->bind_param("s", $studentName);
$stmt->execute();
$result = $stmt->get_result();
$borrowedBooks = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Account - LibraryHub</title>
<link rel="stylesheet" href="style.css" />
<style>
  .borrowed-list {
    margin-top: 1rem;
    border-collapse: collapse;
    width: 100%;
  }
  .borrowed-list th, .borrowed-list td {
    border: 1px solid #ddd;
    padding: 8px;
  }
  .borrowed-list th {
    background-color: #f2f2f2;
    text-align: left;
  }
  .no-data {
    margin-top: 1rem;
    color: #888;
  }
  .container {
    max-width: 800px;
    margin: auto;
    padding: 2rem;
    font-family: Georgia, serif;
  }
  .header h1 {
    margin-bottom: 0.5rem;
  }
  .logout-link {
    float: right;
    font-size: 0.9rem;
  }
  .logout-link a {
    color: #8b0000;
    text-decoration: none;
  }
  .nav-link {
    margin-top: 1rem;
    display: inline-block;
  }
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>👤 My Account</h1>
      <p>Welcome, <strong><?= htmlspecialchars($studentName) ?></strong></p>
    
      <p>Books you have currently borrowed and their due dates:</p>
    </div>

    <?php if (empty($borrowedBooks)): ?>
      <div class="no-data">You currently have no borrowed books.</div>
    <?php else: ?>
      <table class="borrowed-list">
        <thead>
          <tr>
            <th>Book Title</th>
            <th>Due Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrowedBooks as $book): ?>
            <tr>
              <td><?= htmlspecialchars($book['book_title']) ?></td>
              <td><?= htmlspecialchars($book['due_date']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <a href="book_list.php" class="nav-link">➡️ Borrow a New Book</a><br>
     <a href="logout.php">Logout</a>
  </div>
</body>
</html>
