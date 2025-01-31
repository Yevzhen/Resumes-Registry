<?php
require_once "pdo.php";

session_start();

// If the user requested cancel go to index.php
if ( isset($_POST['cancel']) ) {
    $_SESSION ['cancel'] = $_POST['cancel'];
    header('Location: index.php');
    return;
}

$salt = 'XyZzy12*_';
// $stored_hash = '1a52e17fa899cf40fb04cfc42e6352f1';  // Pw is php123

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if ( isset($_POST['email']) && isset($_POST['pass'])  ) {
	
	if (empty(trim($_POST['email'])) || empty(trim($_POST['pass']))) {
		$_SESSION ['error'] = "Email and password are required";
	}
	else {
		if (strpos($_POST['email'], '@') === false) {
			$_SESSION ['error'] = "Email must contain an '@' character.";
		}	
		else {
			$check = hash('md5', $salt.$_POST['pass']);
			$stmt = $pdo->prepare('SELECT user_id, name FROM users WHERE email = :em AND password = :pw');
			$stmt->execute(array( ':em' => $_POST['email'], ':pw' => $check));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ( $row !== false ) {
				$_SESSION['name'] = $row['name'];
				$_SESSION['user_id'] = $row['user_id'];
				header("Location: index.php");
				return;
			} else {
				$_SESSION ['error'] = "Incorrect email or password";
				header("Location: login.php");
				return;
			}
		}
	}
}
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Yevheniia Tychynska's Resumes Registry</title>
<script src="validation.js"></script>
</head>
<body>

<div class="container">
<p>Please Login</p>
<?php
if (isset($_SESSION['error'])) {
    echo '<p style="color:red;">' . htmlentities($_SESSION['error']) . "</p>\n";
    unset($_SESSION['error']); // Clear error after displaying
}
?>
<form method="post">
<p>Email:
<input type="text" name="email" id="id_email"></p>
<p>Password:
<input type="password" name="pass" id="id_1723"></p>
<p><input type="submit" onclick="return doValidate();" value="Log In"/>

<input type="submit" name="cancel" value="Cancel"/></p>
</form>
</div>
</body>
</html>

