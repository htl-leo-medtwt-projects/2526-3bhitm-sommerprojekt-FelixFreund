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
$profile_image_path = "../img/profile_placeholder.png";
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Bild-Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../img/uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image_path = $target_file;
        }
    }

    $personality = trim($_POST["personality"]);
    $hobby = trim($_POST["hobby"]);

    if (empty($personality) || empty($hobby)) {
        $message = "Bitte alle Felder ausfüllen.";
    } else {
        // Prüfe ob bereits Profil existiert
        $check = $conn->prepare("SELECT id FROM profiles WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Update existierendes Profil
            $stmt = $conn->prepare("UPDATE profiles SET personality=?, hobby=?, image_path=? WHERE id=?");
            $stmt->bind_param("sssi", $personality, $hobby, $profile_image_path, $user_id);
        } else {
            // Neues Profil
            $stmt = $conn->prepare("INSERT INTO profiles (id, personality, hobby, image_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $personality, $hobby, $profile_image_path);
        }
        
        if ($stmt->execute()) {
            // Profil erfolgreich erstellt/aktualisiert, zur home.php leiten
            header('Location: home.php');
            exit;
        } else {
            $message = "Fehler beim Speichern des Profils.";
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
    <title>Titelblatt erstellen</title>
    <link rel="stylesheet" href="../stylings/profile.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Titelblatt erstellen</h1>
            <div class="step-label">Schritt 2 von 2</div>
        </div>
        <div class="profile-card">
            <?php if (!empty($message)): ?>
                <div class="profile-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <label for="profile_image">Profilbild</label>
                <div class="profile-image-preview">
                    <img id="preview" src="<?php echo $profile_image_path; ?>" alt="Profilbild">
                </div>
                <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(event)">

                <label for="personality">Bulletpoint über Persönlichkeit</label>
                <input type="text" id="personality" name="personality" placeholder="z.B. Spontan & reiselustig" required>

                <label for="hobby">Lieblingshobby</label>
                <input type="text" id="hobby" name="hobby" placeholder="z.B. Surfing" required>

                <button type="submit">Profil erstellen</button>
            </form>
        </div>
    </div>
    <a href="../index.html"><img class="mascot" src="../img/maskotchen.png" alt="Maskottchen"></a>
    <script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('preview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
    </script>
</body>
</html>
