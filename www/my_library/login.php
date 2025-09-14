<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // ✅ Plain text password check
        if ($password === $row["password"]) {
            $_SESSION["username"] = $row["username"];
            $_SESSION["role"] = $row["role"]; // store role for access control

            // ✅ Redirect based on role
            if ($row["role"] === "admin") {
                header("Location: admin_dashboard.php");
            } elseif ($row["role"] === "borrower") {
                header("Location: borrower_dashboard.php");
            } else {
                header("Location: dashboard.php"); // fallback
            }
            exit();
        } else {
            $error = "Invalid Username or Password!";
        }
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
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
    .forgot {
      margin-top: -10px;
      margin-bottom: 15px;
      text-align: right;
      font-size: 13px;
    }
    .forgot a {
      color: purple;
      text-decoration: none;
    }
    .forgot a:hover {
      text-decoration: underline;
    }
    .error {
      color: red;
      margin-top: 10px;
      font-size: 14px;
    }
    .success {
      color: green;
      margin-top: 10px;
      font-size: 14px;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="form-box">
    <h2>Sign In</h2>

    <?php 
    if (isset($_GET["registered"]) && $_GET["registered"] == 1) {
        echo "<p class='success'>✅ Account created successfully! Please log in.</p>";
    }
    ?>

    <form method="POST">
      <label for="username">Username:</label>
      <input type="text" name="username" placeholder="Enter Username" required>

      <label for="password">Password:</label>
      <input type="password" name="password" placeholder="Enter Password" required>

      <div class="forgot">
        <a href="forgot_password.php">Forgot password?</a>
      </div>

      <button type="submit">Sign In</button>
    </form>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
  </div>
</body>
</html>
