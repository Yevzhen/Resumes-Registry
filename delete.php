<?php
require_once "pdo.php";

session_start();

if ( ! isset($_SESSION['user_id']) ) {
    die("Not logged in");
}

// If the user requested cancel go to index.php
if ( isset($_POST['cancel']) ) {
    $_SESSION ['cancel'] = $_POST['cancel'];
    header('Location: index.php');
    return;
}

if ( ! isset($_GET['profile_id']) ) {
    $_SESSION['error'] = "Missing profile id";
    header('Location: index.php');
    return;
}

$profile_id = $_GET['profile_id'];

// Fetch the car data from the database using the ID
$stmt = $pdo->prepare('SELECT * FROM profile WHERE profile_id = :pid AND user_id = :uid');
$stmt->execute([':pid' => $profile_id, ':uid' => $_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ( $row === false ) {
    $_SESSION['error'] = 'Profile not found or access denied';
    header('Location: index.php');
    return;
}

if ( isset($_POST['delete'])) {
    $stmt = $pdo->prepare('DELETE from profile WHERE profile_id = :pid AND user_id = :uid');
    $stmt->execute([':pid' => $profile_id, ':uid' => $_SESSION['user_id']]);
    $_SESSION ['success'] = "Record deleted";
    header("Location: index.php");
    return;
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Yevheniia Tychynska - Resume Registry</title>
</head>
<body>
<div class="container">
<h1>Deleting Record</h1>

<?php
if ( isset($_SESSION['error']) ) {
    echo '<p style="color: red;">'.htmlentities($_SESSION['error'])."</p>\n";
    unset($_SESSION['error']);
}
else {
    echo "<p><b>Confirm deleting:</b></p>\n";
    echo "<p><b>First name: </b>".htmlentities($row['first_name'])."</p>\n";
    echo "<p><b>Last name: </b>".htmlentities($row['last_name'])."</p>\n";
}

?>

<form method="post">
<p><input type="submit" name="delete" value="Delete"/>
<input type="submit" name="cancel" value="Cancel"/></p>
</form>

</div>
</body>
</html>
