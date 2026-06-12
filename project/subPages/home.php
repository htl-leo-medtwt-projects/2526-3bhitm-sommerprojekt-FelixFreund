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

$user_id = $_SESSION['user_id'];

// Hole die Preferences des aktuellen Benutzers
$user_prefs = $conn->prepare("SELECT age, gender, interests, preferences, favorite_food, hobbies, sexual_orientation FROM user_preferences WHERE id = ?");
$user_prefs->bind_param("i", $user_id);
$user_prefs->execute();
$user_prefs_result = $user_prefs->get_result();
$current_user = $user_prefs_result->fetch_assoc();
$user_prefs->close();

if (!$current_user) {
    header('Location: preferences.php');
    exit;
}

// Profil aus DB laden
$stmt = $conn->prepare("SELECT image_path FROM profiles WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$avatar = $result['image_path'] ?? '../img/profile_placeholder.png';
$stmt->close();

// Hole alle anderen Benutzer mit deren Preferences und Profilen
$all_users = $conn->prepare("
    SELECT u.id, up.age, up.gender, up.interests, up.preferences, up.favorite_food, up.hobbies, up.sexual_orientation,
           prof.personality, prof.hobby, prof.image_path
    FROM users u
    LEFT JOIN user_preferences up ON u.id = up.id
    LEFT JOIN profiles prof ON u.id = prof.id
    WHERE u.id != ?
");
$all_users->bind_param("i", $user_id);
$all_users->execute();
$all_users_result = $all_users->get_result();
$users_list = [];
while ($row = $all_users_result->fetch_assoc()) {
    $users_list[] = $row;
}
$all_users->close();

// Prüft ob zwei Personen aufgrund ihrer Orientierung und Geschlechter kompatibel sind
function isOrientationCompatible($current, $other) {
    $myOrientation    = $current['sexual_orientation'] ?? 'keine_angabe';
    $otherOrientation = $other['sexual_orientation']   ?? 'keine_angabe';
    $myGender         = $current['gender'] ?? '';
    $otherGender      = $other['gender']   ?? '';

    // Pansexuell, asexuell und keine_angabe passen immer
    $openOrientations = ['pansexuell', 'keine_angabe', 'asexuell'];
    if (in_array($myOrientation, $openOrientations) || in_array($otherOrientation, $openOrientations)) {
        return true;
    }

    // Bisexuell passt zu allen
    if ($myOrientation === 'bisexuell' || $otherOrientation === 'bisexuell') {
        return true;
    }

    // Heterosexuell: muss unterschiedliches Geschlecht haben
    if ($myOrientation === 'heterosexuell' && $otherOrientation === 'heterosexuell') {
        return $myGender !== $otherGender;
    }

    // Homosexuell: muss gleiches Geschlecht haben
    if ($myOrientation === 'homosexuell' && $otherOrientation === 'homosexuell') {
        return $myGender === $otherGender;
    }

    // Hetero trifft auf Homo → nicht kompatibel
    if (
        ($myOrientation === 'heterosexuell' && $otherOrientation === 'homosexuell') ||
        ($myOrientation === 'homosexuell'   && $otherOrientation === 'heterosexuell')
    ) {
        return false;
    }

    return true;
}

// Berechne Kompatibilität für jeden Benutzer
function calculateCompatibility($current, $other) {
    $score = 0;
    $max_score = 0;

    $fields = ['age', 'gender', 'interests', 'preferences', 'favorite_food', 'hobbies'];
    
    foreach ($fields as $field) {
        $max_score += 100;
        
        if (!empty($other[$field]) && !empty($current[$field])) {
            if ($other[$field] === $current[$field]) {
                $score += 100;
            } elseif ($field === 'age') {
                $score += 30;
            } else {
                $score += 10;
            }
        } elseif (!empty($other[$field])) {
            $score += 5;
        }
    }
    
    return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
}

// Berechne Kompatibilität für alle Benutzer
$partners = [];
foreach ($users_list as $user) {
    if (!empty($user['age'])) {
        // Orientierung prüfen – unpassende Kombinationen direkt ausschließen
        if (!isOrientationCompatible($current_user, $user)) {
            continue;
        }
        $compatibility = calculateCompatibility($current_user, $user);
        $partners[] = [
            'id' => $user['id'],
            'age' => $user['age'],
            'gender' => $user['gender'],
            'interests' => $user['interests'],
            'preferences' => $user['preferences'],
            'favorite_food' => $user['favorite_food'],
            'hobbies' => $user['hobbies'],
            'sexual_orientation' => $user['sexual_orientation'],
            'personality' => $user['personality'],
            'hobby' => $user['hobby'],
            'profile_image' => $user['image_path'] ?? '../img/profile_placeholder.png',
            'compatibility' => $compatibility
        ];
    }
}

// Sortiere nach Kompatibilität (absteigend)
usort($partners, function($a, $b) {
    return $b['compatibility'] - $a['compatibility'];
});

// Nimm nur die Top 5
$top_partners = array_slice($partners, 0, 5);

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Suche - Ace of Dates</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../stylings/home.css">
    <script src="../script-home.js" defer></script>
</head>
<body>
    <div class="home-container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <a href="../index.html" class="nav-logo">
                <img src="../img/blinder_logo.png" alt="Logo">
            </a>
            <div class="nav-menu">
                <a href="home.php" class="nav-item active">Date Suche</a>
                <a href="posteingang.php" class="nav-item">Posteingang</a>
                <a href="profile_view.php" class="nav-item">Profil</a>
                <a href="next_date.php" class="nav-item">Next Date</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="page-shell">
            <div class="dating-cards-container">
                <!-- Card Slider -->
                <div class="cards-slider">
                    <?php if (count($top_partners) > 0): ?>
                        <?php foreach ($top_partners as $index => $partner): ?>
                            <div class="dating-card" data-index="<?php echo $index; ?>" data-id="<?php echo $partner['id']; ?>">
                                <div class="card-image-container">
                                    <img src="<?php echo htmlspecialchars($partner['profile_image']); ?>" alt="Profilbild" class="card-image">
                                </div>
                                <div class="card-content">
                                    <div class="card-header">
                                        <h2 class="card-title"><?php echo htmlspecialchars($partner['personality']); ?></h2>
                                        <div class="compatibility-badge">
                                            <span class="compat-percentage"><?php echo $partner['compatibility']; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="card-details">
                                        <p><strong>Alter:</strong> <?php echo htmlspecialchars($partner['age']); ?></p>
                                        <p><strong>Geschlecht:</strong> <?php echo htmlspecialchars($partner['gender']); ?></p>
                                        <p><strong>Hobby:</strong> <?php echo htmlspecialchars($partner['hobby']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-partners">
                            <p>Keine passenden Partner gefunden. Bitte später erneut versuchen.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation Controls -->
                <div class="cards-controls">
                    <button class="control-btn prev-btn" id="prevBtn" aria-label="Zurück">
                        <span>&lt;</span>
                    </button>
                    <button class="control-btn next-btn" id="nextBtn" aria-label="Weiter">
                        <span>&gt;</span>
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="action-btn dislike-btn" id="dislikeBtn" title="Nicht interessiert">
                        <span class="icon">✕</span>
                    </button>
                    <button class="action-btn like-btn" id="likeBtn" title="Interessant">
                        <span class="icon">♥</span>
                    </button>
                </div>

                <!-- Progress Indicator -->
                <div class="progress-info">
                    <span id="currentIndex">1</span> / <span id="totalPartners"><?php echo count($top_partners); ?></span>
                </div>
            </div>
        </main>
    </div>
</body>
</html>