<?php
require 'config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        $message = "<div class='alert alert-danger'>Username already taken!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        if ($stmt->execute([$username, $password])) {
            $message = "<div class='alert alert-success'>Registered successfully. <a href='login.php'>Login here</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Registration failed.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register -🐞Bug Tracker</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background-color: #fff; }
    .form-container {
      max-width: 500px;
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
    <h2 class="text-center mb-4" style="color:#4A90E2;">Create Account</h2>
    <?= $message ?>
    <form method="POST">
      <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" required class="form-control">
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" required class="form-control">
      </div>
      <button type="submit" class="btn btn-pink w-100">Register</button>
    </form>
    <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
  </div>
</div>

</body>
</html>
