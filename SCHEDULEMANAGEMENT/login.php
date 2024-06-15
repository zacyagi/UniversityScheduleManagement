<?php
session_start();
require 'db_connection.php'; // File containing database connection details

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Basic input validation
    if (empty($username) || empty($password)) {
        $error = "Username or Password is empty!";
    } else {
        // Query to check if the user exists and password matches
        $stmt = $conn->prepare("SELECT * FROM Employee WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['faculty_id'] = $user['faculty_id'];
            // Redirect based on role
            switch ($user['role_id']) {
                case 1:
                    header("Location: assistant_dashboard.php");
                    break;
                case 2:
                    header("Location: secretary_dashboard.php");
                    break;
                case 3:
                    header("Location: head_of_department_dashboard.php");
                    break;
                case 4:
                    header("Location: head_of_secretary_dashboard.php");
                    break;
                case 5:
                    header("Location: dean_dashboard.php");
                    break;
                default:
                    $error = "Invalid role!";
                    break;
            }
            exit();
        } else {
            $error = "Invalid Username or Password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Exam Planning System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .login-container h2 {
            margin-bottom: 20px;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
        }
        .login-container input {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-container button {
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #218838;
        }
        .error {
            color: red;
        }
        .forgot-password {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post" action="login.php">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>
    <div class="forgot-password">
        <a href="forgot_password.php">Forgot Password?</a>
    </div>
</div>
</body>
</html>
