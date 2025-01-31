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

function validatePos() {
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['year'.$i]) ) continue;
        if ( ! isset($_POST['desc'.$i]) ) continue;

        $year = trim($_POST['year'.$i]);
        $desc = trim($_POST['desc'.$i]);

        if ( strlen($year) == 0 || strlen($desc) == 0 ) {
            return "All fields are required";
        }

        if ( ! is_numeric($year) ) {
            return "Position year must be numeric";
        }
    }
    return true;
}

if (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary'])) {
    if (empty(trim($_POST['first_name'])) || empty(trim($_POST['last_name'])) || empty(trim($_POST['email'])) || empty(trim($_POST['headline'])) || empty(trim($_POST['summary']))) {
        $_SESSION ['error'] = "All fields are required";
        header("Location: add.php");
        return;
    }

    if (strpos($_POST['email'], '@') === false) {
			$_SESSION ['error'] = "Email must contain an '@' character.";
            header("Location: add.php");
            return;
	}
    
    $validation_result = validatePos();
    if ($validation_result !== true) {
        // If validation fails
        $_SESSION['error'] = $validation_result;
        header("Location: add.php");
        return;
    }
    
    $stmt = $pdo->prepare('INSERT INTO profile
        (user_id, first_name, last_name, email, headline, summary)
        VALUES ( :uid, :fn, :ln, :em, :he, :su)');
    $stmt->execute(array(
        ':uid' => $_SESSION['user_id'],
        ':fn' => $_POST['first_name'],
        ':ln' => $_POST['last_name'],
        ':em' => $_POST['email'],
        ':he' => $_POST['headline'],
        ':su' => $_POST['summary'])
    );

    // Get the ID of the newly inserted profile
    $profile_id = $pdo->lastInsertId();
    
    for ($i = 1; $i <= 9; $i++) {
        if (!isset($_POST['year' . $i]) || !isset($_POST['desc' . $i])) continue;

        $year = trim($_POST['year' . $i]);
        $desc = trim($_POST['desc' . $i]);

        if (strlen($year) > 0 && strlen($desc) > 0) {
            $stmt = $pdo->prepare('INSERT INTO position (profile_id, rank, year, description)
                                   VALUES (:pid, :rank, :year, :desc)');
            $stmt->execute([
                ':pid' => $profile_id,
                ':rank' => $i,
                ':year' => $year,
                ':desc' => $desc
            ]);
        }
    }

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
                ':pid' => $profile_id,
                ':rank' => $i, 
                ':year' => $year, 
                ':iid' => $institution_id
            ));

    }

    $_SESSION ['success'] = "Record added";
    header("Location: index.php");
    return;
}
?>

<!DOCTYPE html>
<html>
<head>
<?php require_once "head.php"; ?>
<title>Yevheniia Tychynska's Resumes Registry</title>
<script>
    let countPos = 0;

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
        <p>
            Year: <input type="text" name="year${countPos}" value="">
            Description: <input type="text" name="desc${countPos}" value="">
            <input type="button" value="-" onclick="removePosition(${countPos});">
        </p>
    `;
    positionFields.appendChild(newField);
}

function removePosition(pos) {
    const field = document.getElementById(`position${pos}`);
    if (field) field.parentNode.removeChild(field);
}

</script>
<script>
    let countEdu = 0;

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
    //$(function () {
     //   $('.school').autocomplete({
     //       source: "school.php"
     //   });
   // });
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

if ( isset($_SESSION['error']) ) {
	echo('<p style="color: red;">'.htmlentities($_SESSION['error'])."</p>\n");
	unset($_SESSION['error']);
}

if ( isset ($_SESSION ['success']) ) {
    echo('<p style="color: green;">'.htmlentities($_SESSION['success'])."</p>\n");
    unset($_SESSION['success']);
}

?>
<p><b>Adding Profile For <?php echo htmlentities($_SESSION['name']); ?></b></p>
<form method="post">
<p>First Name:
<input type="text" name="first_name" size="40"></p>
<p>Last Name:
<input type="text" name="last_name" size="40"></p>
<p>Email:
<input type="text" name="email"></p>
<p>Headline:
<input type="text" name="headline"></p>
<p style="text-align: left;">Summary:<br>
<textarea name="summary" rows="5" cols="50"></textarea></p>

<p>Position: <input type="button" value="+" onclick="addPosition();"></p>
    <div id="position_fields"></div>

    <p>Education: <input type="button" id="addEdu" value="+"></p>
    <div id="edu_fields">
    </div>
<p><input type="submit" value="Add"/>
<input type="submit" name="cancel" value="Cancel"/></p>
</form>
</div>
</body>
</html>
