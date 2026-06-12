<?php
session_start();

// Prüfe ob Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host = "db_server";
$dbname = "aceofdates";
$username = "aceofdates";
$password = "123";

// Datenbankverbindung
$conn = new mysqli($host, $username, $password, $dbname);

// Verbindung prüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$message = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $age = trim($_POST["age"]);
    $gender = trim($_POST["gender"]);
    $interests = trim($_POST["interests"]);
    $preferences = trim($_POST["preferences"]);
    $favorite_food = trim($_POST["favorite_food"]);
    $hobbies = trim($_POST["hobbies"]);
    $sexual_orientation = trim($_POST["sexual_orientation"]);

    if (empty($age) || empty($gender) || empty($interests) || empty($preferences) || empty($favorite_food) || empty($hobbies) || empty($sexual_orientation)) {
        $message = "Bitte alle Felder ausfüllen.";
    } else {
        // Prüfe ob bereits Preferences existieren
        $check = $conn->prepare("SELECT id FROM user_preferences WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Update existierende Preferences
            $stmt = $conn->prepare("UPDATE user_preferences SET age=?, gender=?, interests=?, preferences=?, favorite_food=?, hobbies=?, sexual_orientation=? WHERE id=?");
            $stmt->bind_param("sssssssi", $age, $gender, $interests, $preferences, $favorite_food, $hobbies, $sexual_orientation, $user_id);
        } else {
            // Neue Preferences
            $stmt = $conn->prepare("INSERT INTO user_preferences (id, age, gender, interests, preferences, favorite_food, hobbies, sexual_orientation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $user_id, $age, $gender, $interests, $preferences, $favorite_food, $hobbies, $sexual_orientation);
        }
        
        if ($stmt->execute()) {
            header('Location: profile.php');
            exit;
        } else {
            $message = "Fehler beim Speichern der Daten.";
        }
        $stmt->close();
        $check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Persönliche Infos</title>
    <link rel="stylesheet" href="../stylings/preferences.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="progress-bar"></div>
    <div class="preferences-container">
        <div class="preferences-header">
            <div class="progress-indicator"></div>
            <h1>Persönliche Infos</h1>
            <div class="step-label">Schritt 1 von 2</div>
        </div>
        <div class="preferences-card">
            <?php if (!empty($message)): ?>
                <div class="preferences-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="age">Alter</label>
                <select id="age" name="age" required>
                    <option value="">Wähle dein Alter</option>
                    <option value="16-19">16-19</option>
                    <option value="20-25">20-25</option>
                    <option value="26-30">26-30</option>
                    <option value="31-35">31-35</option>
                    <option value="36-40">36-40</option>
                    <option value="41-50">41-50</option>
                    <option value="51+">51+</option>
                </select>

                <label>Geschlecht</label>
                <div class="gender-options">
                    <label class="gender-option">
                        <input type="radio" id="gender-male" name="gender" value="männlich" required <?php echo isset($_POST['gender']) && $_POST['gender'] === 'männlich' ? 'checked' : ''; ?> >
                        <span>Männlich</span>
                    </label>
                    <label class="gender-option">
                        <input type="radio" id="gender-female" name="gender" value="weiblich" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'weiblich' ? 'checked' : ''; ?> >
                        <span>Weiblich</span>
                    </label>
                    <label class="gender-option">
                        <input type="radio" id="gender-diverse" name="gender" value="divers" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'divers' ? 'checked' : ''; ?> >
                        <span>Divers</span>
                    </label>
                </div>

                <label for="interests">Interessen</label>
                <select id="interests" name="interests" required>
                    <option value="">Wähle deine Interessen</option>
                    <option value="reisen">Reisen</option>
                    <option value="sport">Sport</option>
                    <option value="musik">Musik</option>
                    <option value="kochen">Kochen</option>
                    <option value="lesen">Lesen</option>
                    <option value="kunst">Kunst</option>
                    <option value="technologie">Technologie</option>
                    <option value="natur">Natur</option>
                    <option value="fotografie">Fotografie</option>
                    <option value="filme">Filme</option>
                </select>

                <label for="preferences">Präferenzen</label>
                <select id="preferences" name="preferences" required>
                    <option value="">Wähle deine Präferenz</option>
                    <option value="abenteuerlustig">Abenteuerlustig</option>
                    <option value="naturverbunden">Naturverbunden</option>
                    <option value="kulturell">Kulturell interessiert</option>
                    <option value="entspannt">Entspannt</option>
                    <option value="aktiv">Aktiv und sportlich</option>
                    <option value="romantisch">Romantisch</option>
                    <option value="gesellig">Gesellig</option>
                    <option value="ruhig">Ruhig und besinnlich</option>
                </select>

                <label for="favorite_food">Lieblingsessen</label>
                <select id="favorite_food" name="favorite_food" required>
                    <option value="">Wähle dein Lieblingsessen</option>
                    <option value="sushi">Sushi</option>
                    <option value="pizza">Pizza</option>
                    <option value="burger">Burger</option>
                    <option value="pasta">Pasta</option>
                    <option value="asiatisch">Asiatisch</option>
                    <option value="indisch">Indisch</option>
                    <option value="steak">Steak</option>
                    <option value="vegetarisch">Vegetarisch</option>
                    <option value="fastfood">Fast Food</option>
                    <option value="hausmannskost">Hausmannskost</option>
                </select>

                <label for="hobbies">Hobbys</label>
                <select id="hobbies" name="hobbies" required>
                    <option value="">Wähle dein Hobby</option>
                    <option value="surfing">Surfing</option>
                    <option value="wandern">Wandern</option>
                    <option value="radfahren">Radfahren</option>
                    <option value="schwimmen">Schwimmen</option>
                    <option value="fitness">Fitness</option>
                    <option value="lesen">Lesen</option>
                    <option value="musik">Musik hören/spielen</option>
                    <option value="fotografie">Fotografie</option>
                    <option value="gaming">Gaming</option>
                    <option value="garten">Gartenarbeit</option>
                    <option value="kochen">Kochen</option>
                    <option value="reisen">Reisen</option>
                </select>

                <label for="sexual_orientation">Sexuelle Orientierung</label>
                <select id="sexual_orientation" name="sexual_orientation" required>
                    <option value="">Wähle deine Orientierung</option>
                    <option value="heterosexuell" <?php echo isset($_POST['sexual_orientation']) && $_POST['sexual_orientation'] === 'heterosexuell' ? 'selected' : ''; ?>>Heterosexuell</option>
                    <option value="homosexuell" <?php echo isset($_POST['sexual_orientation']) && $_POST['sexual_orientation'] === 'homosexuell' ? 'selected' : ''; ?>>Homosexuell</option>
                    <option value="bisexuell" <?php echo isset($_POST['sexual_orientation']) && $_POST['sexual_orientation'] === 'bisexuell' ? 'selected' : ''; ?>>Bisexuell</option>
                    <option value="pansexuell" <?php echo isset($_POST['sexual_orientation']) && $_POST['sexual_orientation'] === 'pansexuell' ? 'selected' : ''; ?>>Pansexuell</option>
                    <option value="asexuell" <?php echo isset($_POST['sexual_orientation']) && $_POST['sexual_orientation'] === 'asexuell' ? 'selected' : ''; ?>>Asexuell</option>
                    <option value="keine_angabe" <?php echo isset($_POST['sexual_orientation']) && $_POST['sexual_orientation'] === 'keine_angabe' ? 'selected' : ''; ?>>Keine Angabe</option>
                </select>

                <button type="submit">Weiter &rarr;</button>
            </form>
        </div>
    </div>
    <a href="../index.html"><img class="mascot" src="../img/maskotchen.png" alt="Maskottchen"></a>
</body>
</html>