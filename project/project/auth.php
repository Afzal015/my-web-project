<?php

require 'config.php';
require 'function.php';

$error = $success = "";

// Determine the page (login or register) based on the query parameter
$page = isset($_GET['page']) && $_GET['page'] === 'register' ? 'register' : 'login';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'register') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $check_query = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($check_query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $error = "Email is already registered!";
    } else {
        $query = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            $success = "Registration successful! <a href='auth.php'>Login here</a>";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// In your login handling logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetchAll();

    if (count($result) > 0) {
        $user = $result[0];
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // If role is 'author', store their email in session
            if ($user['role'] == 'author') {
                $_SESSION['author_email'] = $user['email']; // Store email in session for authors
            }
            // If role is 'reviewer', store their email in session for reviewer dashboard
            if ($user['role'] == 'reviewer') {
                $_SESSION['reviewer_email'] = $user['email']; // Store email in session for reviewers
            }
            

            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'author':
                    header("Location: author_dashboard.php");
                    break;
                case 'reviewer':
                    header("Location: reviewer_dashboard.php");
                    break;
                default:
                    header("Location: auth.php?error=unrecognized_role");
                    break;
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($page); ?> | Research Paper System</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .container { width: 30%; margin: 5% auto; padding: 20px; background: white; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; }
        input, select, button { width: 100%; padding: 10px; margin: 10px 0; }
        button { background-color: #007BFF; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .error { color: red; text-align: center; }
        .success { color: green; text-align: center; }
        .toggle-link { text-align: center; margin-top: 10px; }
        .toggle-link a { color: #007BFF; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo ucfirst($page); ?></h1>
        
        <!-- Display success or error messages -->
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if ($page === 'login'): ?>
            <!-- Login Form -->
            <form action="auth.php" method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <input type="password" name="password" placeholder="Enter your password" required>
                <button type="submit">Login</button>
            </form>
            <div class="toggle-link">
                <p>Don't have an account? <a href="auth.php?page=register">Register here</a>.</p>
            </div>
        <?php elseif ($page === 'register'): ?>
            <!-- Registration Form -->
            <form action="auth.php?page=register" method="POST">
                <input type="text" name="name" placeholder="Enter your name" required>
                <input type="email" name="email" placeholder="Enter your email" required>
                <input type="password" name="password" placeholder="Create a password" required>
                <select name="role" required>
                    <option value="author">Author</option>
                    <option value="reviewer">Reviewer</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit">Register</button>
            </form>
            <div class="toggle-link">
                <p>Already have an account? <a href="auth.php">Login here</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
