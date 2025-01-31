<?php
require_once "pdo.php";

session_start();

if (!isset($_GET['profile_id'])) {
    die("Missing profile_id");
}

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

// If the user requested cancel, go to index.php
if (isset($_POST['cancel'])) {
    header('Location: index.php');
    return;
}

$profile_id = htmlentities($_GET['profile_id']);

// Fetch the profile data from the database using the ID
$stmt = $pdo->prepare('SELECT * FROM profile WHERE profile_id = :pid');
$stmt->execute([':pid' => $profile_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    $_SESSION['error'] = 'Bad value for id';
    header('Location: index.php');
    return;
}

// Fetch positions for the profile
$stmt = $pdo->prepare('SELECT * FROM position WHERE profile_id = :pid ORDER BY rank');
$stmt->execute([':pid' => $profile_id]);
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// fetch education data for the profile
$stmt = $pdo->prepare('SELECT year, name FROM education JOIN institution ON education.institution_id = institution.institution_id 
                        WHERE profile_id = :pid ORDER BY rank');
$stmt->execute([':pid' => $profile_id]);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate profile fields
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['headline'], $_POST['summary'])) {
        if (empty(trim($_POST['first_name'])) || empty(trim($_POST['last_name'])) || empty(trim($_POST['email'])) || empty(trim($_POST['headline'])) || empty(trim($_POST['summary']))) {
            $_SESSION['error'] = "All fields are required";
            header("Location: edit.php?profile_id=" . $profile_id);
            return;
        }

        if (strpos($_POST['email'], '@') === false) {
            $_SESSION['error'] = "Email must contain an '@' character.";
            header("Location: edit.php?profile_id=" . $profile_id);
            return;
        }

        // Update profile data
        $stmt = $pdo->prepare('UPDATE profile SET
            first_name = :fn, last_name = :ln, email = :em, headline = :he, summary = :su
            WHERE profile_id = :pid');
        $stmt->execute([
            ':fn' => $_POST['first_name'],
            ':ln' => $_POST['last_name'],
            ':em' => $_POST['email'],
            ':he' => $_POST['headline'],
            ':su' => $_POST['summary'],
            ':pid' => $profile_id
        ]);


    // Clear out the old position entries
    $stmt = $pdo->prepare('DELETE FROM Position WHERE profile_id=:pid');
    $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));

    // Handle new positions
    // Insert the position entries

$rank = 1;
for($i=1; $i<=9; $i++) {
  if ( ! isset($_POST['year'.$i]) ) continue;
  if ( ! isset($_POST['desc'.$i]) ) continue;

  $year = $_POST['year'.$i];
  $desc = $_POST['desc'.$i];
  $stmt = $pdo->prepare('INSERT INTO Position
    (profile_id, rank, year, description)
    VALUES ( :pid, :rank, :year, :desc)');

  $stmt->execute(array(
  ':pid' => $_REQUEST['profile_id'],
  ':rank' => $rank,
  ':year' => $year,
  ':desc' => $desc)
  );

  $rank++;
}
  // Clear out the old education entries
 $stmt = $pdo->prepare('DELETE FROM Education WHERE profile_id=:pid');
 $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));

 // insert the education entries
$rankEdu = 1;
for($i=1; $i<=9; $i++) {
    if ( ! isset($_POST['edu_year'.$i]) ) continue;
    if ( ! isset($_POST['edu_school'.$i]) ) continue;
  
    $year = $_POST['edu_year'.$i];
    $school = $_POST['edu_school'.$i];

    $institution_id = false;
    $stmt = $pdo->prepare('SELECT institution_id FROM institution WHERE name = :name');
    $stmt->execute(array(':name' => $school));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) $institution_id = $row['institution_id'];
    if ($institution_id === false) {
        $stmt = $pdo->prepare('INSERT INTO Institution (name) VALUES ( :name)');
        $stmt->execute(array(':name' => $school));
        $institution_id = $pdo->lastInsertId();
    }
    $stmt = $pdo->prepare('INSERT INTO Education (profile_id, rank, year, institution_id) VALUES ( :pid, :rank, :year, :iid)');
        $stmt->execute(array(
            ':pid' => $_REQUEST['profile_id'],
            ':rank' => $rankEdu, 
            ':year' => $year, 
            ':iid' => $institution_id
        ));
    $rankEdu++;
}


    $_SESSION['success'] = "Record edited";
    header("Location: index.php");
    return;
}
}

?>


<!DOCTYPE html>
<html>
<head>
<?php require_once "head.php"; ?>
<title>Yevheniia Tychynska's Resumes Registry</title>
<script>
    let countPos = <?php echo count($positions); ?>;

    function addPosition() {
        if (countPos >= 9) {
            alert("Maximum of nine position entries exceeded");
            return;
        }
        countPos++;
        const positionFields = document.getElementById('position_fields');
        const newField = document.createElement('div');
        newField.id = `position${countPos}`;
        newField.innerHTML = `
            <p>Year: <input type="text" name="year${countPos}" value="">
            Description: <input type="text" name="desc${countPos}" value="">
            <input type="button" value="-" onclick="removePosition(${countPos});"></p>
        `;
        positionFields.appendChild(newField);
    }

    function removePosition(pos) {
        const field = document.getElementById(`position${pos}`);
        if (field) field.parentNode.removeChild(field);
    }
</script>
<script>
    let countEdu = <?= count($schools) ?>;

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('addEdu').addEventListener('click', function (event) {
            event.preventDefault(); // Prevent the default form submission behavior
            if (countEdu >= 9) {
                alert("Maximum of nine education entries exceeded");
                return;
            }
            countEdu++;
            const eduFields = document.getElementById('edu_fields');
            const newField = document.createElement('div');
            newField.id = `edu${countEdu}`;
            newField.innerHTML = `
                <p>Year: <input type="text" name="edu_year${countEdu}" value="">
                <input type="button" value="-" onclick="document.getElementById('edu${countEdu}').remove(); return false;"></p>
                <p>School: <input type="text" size="80" name="edu_school${countEdu}" class="school" value=""></p>
            `;
            eduFields.appendChild(newField);

            // Reinitialize the autocomplete for the newly added field
            $('.school').autocomplete({
                source: "school.php"
            });
        });
    });

    // Initialize autocomplete for existing school fields
    $(function () {
        $('.school').autocomplete({
            source: "school.php"
        });
    });
    </script>

    <script id="edu-template" type="text">
        <div id="edu@COUNT@">
            <p> Year: <input type="text" name="edu_year@COUNT@" value=""/>
            <input type="button" value="-" onclick="$('#edu@COUNT@').remove(); return false;"><br>
            <p> School: <input type="text" size="80" name="edu_school@COUNT@" class="school" value=""/>
            </p>
        </div>

    </script>  
</head>

<body>
<div class="container">

<?php
if (isset($_SESSION['error'])) {
    echo '<p style="color: red;">' . htmlentities($_SESSION['error']) . "</p>\n";
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo '<p style="color: green;">' . htmlentities($_SESSION['success']) . "</p>\n";
    unset($_SESSION['success']);
}
?>

<p><b>Editing Profile For <?php echo htmlentities($_SESSION['name']); ?></b></p>
<form method="post">
    <input type="hidden" name="profile_id" value="<?= htmlentities($row['profile_id']) ?>">
    <p>First Name:
        <input type="text" name="first_name" size="40" value="<?= htmlentities($row['first_name']) ?>">
    </p>
    <p>Last Name:
        <input type="text" name="last_name" size="40" value="<?= htmlentities($row['last_name']) ?>">
    </p>
    <p>Email:
        <input type="text" name="email" value="<?= htmlentities($row['email']) ?>">
    </p>
    <p>Headline:
        <input type="text" name="headline" value="<?= htmlentities($row['headline']) ?>">
    </p>
    <p style="text-align: left;">Summary:<br>
        <textarea name="summary" rows="5" cols="50"><?= htmlentities($row['summary']) ?></textarea>
    </p>

    <p>Position: <input type="button" value="+" onclick="addPosition();"></p>
    <div id="position_fields">
        <?php foreach ($positions as $position): ?>
            <div id="position<?= $position['rank'] ?>">
                <p>Year: <input type="text" name="year<?= $position['rank'] ?>"
                                value="<?= htmlentities($position['year']) ?>">
                    Description: <input type="text" name="desc<?= $position['rank'] ?>"
                                        value="<?= htmlentities($position['description']) ?>">
                    <input type="button" value="-" onclick="removePosition(<?= $position['rank'] ?>);"></p>
            </div>
        <?php endforeach; ?>
    </div>

    <p>Education: <input type="button" id="addEdu" value="+"></p>
    <div id="edu_fields">
        <?php
        $countEdu = 0;
        if (count($schools) > 0) {
            foreach ($schools as $school) {
                $countEdu++;
                echo '<div id="edu' . $countEdu . '">';
                echo '<p>Year: <input type="text" name="edu_year' . $countEdu . '" value="' . $school['year'] . '" />
                        <input type="button" value="-" onclick="$(\'#edu' . $countEdu . '\').remove();return false;"></p>';
                echo '<p>School: <input type="text" size="80" name="edu_school' . $countEdu . '" class="school" 
                        value="' . htmlentities($school['name']) . '" />';
                echo "</p>\n</div>\n";
            }
        }
        ?>
    </div>

    <p>
        <input type="submit" value="Save"/>
        <input type="submit" name="cancel" value="Cancel"/>
    </p>
</form>
</div>
</body>

</html>
