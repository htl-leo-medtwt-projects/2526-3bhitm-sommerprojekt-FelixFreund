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

$personality = "";
$hobby = "";
$existing_profile_image_path = "";
$profile_image_path = "../img/profile_placeholder.png";

// Lade vorhandenes Profil, damit Bild und Felder beim Bearbeiten erhalten bleiben
$profileCheck = $conn->prepare("SELECT personality, hobby, image_path FROM profiles WHERE id = ?");
$profileCheck->bind_param("i", $user_id);
$profileCheck->execute();
$profileResult = $profileCheck->get_result();
if ($profileRow = $profileResult->fetch_assoc()) {
    $personality = $profileRow['personality'];
    $hobby = $profileRow['hobby'];
    $existing_profile_image_path = $profileRow['image_path'];
    if (!empty($existing_profile_image_path)) {
        $profile_image_path = $existing_profile_image_path;
    }
}
$profileCheck->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['profile_image_path'])) {
        $profile_image_path = htmlspecialchars($_POST['profile_image_path']);
    } elseif (!empty($existing_profile_image_path)) {
        $profile_image_path = $existing_profile_image_path;
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

    <label>Profilbild wählen</label>
    <!-- Verstecktes Feld speichert den gewählten Pfad -->
    <input type="hidden" id="selected_avatar" name="profile_image_path" value="<?php echo htmlspecialchars($profile_image_path); ?>">

    <!-- 3x3 Avatar-Grid -->
    <div class="avatar-grid" id="avatarGrid">
        <div class="avatar-item" data-src="../img/1_herz.png">
            <div class="avatar-img-wrap">
                <img src="../img/1_herz.png" alt="Avatar 1">
            </div>
            <div class="avatar-check">
                <svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5">
                    <polyline points="1,5 4.5,8.5 11,1"/>
                </svg>
            </div>
        </div>
  
        <div class="avatar-item" data-src="../img/1_blatt.png">
            <div class="avatar-img-wrap"><img src="../img/1_blatt.png" alt="Avatar 2"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/1_spade.png">
            <div class="avatar-img-wrap"><img src="../img/1_spade.png" alt="Avatar 3"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/10_herz.png">
            <div class="avatar-img-wrap"><img src="../img/10_herz.png" alt="Avatar 4"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/10_blatt.png">
            <div class="avatar-img-wrap"><img src="../img/10_blatt.png" alt="Avatar 5"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/10_spade.png">
            <div class="avatar-img-wrap"><img src="../img/10_spade.png" alt="Avatar 6"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/koenig_herz.png">
            <div class="avatar-img-wrap"><img src="../img/koenig_herz.png" alt="Avatar 7"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/koenig_blatt.png">
            <div class="avatar-img-wrap"><img src="../img/koenig_blatt.png" alt="Avatar 8"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
        <div class="avatar-item" data-src="../img/koenig_spade.png">
            <div class="avatar-img-wrap"><img src="../img/koenig_spade.png" alt="Avatar 9"></div>
            <div class="avatar-check"><svg viewBox="0 0 12 10" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="1,5 4.5,8.5 11,1"/></svg></div>
        </div>
    </div>

    <label for="personality">Name</label>
    <input type="text" id="personality" name="personality" placeholder="z.B. Max Mustermann" required value="<?php echo htmlspecialchars($personality); ?>">

    <label for="hobby">Lieblingshobby</label>
    <input type="text" id="hobby" name="hobby" placeholder="z.B. Surfing" required value="<?php echo htmlspecialchars($hobby); ?>">

    <button type="submit" id="submitBtn" <?php echo empty($profile_image_path) || $profile_image_path === '../img/profile_placeholder.png' ? 'disabled' : ''; ?>>Profil erstellen</button>
    <p style="text-align:center; font-size:0.85rem; color:#999; margin-top:0.5rem;">Bitte wähle zuerst ein Profilbild</p>
</form>
        </div>
    </div>
    <a href="../index.html"><img class="mascot" src="../img/maskotchen.png" alt="Maskottchen"></a>
    <script>
const selectedAvatarInput = document.getElementById('selected_avatar');
const submitBtn = document.getElementById('submitBtn');

function updateSelection() {
    const selectedValue = selectedAvatarInput.value;
    document.querySelectorAll('.avatar-item').forEach(function(item) {
        item.classList.toggle('selected', item.dataset.src === selectedValue);
    });
    submitBtn.disabled = !selectedValue || selectedValue === '../img/profile_placeholder.png';
}

document.querySelectorAll('.avatar-item').forEach(function(item) {
    item.addEventListener('click', function() {
        document.querySelectorAll('.avatar-item').forEach(function(el) {
            el.classList.remove('selected');
        });
        item.classList.add('selected');
        selectedAvatarInput.value = item.dataset.src;
        submitBtn.disabled = false;
    });
});

updateSelection();
</script>
</body>
</html>
