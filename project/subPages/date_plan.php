<?php
session_start();

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

$sql = "CREATE TABLE IF NOT EXISTS date_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    receiver_id INT NOT NULL,
    activity VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

$userReactionsSql = "CREATE TABLE IF NOT EXISTS user_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    partner_id INT NOT NULL,
    reaction ENUM('like','dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (user_id, partner_id)
)";
$conn->query($userReactionsSql);

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner_id = intval($_POST['partner_id'] ?? 0);
    $activity = trim($_POST['activity'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = trim($_POST['location'] ?? '');

    if ($partner_id <= 0 || $partner_id === $user_id) {
        header('Location: home.php');
        exit;
    }

    if (empty($activity) || empty($date) || empty($time) || empty($location)) {
        $error = 'Bitte fülle alle Felder aus.';
    } else {
        $stmt = $conn->prepare("INSERT INTO date_requests (requester_id, receiver_id, activity, date, time, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissss', $user_id, $partner_id, $activity, $date, $time, $location);
        if ($stmt->execute()) {
            $success = true;
            // Speichere die Like-Reaktion für den aktuellen Benutzer
            $check = $conn->prepare("SELECT id FROM user_reactions WHERE user_id = ? AND partner_id = ?");
            $check->bind_param('ii', $user_id, $partner_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $updateReaction = $conn->prepare("UPDATE user_reactions SET reaction = 'like' WHERE user_id = ? AND partner_id = ?");
                $updateReaction->bind_param('ii', $user_id, $partner_id);
                $updateReaction->execute();
                $updateReaction->close();
            } else {
                $insertReaction = $conn->prepare("INSERT INTO user_reactions (user_id, partner_id, reaction) VALUES (?, ?, 'like')");
                $insertReaction->bind_param('ii', $user_id, $partner_id);
                $insertReaction->execute();
                $insertReaction->close();
            }
            $check->close();
        } else {
            $error = 'Die Anfrage konnte nicht gesendet werden. Bitte versuche es erneut.';
        }
        $stmt->close();
    }
}

$partner_id = intval($_GET['partner_id'] ?? 0);
if ($partner_id <= 0 || $partner_id === $user_id) {
    header('Location: home.php');
    exit;
}

$stmt = $conn->prepare("SELECT prof.personality, prof.hobby, up.age, up.gender, prof.image_path FROM users u LEFT JOIN profiles prof ON u.id = prof.id LEFT JOIN user_preferences up ON u.id = up.id WHERE u.id = ?");
$stmt->bind_param('i', $partner_id);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$partner) {
    header('Location: home.php');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date planen - Ace of Dates</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../stylings/home.css">
</head>
<body>
    <div class="home-container">
        <nav class="navbar">
            <a href="../index.html" class="nav-logo"><img src="../img/blinder_logo.png" alt="Logo"></a>
            <div class="nav-menu">
                <a href="home.php" class="nav-item">Date Suche</a>
                <a href="posteingang.php" class="nav-item">Posteingang</a>
                <a href="profile_view.php" class="nav-item">Profil</a>
                <a href="next_date.php" class="nav-item">Next Date</a>
            </div>
        </nav>

        <main class="page-shell">
            <div class="date-plan-card">
                <h1>Date planen</h1>
                <div class="request-profile">
                    <img src="<?php echo htmlspecialchars($partner['image_path'] ?? '../img/profile_placeholder.png'); ?>" alt="Partner" class="request-avatar">
                    <div>
                        <h2><?php echo htmlspecialchars($partner['personality'] ?? 'Unbekannt'); ?></h2>
                        <p><?php echo htmlspecialchars($partner['age'] ?? ''); ?> Jahre · <?php echo htmlspecialchars($partner['gender'] ?? ''); ?></p>
                        <p><?php echo htmlspecialchars($partner['hobby'] ?? ''); ?></p>
                    </div>
                </div>
                <?php if ($error): ?>
                    <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($success): ?>
                    <div class="form-success">Deine Date-Anfrage wurde erfolgreich gesendet.</div>
                <?php endif; ?>
                <form method="post" action="date_plan.php?partner_id=<?php echo $partner_id; ?>" class="date-form">
                    <input type="hidden" name="partner_id" value="<?php echo $partner_id; ?>">
                    <label>Art des Treffens</label>
                    <select name="activity" required>
                        <option value="">Bitte wählen</option>
                        <option value="Kaffee">Kaffee</option>
                        <option value="Spaziergang">Spaziergang</option>
                        <option value="Restaurant">Restaurant</option>
                    </select>
                    <label>Datum</label>
                    <input type="date" name="date" required>
                    <label>Uhrzeit</label>
                    <input type="time" name="time" required>
                    <label>Ort</label>
                    <input type="text" name="location" placeholder="z.B. Café Central, Hauptstraße 12" required>
                    <button type="submit" class="submit-btn">Date anfragen</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
