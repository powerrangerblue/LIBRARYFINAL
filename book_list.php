<?php
session_start();
require 'connect_db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$studentName = $_SESSION['user']['name'];

$error = '';
$success = '';

// Fetch all books ordered by title
$result = $conn->query("SELECT * FROM books ORDER BY title ASC");
$books = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Handle borrow form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_submit'])) {
    $borrowerName = trim($_POST['borrower_name'] ?? '');
    $bookName = $_POST['book_name'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $agree = isset($_POST['agree']);

    if ($borrowerName === '') {
        $error = "Borrower's Name is required.";
    } elseif ($bookName === '') {
        $error = "Please select a Book Name.";
    } elseif (!in_array($duration, ['1_week', '2_weeks', '1_month'])) {
        $error = "Please select a borrowing duration.";
    } elseif (!$agree) {
        $error = "You must agree to the library rules before submitting.";
    } else {
        // Check book availability by title
        $stmt = $conn->prepare("SELECT id, copies FROM books WHERE title = ?");
        $stmt->bind_param("s", $bookName);
        $stmt->execute();
        $res = $stmt->get_result();
        $book = $res->fetch_assoc();

        if (!$book) {
            $error = "Selected book not found.";
        } elseif ($book['copies'] <= 0) {
            $error = "Sorry, the selected book is currently not available.";
        } else {
            // Calculate borrow and due dates
            $borrowDate = date('Y-m-d');

            switch ($duration) {
                case '1_week':
                    $dueDate = date('Y-m-d', strtotime('+1 week'));
                    break;
                case '2_weeks':
                    $dueDate = date('Y-m-d', strtotime('+2 weeks'));
                    break;
                case '1_month':
                    $dueDate = date('Y-m-d', strtotime('+1 month'));
                    break;
                default:
                    $dueDate = null;
                    break;
            }

            if ($dueDate === null) {
                $error = "Invalid borrowing duration.";
            } else {
                // Insert borrow record
                $insert = $conn->prepare("INSERT INTO borrowed_books (student_name, book_title, borrow_date, due_date) VALUES (?, ?, ?, ?)");
                $insert->bind_param("ssss", $borrowerName, $bookName, $borrowDate, $dueDate);

                if ($insert->execute()) {
                    // Decrease available copies by 1
                    $new_available = $book['copies'] - 1;
                    $update = $conn->prepare("UPDATE books SET copies = ? WHERE id = ?");
                    $update->bind_param("ii", $new_available, $book['id']);
                    $update->execute();

                    $success = "You have successfully borrowed '{$bookName}'. Due date is {$dueDate}.";

                    // Refresh books data
                    $result = $conn->query("SELECT * FROM books ORDER BY title ASC");
                    $books = $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    $error = "Failed to record borrowing. Please try again.";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Book List & Borrow Form - LibraryHub</title>
<link rel="stylesheet" href="style.css" />
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0; padding: 20px;
  }
  .container {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
  }
  h1 {
    text-align: center;
    margin-bottom: 1rem;
  }
  .back-btn {
    text-decoration: none;
    color: #444;
    display: inline-block;
    margin-bottom: 1rem;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1.5rem;
  }
  th, td {
    border: 1px solid #ccc;
    padding: 8px 12px;
    text-align: left;
  }
  th {
    background-color: #eee;
  }
  .available {
    color: green;
    font-weight: bold;
  }
  .not-available {
    color: red;
    font-weight: bold;
  }
  .borrow-form label {
    display: block;
    margin: 0.8rem 0 0.3rem;
  }
  .borrow-form input[type="text"], 
  .borrow-form select {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
  }
  .duration-options label {
    margin-right: 15px;
  }
  .agree-container {
    margin-top: 1rem;
  }
  .submit-btn {
    margin-top: 1rem;
    padding: 10px 20px;
    background-color: #0077cc;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  .submit-btn:hover {
    background-color: #005fa3;
  }
  .message {
    margin: 1rem 0;
    padding: 10px;
    border-radius: 4px;
  }
  .message.error {
    background-color: #f8d7da;
    color: #721c24;
  }
  .message.success {
    background-color: #d4edda;
    color: #155724;
  }
  /* Responsive */
  @media (max-width: 600px) {
    .container {
      padding: 10px;
    }
    th, td {
      font-size: 14px;
      padding: 6px 8px;
    }
  }
</style>
</head>
<body>
  <div class="container">

    <a href="borrowed_books.php" class="back-btn">← Back to My Borrowed Books</a>

    <h1>📚 Borrow a Book</h1>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form class="borrow-form" method="POST" novalidate>
      <label for="borrower_name">Borrower’s Name:</label>
      <input type="text" id="borrower_name" name="borrower_name" required
             value="<?= htmlspecialchars($studentName) ?>" />

      <label for="book_name">Book Name:</label>
   <select id="book_name" name="book_name" required>
  <option value="" disabled <?= !isset($_POST['book_name']) ? 'selected' : '' ?>>Select a book</option>
  <?php foreach ($books as $book): ?>
    <?php
      $title = htmlspecialchars($book['title']);
      $copies = isset($book['copies']) ? (int)$book['copies'] : 0;
    ?>
    <?php if ($copies > 0): ?>
      <option value="<?= $title ?>" <?= (isset($_POST['book_name']) && $_POST['book_name'] === $book['title']) ? 'selected' : '' ?>>
        <?= $title ?> (<?= $copies ?> copies available)
      </option>
    <?php endif; ?>
  <?php endforeach; ?>
</select>



      <label>Borrowing Duration:</label>
      <div class="duration-options">
        <label><input type="radio" name="duration" value="1_week" required
          <?= (isset($_POST['duration']) && $_POST['duration'] === '1_week') ? 'checked' : '' ?>> 1 week</label>
        <label><input type="radio" name="duration" value="2_weeks"
          <?= (isset($_POST['duration']) && $_POST['duration'] === '2_weeks') ? 'checked' : '' ?>> 2 weeks</label>
        <label><input type="radio" name="duration" value="1_month"
          <?= (isset($_POST['duration']) && $_POST['duration'] === '1_month') ? 'checked' : '' ?>> 1 month</label>
      </div>

      <div class="agree-container">
        <label><input type="checkbox" name="agree" required
          <?= isset($_POST['agree']) ? 'checked' : '' ?>> I agree to the library rules</label>
      </div>

      <button class="submit-btn" type="submit" name="borrow_submit">Submit</button>
    </form>

    <h1>📖 Book List</h1>
    <p>Welcome, <strong><?= htmlspecialchars($studentName) ?></strong>. Browse and borrow available books below.</p>

    <table>
      <thead>
        <tr>
          <th>Title</th><th>Author</th><th>Genre</th><th>ISBN</th><th>Year</th><th>Available Copies</th><th>Availability</th>
        </tr>
      </thead>
     <tbody>
 <?php foreach ($books as $book): ?>
  <?php
    $title = htmlspecialchars($book['title'] ?? 'N/A');
    $author = htmlspecialchars($book['author'] ?? 'N/A');
    $genre = htmlspecialchars($book['genre'] ?? 'N/A');
    $isbn = htmlspecialchars($book['isbn'] ?? 'N/A');
    $year = isset($book['publication_year']) ? htmlspecialchars($book['publication_year']) : 'N/A';
    $copies = isset($book['copies']) ? (int)$book['copies'] : 0;
  ?>
  <tr>
    <td><?= $title ?></td>
    <td><?= $author ?></td>
    <td><?= $genre ?></td>
    <td><?= $isbn ?></td>
    <td><?= $year ?></td>
    <td><?= $copies ?></td>
    <td class="<?= $copies > 0 ? 'available' : 'not-available' ?>">
      <?= $copies > 0 ? 'Available' : 'Not Available' ?>
    </td>
  </tr>
<?php endforeach; ?>

</tbody>

    </table>

  </div>
</body>
</html>
