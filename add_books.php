<?php
session_start();

// Only allow librarians here
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'librarian') {
    header("Location: login.php");
    exit;
}

// Capture and clear any success or error messages from the session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Database connection settings - update accordingly
$host = 'localhost';
$db   = 'library_db';  // change this to your actual DB name
$user = 'root';        // change if needed
$pass = '';            // change if needed
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Query to get borrowed books info
    $stmt = $pdo->query("SELECT id, student_name, book_title, borrow_date, due_date, returned_date, status FROM borrowed_books ORDER BY due_date ASC");
    $borrowedBooks = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Function to calculate fines (e.g., $1 per day late)
function calculateFine($dueDate, $returnedDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    $returned = $returnedDate ? new DateTime($returnedDate) : $today;

    if ($returned <= $due) {
        return 0;
    }

    $interval = $returned->diff($due);
    return $interval->days; // $1 per day late
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Add Books - Librarian</title>
  <style>
    /* Styles trimmed for brevity - use your existing CSS here */
    /* Add styles for alerts */
    .container {
      max-width: 500px;
      margin: 40px auto;
      background: #fff;
      padding: 32px 28px 24px 28px;
      border-radius: 10px;
      box-shadow: 0 2px 16px rgba(0,0,0,0.08);
      font-family: Arial, sans-serif;
    }
    h1, h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #333;
    }
    label {
      display: block;
      margin-top: 1rem;
      margin-bottom: 0.4rem;
      font-weight: bold;
      color: #222;
    }
    input[type="text"],
    input[type="password"],
    input[type="number"],
    select {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 1rem;
      margin-bottom: 0.5rem;
      box-sizing: border-box;
    }
    input[type="file"] {
      margin-top: 0.5rem;
      margin-bottom: 1rem;
    }
    input[type="submit"] {
      width: 100%;
      background: #007bff;
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 6px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      margin-top: 1.2rem;
      transition: background 0.2s;
    }
    input[type="submit"]:hover {
      background: #0056b3;
    }
    .alert-success {
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 1rem;
      text-align: center;
    }
    .alert-error {
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 1rem;
      text-align: center;
    }
    /* Table styles */
    table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, sans-serif;
      margin-top: 30px;
    }
    table thead {
      background-color: #007bff;
      color: white;
    }
    table th, table td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }
    table tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    table tbody tr:hover {
      background-color: #f1f1f1;
    }
  </style>
</head>
<body>

  <div class="container">
    <h1>Add New Book</h1>

    <!-- Show session-based messages -->
    <?php if ($success): ?>
      <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="process_add_book.php" enctype="multipart/form-data">
      <label for="title">Book Title <span style="color:red">*</span>:</label>
      <input type="text" name="title" id="title" required />

      <label for="author">Author <span style="color:red">*</span>:</label>
      <input type="text" name="author" id="author" required />

      <label for="isbn">ISBN <span style="color:red">*</span>:</label>
      <input type="text" name="isbn" id="isbn" required />

      <label for="publisher">Publisher <span style="color:red">*</span>:</label>
      <input type="text" name="publisher" id="publisher" required />

      <label for="year">Publication Year <span style="color:red">*</span>:</label>
      <input type="number" name="year" id="year" min="1000" max="<?= date('Y') ?>" required />

      <label for="genre">Category / Genre <span style="color:red">*</span>:</label>
      <select name="genre" id="genre" required>
        <option value="" disabled selected>-- Select Genre --</option>
        <option value="Fiction">Fiction</option>
        <option value="Nonfiction">Nonfiction</option>
        <option value="Science">Science</option>
        <option value="History">History</option>
        <option value="Biography">Biography</option>
        <option value="Fantasy">Fantasy</option>
        <option value="Mystery">Mystery</option>
        <option value="Self-Help">Self-Help</option>
        <option value="Other">Other</option>
      </select>

      <label for="copies">Number of Copies <span style="color:red">*</span>:</label>
      <input type="number" name="copies" id="copies" min="1" value="1" required />

      <label for="shelf">Shelf Location <span style="color:red">*</span>:</label>
      <input type="text" name="shelf" id="shelf" placeholder="e.g., A3-12" required />

      <label for="cover_image">Book Cover Image (optional):</label>
      <input type="file" name="cover_image" id="cover_image" accept="image/*" />

      <input type="submit" value="Add Book" />
    </form>

    <form method="post" action="logout.php" style="margin-top: 1.5rem; text-align: center;">
      <input type="submit" value="Logout" style="background: #dc3545; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer;" />
    </form>
  </div>

  <!-- Borrowed Books Section -->
  <div class="container" style="max-width: 900px; margin-top: 50px;">
    <h2>Borrowed Books Overview</h2>

    <?php if (!empty($borrowedBooks)): ?>
      <table>
        <thead>
          <tr>
            <th>Book Title</th>
            <th>Borrower</th>
            <th>Borrow Date</th>
            <th>Due Date</th>
            <th>Returned Date</th>
            <th>Status</th>
            <th>Fine ($)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($borrowedBooks as $borrow): ?>
            <tr>
              <td><?= htmlspecialchars($borrow['book_title']) ?></td>
              <td><?= htmlspecialchars($borrow['student_name']) ?></td>
              <td><?= htmlspecialchars($borrow['borrow_date']) ?></td>
              <td><?= htmlspecialchars($borrow['due_date']) ?></td>
              <td><?= $borrow['returned_date'] ? htmlspecialchars($borrow['returned_date']) : '<em>Not returned</em>' ?></td>
              <td><?= htmlspecialchars(ucfirst($borrow['status'])) ?></td>
              <td style="color: red; font-weight: bold;">
                <?php
                  $fine = calculateFine($borrow['due_date'], $borrow['returned_date']);
                  echo $fine > 0 ? $fine : '-';
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No borrowed books records found.</p>
    <?php endif; ?>
  </div>

</body>
</html>
