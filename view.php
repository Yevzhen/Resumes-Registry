<?php
require_once "pdo.php";

session_start();

if (!isset($_GET['profile_id'])) {
    die("Missing profile_id");
}

$profile_id = htmlentities($_GET['profile_id']);


$stmt = $pdo->prepare("SELECT first_name, last_name, email, headline, summary FROM profile WHERE profile_id = :pid");
$stmt->execute(array(':pid' => $profile_id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Profile not found");
}

$stmt = $pdo->prepare('SELECT * FROM position WHERE profile_id = :pid ORDER BY rank');
$stmt->execute([':pid' => $profile_id]);
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT year, name FROM education JOIN institution ON education.institution_id = institution.institution_id 
                        WHERE education.profile_id = :pid ORDER BY rank');
$stmt->execute([':pid' => $profile_id]);
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
<title>Yevheniia Tychynska's Resume Registry</title>
</head>
<body>
<div class="container">
<h1>Profile Information</h1>
<p><strong>First Name:</strong> <?= htmlentities($row['first_name']) ?></p>
<p><strong>Last Name:</strong> <?= htmlentities($row['last_name']) ?></p>
<p><strong>Email:</strong> <?= htmlentities($row['email']) ?></p>
<p><strong>Headline:</strong> <?= htmlentities($row['headline']) ?></p>
<p><strong>Summary:</strong> <?= htmlentities($row['summary']) ?></p>
<p><strong>Positions:</strong> 
<ul>
<?php foreach ($positions as $position): ?>
    <li>
        <?= htmlentities($position['year']) ?> - <?= htmlentities($position['description']) ?>
    </li>
<?php endforeach; ?>
</ul></p>
<p><strong>Educations:</strong> 
<ul>
<?php foreach ($educations as $education): ?>
    <li>
        <?= htmlentities($education['year']) ?> - <?= htmlentities($education['name']) ?>
    </li>
<?php endforeach; ?>
</ul></p>
</div>

<form method="post">
<p>
    <a href = "index.php">Done</a>
</p>
</form>

</body>
</html>
