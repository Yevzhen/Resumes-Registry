<?php
require_once "pdo.php";

session_start();

// If the user requested cancel go to index.php
if (isset($_POST['cancel'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['pass'])) {
        
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['pass']);

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Email and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
        } else {
            $stmt = $pdo->prepare('SELECT user_id, name, password FROM users WHERE email = :em');
            $stmt->execute([':em' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($password, $row['password'])) {
                session_regenerate_id(true);  // Prevent session fixation
                $_SESSION['name'] = $row['name'];
                $_SESSION['user_id'] = $row['user_id'];
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid credentials. Please try again.";
                header("Location: login.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Secure App</title>
    <script src="validation.js"></script>
</head>
<body>
    <div class="container">
        <p>Please Login</p>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color:red;">' . htmlentities($_SESSION['error']) . "</p>\n";
            unset($_SESSION['error']);
        }
        ?>
        <form method="post">
            <p>Email: <input type="email" name="email" required></p>
            <p>Password: <input type="password" name="pass" required></p>
            <p>
                <input type="submit" value="Log In">
                <input type="submit" name="cancel" value="Cancel">
            </p>
        </form>
    </div>
</body>
</html>

