<?php
session_start();
require 'connect_db.php'; // Include your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $publisher = trim($_POST['publisher']);
    $year = intval($_POST['year']);
    $genre = trim($_POST['genre']);
    $copies = intval($_POST['copies']);
    $shelf = trim($_POST['shelf']);
    $cover_image_name = null;

    // Handle file upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir);
        $cover_image_name = time() . '_' . basename($_FILES['cover_image']['name']);
        $target = $upload_dir . $cover_image_name;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $target);
    }

    $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, publisher, publication_year, genre, copies, shelf_location, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissss", $title, $author, $isbn, $publisher, $year, $genre, $copies, $shelf, $cover_image_name);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }

    header("Location: add_books.php");
    exit;
}
