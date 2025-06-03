<?php
session_start();
require 'connect_db.php'; // Make sure your DB connection works

// Registration
if (isset($_POST['signUp'])) {
    $email = strtolower(trim($_POST['email']));
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $username, $password, $role);
        if ($stmt->execute()) {
            $_SESSION['user'] = [
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'name' => $username  // Use username here
            ];
            header("Location: " . ($role === 'student' ? "borrowed_books.php" : "add_books.php"));
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

// Login
if (isset($_POST['signIn'])) {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT password, role, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($hashedPassword, $userRole, $username);

    if ($stmt->fetch() && password_verify($password, $hashedPassword) && $userRole === $role) {
        $_SESSION['user'] = [
            'email' => $email,
            'username' => $username,
            'role' => $role,
            'name' => $username  // Use username here
        ];
        header("Location: " . ($role === 'student' ? "borrowed_books.php" : "add_books.php"));
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Library Login & Register</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
  />
  <style>
    /* Your existing styles here, unchanged */
    @import url("https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap");

    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: "Josefin Sans", sans-serif;
      background-color: #fae3d9;
      color: #000;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .container {
      background-color: #f9f4f1;
      border-radius: 15px;
      box-shadow: 0 0 25px rgb(0 0 0 / 0.1);
      padding: 40px 40px 30px;
      width: 100%;
      max-width: 400px;
    }

    .form-title {
      font-size: 2.4rem;
      text-align: center;
      margin-bottom: 1.2rem;
      font-weight: 600;
      color: #ddbea9;
      user-select: none;
    }

    .input-group {
      display: flex;
      align-items: center;
      margin-bottom: 1.4rem;
      border-bottom: 1.5px solid #ddbea9;
      padding-bottom: 6px;
      transition: 0.4s ease-in-out;
    }

    .input-group i {
      font-size: 1.6rem;
      margin-right: 10px;
      color: #ddbea9;
      user-select: none;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"],
    select {
      font-family: "Josefin Sans", sans-serif;
      font-size: 1.3rem;
      border: none;
      outline: none;
      width: 100%;
      background: transparent;
      color: #000;
      padding: 8px 0;
      user-select: text;
    }

    input::placeholder {
      color: #ddbea9;
      font-weight: 300;
    }

    select {
      cursor: pointer;
    }

    input[type="submit"].btn {
      width: 100%;
      padding: 10px;
      background-color: #ddbea9;
      border-radius: 10px;
      font-size: 1.4rem;
      font-weight: 600;
      border: none;
      color: #000;
      cursor: pointer;
      user-select: none;
      transition: 0.3s ease-in-out;
    }

    input[type="submit"].btn:hover {
      background-color: #d4a373;
    }

    .links {
      margin-top: 2rem;
      text-align: center;
      user-select: none;
    }

    .links p {
      margin-bottom: 8px;
      color: #ddbea9;
    }

    .links button {
      font-family: "Josefin Sans", sans-serif;
      font-size: 1.2rem;
      font-weight: 500;
      background: transparent;
      border: none;
      color: #d4a373;
      cursor: pointer;
      user-select: none;
    }

    .links button:hover {
      color: #ddbea9;
    }

    .recover {
      text-align: right;
      margin-bottom: 1.6rem;
    }

    .recover a {
      text-decoration: none;
      color: #ddbea9;
      font-weight: 500;
    }

    .recover a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <?php if (isset($error)): ?>
    <div style="color:red; text-align:center; margin-bottom:1rem;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Register Form -->
  <div class="container" id="signup" style="display:none;">
    <h1 class="form-title">Register</h1>
    <form method="post" action="login.php">
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="username" placeholder="Username" required minlength="3" maxlength="30" />
      </div>
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required />
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
          minlength="6"
        />
      </div>
      <div class="input-group">
        <i class="fas fa-user-tag"></i>
        <select name="role" required>
          <option value="">Select User Type</option>
          <option value="student">Student</option>
          <option value="librarian">Librarian</option>
        </select>
      </div>
      <input type="submit" class="btn" value="Sign Up" name="signUp" />
    </form>

    <div class="links">
      <p>Already have an account?</p>
      <button id="signInButton">Sign In</button>
    </div>
  </div>

  <!-- Login Form -->
  <div class="container" id="signIn">
    <h1 class="form-title">Sign In</h1>
    <form method="post" action="login.php">
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required />
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
          minlength="6"
        />
      </div>
      <div class="input-group">
        <i class="fas fa-user-tag"></i>
        <select name="role" required>
          <option value="">Select User Type</option>
          <option value="student">Student</option>
          <option value="librarian">Librarian</option>
        </select>
      </div>
      <p class="recover"><a href="#">Recover Password</a></p>
      <input type="submit" class="btn" value="Sign In" name="signIn" />
    </form>

    <div class="links">
      <p>Don't have an account?</p>
      <button id="signUpButton">Register Here</button>
    </div>
  </div>

  <script>
    const signUpBtn = document.getElementById("signUpButton");
    const signInBtn = document.getElementById("signInButton");
    const signUpForm = document.getElementById("signup");
    const signInForm = document.getElementById("signIn");

    signUpBtn.addEventListener("click", () => {
      signUpForm.style.display = "block";
      signInForm.style.display = "none";
    });

    signInBtn.addEventListener("click", () => {
      signInForm.style.display = "block";
      signUpForm.style.display = "none";
    });
  </script>
</body>
</html>
