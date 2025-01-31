<!DOCTYPE html>
<html>
<head>
<title>Yevheniia Tychynska - Resumes Database</title>

<?php 
require_once "pdo.php"; 
session_start();

// if user requested login go to login.php
if ( isset($_POST['login'])) {
    $_SESSION ['login'] = $_POST['login'];
    header('Location: login.php');
    return;
}

// If the user requested logout go to logout.php
if ( isset($_POST['logout']) ) {
    $_SESSION ['logout'] = $_POST['logout'];
    header('Location: logout.php');
    return;
}

// If user pressed add button go to autos.php
if (isset($_POST['addnew'])) {
    $_SESSION ['addnew'] = $_POST['addnew'];
    header('Location: add.php');
    return;
}

$stmt = $pdo->query("SELECT profile_id, first_name, last_name, headline FROM profile");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

</head>
<body>
<div class="container">
<h1>Yevheniia Tychynska's Resumes Registry</h1>

<?php
    if ( ! isset($_SESSION['user_id']) ) {  
        echo ('<p><a href="login.php">Please log in</a></p>');
    }

    if ( isset ($_SESSION ['success']) ) {
        echo('<p style="color: green;">'.htmlentities($_SESSION['success'])."</p>\n");
        unset($_SESSION['success']);
    }
        
        if (isset($rows) && count($rows) > 0) {
            echo ('<table border="1">');
            echo ("<tr><td><b>Name</b></td>");
            echo ("<td><b>Headliine</b></td>");
            if ( isset($_SESSION['user_id']) ) {
                echo ("<td><b>Action</b></td>");
            }
            echo ("</tr>");
            foreach ( $rows as $row ) {
                echo "<tr><td>";
                echo '<a href="view.php?profile_id=' . urlencode($row['profile_id']) . '">' . htmlentities($row['first_name']) . ' ' . htmlentities($row['last_name']) . '</a>';
                echo ("</td><td>");
                echo htmlentities($row['headline']);
                if ( isset($_SESSION['user_id']) ) {
                    echo ("</td><td>");
                    echo('<a href = "edit.php?profile_id='.urlencode($row['profile_id']).'">Edit</a> / <a href = "delete.php?profile_id='.urlencode($row['profile_id']).'">Delete</a>');
                }
                echo("</td></tr>\n");
            }
        }
        
        echo ('<p><a href = "add.php">Add New Entry</a></p>');
        if ( isset($_SESSION['user_id']) ) {
            echo ('<p><a href = "logout.php">Logout</a></p>');
        }
?>
</div>
</body>
</html>
