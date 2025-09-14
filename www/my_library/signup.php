<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Check if username already exists
    $check = $conn->prepare("SELECT * FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Username already taken!";
    } else {
        // Hash password before saving
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $hashedPassword);

        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            $error = "Error registering user!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: radial-gradient(circle at top left, #f0fbe8, #dff0d8);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .form-box {
      background: #8fd44a;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.25);
      width: 380px;
      text-align: center;
    }
    .form-box h2 {
      margin-bottom: 25px;
      color: #fff;
      font-size: 26px;
      font-weight: bold;
    }
    .form-box label {
      display: block;
      text-align: left;
      margin-bottom: 6px;
      font-weight: bold;
      color: #fff;
    }
    .form-box input,
    .form-box button {
      width: 100%;
      padding: 12px;
      margin-bottom: 18px;
      border: none;
      border-radius: 25px;
      font-size: 14px;
      box-sizing: border-box;
    }
    .form-box input {
      background: #ffecec;
    }
    .form-box button {
      background: #c4951a;
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    .form-box button:hover {
      background: #a37412;
    }
    .error {
      color: red;
      margin-top: 10px;
      font-size: 14px;
    }
    .login-link {
      margin-top: 15px;
      font-size: 14px;
      color: #fff;
    }
    .login-link a {
      color: #fff;
      font-weight: bold;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="form-box">
    <h2>Sign Up</h2>
    <form method="POST">
      <label for="username">Username:</label>
      <input type="text" name="username" placeholder="Enter Username" required>

      <label for="password">Password:</label>
      <input type="password" name="password" placeholder="Enter Password" required>

      <button type="submit">Sign Up</button>
    </form>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
</body>
</html>
