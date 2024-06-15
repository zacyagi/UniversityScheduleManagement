<?php
require 'db_connection.php'; 

if (isset($_POST['reset_password'])) {
    $username = $_POST['username'];
    $new_password = $_POST['new_password'];

    if (empty($username) || empty($new_password)) {
        $error = "Username or New Password is empty!";
    } else {
        // Check if the user exists
        $stmt = $conn->prepare("SELECT * FROM Employee WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Update the password
            $stmt = $conn->prepare("UPDATE Employee SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $new_password, $username);
            if ($stmt->execute()) {
                $success = "Password successfully updated!";
            } else {
                $error = "Failed to update password!";
            }
        } else {
            $error = "Username Not Found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Exam Planning System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .forgot-password-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .forgot-password-container h2 {
            margin-bottom: 20px;
        }
        .forgot-password-container form {
            display: flex;
            flex-direction: column;
        }
        .forgot-password-container input {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .forgot-password-container button {
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .forgot-password-container button:hover {
            background-color: #0056b3;
        }
        .error, .success {
            color: red;
        }
        .success {
            color: green;
        }
        .back-to-login {
            margin-top: 10px;
        }
        .back-to-login button {
            background-color: #6c757d;
        }
        .back-to-login button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
<div class="forgot-password-container">
    <h2>Forgot Password</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="post" action="forgot_password.php">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <button type="submit" name="reset_password">Reset Password</button>
    </form>
    <div class="back-to-login">
        <form action="login.php">
            <button type="submit">Back to Login</button>
        </form>
    </div>
</div>
</body>
</html>
