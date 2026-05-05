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

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Hole die gespeicherten Preferences des aktuellen Benutzers
$user_prefs = $conn->prepare("SELECT age, gender, interests, preferences, favorite_food, hobbies FROM user_preferences WHERE id = ?");
$user_prefs->bind_param("i", $user_id);
$user_prefs->execute();
$user_prefs_result = $user_prefs->get_result();
$current_user = $user_prefs_result->fetch_assoc();
$user_prefs->close();

if (!$current_user) {
    header('Location: preferences.php');
    exit;
}

// Hole das Profil des aktuellen Benutzers
$user_profile = $conn->prepare("SELECT personality, hobby, image_path FROM profiles WHERE id = ?");
$user_profile->bind_param("i", $user_id);
$user_profile->execute();
$user_profile_result = $user_profile->get_result();
$current_profile = $user_profile_result->fetch_assoc();
$user_profile->close();
$conn->close();

if (!$current_profile) {
    header('Location: profile.php');
    exit;
}

function formatLabel($value) {
    if (empty($value)) {
        return "-";
    }
    $formatted = str_replace(['_', '-'], [' ', '–'], $value);
    return ucfirst($formatted);
}

function formatAgeNumber($range) {
    $map = [
        '16-19' => '18',
        '20-25' => '23',
        '26-30' => '28',
        '31-35' => '33',
        '36-40' => '38',
        '41-50' => '45',
        '51+' => '53'
    ];

    return $map[$range] ?? $range;
}

$profile_image_path = $current_profile['image_path'];
if (empty($profile_image_path) || !file_exists(__DIR__ . '/' . $profile_image_path)) {
    $profile_image_path = '../img/profile_placeholder.png';
}

$age_display = formatAgeNumber($current_user['age']);
$personality = htmlspecialchars($current_profile['personality']);
$hobby = htmlspecialchars($current_profile['hobby']);
$interests = formatLabel(htmlspecialchars($current_user['interests']));
$preferences = formatLabel(htmlspecialchars($current_user['preferences']));
$favorite_food = formatLabel(htmlspecialchars($current_user['favorite_food']));
$gender = formatLabel(htmlspecialchars($current_user['gender']));
$hobbies = formatLabel(htmlspecialchars($current_user['hobbies']));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Profil</title>
    <link rel="stylesheet" href="../stylings/profile-view.css">
</head>
<body>
    <div class="profile-page">
        <header class="profile-header">
            <h1>Mein Profil</h1>
        </header>

        <section class="profile-card">
            <div class="profile-image-wrap">
                <div class="profile-image-border">
                    <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profilbild" class="profile-image">
                </div>
            </div>
            <div class="profile-name">Du, <?php echo htmlspecialchars($age_display); ?></div>

            <div class="profile-details">
                <div class="profile-item">
                    <span class="profile-label">Persönlichkeit</span>
                    <span class="profile-value"><?php echo $personality; ?></span>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Lieblingshobby</span>
                    <span class="profile-value"><?php echo $hobby; ?></span>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Interessen</span>
                    <span class="profile-value"><?php echo $interests; ?></span>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Präferenzen</span>
                    <span class="profile-value"><?php echo $preferences; ?></span>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Lieblingsessen</span>
                    <span class="profile-value"><?php echo $favorite_food; ?></span>
                </div>
            </div>

            <div class="profile-actions">
                <a class="button edit-button" href="profile.php">Profil bearbeiten</a>
                <a class="button logout-button" href="logout.php">Ausloggen</a>
            </div>
        </section>

        <section class="info-card">
            <div class="info-title">Kompatibilität</div>
            <p>Dein Profil wird verwendet, um die besten Matches für dich zu finden.</p>
        </section>

        <nav class="bottom-navigation">
            <a href="home.php" class="bottom-link">
                <span class="bottom-icon"></span>
                <span>Date Suche</span>
            </a>
            <a href="preferences.php" class="bottom-link">
                <span class="bottom-icon"></span>
                <span>Einstellungen</span>
            </a>
            <a href="profile.php" class="bottom-link">
                <span class="bottom-icon"></span>
                <span>Bearbeiten</span>
            </a>
            <a href="logout.php" class="bottom-link">
                <span class="bottom-icon"></span>
                <span>Logout</span>
            </a>
        </nav>
    </div>
</body>
</html>
