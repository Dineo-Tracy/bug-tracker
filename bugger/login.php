<?php
session_start();
require 'config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Valid credentials
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Invalid username or password</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - 🐞Bug Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #fff; }
    .form-container {
      max-width: 400px;
      margin: 80px auto;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      background-color: #fdfdfd;
    }
    .btn-pink {
      background-color: #4A90E2;
      color: white;
    }
    .btn-pink:hover {
      background-color: #4A90E2;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="form-container">
    <h2 class="text-center mb-4" style="color:#4A90E2;">Login to Your Account</h2>
    <?= $message ?>
    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" required class="form-control" />
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" required class="form-control" />
      </div>
      <button type="submit" class="btn btn-pink w-100">Login</button>
    </form>
    <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register</a></p>
  </div>
</div>

</body>
</html>
