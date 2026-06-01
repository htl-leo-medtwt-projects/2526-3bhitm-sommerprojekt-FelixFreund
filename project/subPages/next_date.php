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

$user_id = $_SESSION['user_id'];

$upcoming = [];
$query = $conn->prepare("SELECT dr.id, dr.activity, dr.date, dr.time, dr.location, dr.requester_id, dr.receiver_id, u.id AS other_id, prof.personality, prof.image_path, up.age, up.gender, prof.hobby FROM date_requests dr JOIN users u ON dr.receiver_id = u.id LEFT JOIN profiles prof ON u.id = prof.id LEFT JOIN user_preferences up ON u.id = up.id WHERE dr.requester_id = ? AND dr.status = 'accepted' ORDER BY dr.date, dr.time");
$query->bind_param('i', $user_id);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $row['role'] = 'requester';
    $upcoming[] = $row;
}
$query->close();

$query = $conn->prepare("SELECT dr.id, dr.activity, dr.date, dr.time, dr.location, dr.requester_id, dr.receiver_id, u.id AS other_id, prof.personality, prof.image_path, up.age, up.gender, prof.hobby FROM date_requests dr JOIN users u ON dr.requester_id = u.id LEFT JOIN profiles prof ON u.id = prof.id LEFT JOIN user_preferences up ON u.id = up.id WHERE dr.receiver_id = ? AND dr.status = 'accepted' ORDER BY dr.date, dr.time");
$query->bind_param('i', $user_id);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $row['role'] = 'receiver';
    $upcoming[] = $row;
}
$query->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Next Date - Ace of Dates</title>
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
                <a href="next_date.php" class="nav-item active">Next Date</a>
            </div>
        </nav>

        <main class="page-shell">
            <div class="next-date-card">
                <h1>Anstehende Dates</h1>
                <?php if (count($upcoming) === 0): ?>
                    <p>Es sind noch keine bestätigten Dates vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($upcoming as $meeting): ?>
                        <div class="date-card">
                            <div class="request-header">
                                <img src="<?php echo htmlspecialchars($meeting['image_path'] ?? '../img/profile_placeholder.png'); ?>" alt="Partner" class="request-avatar">
                                <div>
                                    <h2><?php echo htmlspecialchars($meeting['personality'] ?? 'Unbekannt'); ?></h2>
                                    <p><?php echo htmlspecialchars($meeting['age'] ?? ''); ?> Jahre · <?php echo htmlspecialchars($meeting['gender'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="request-details">
                                <p><strong>Art des Treffens:</strong> <?php echo htmlspecialchars($meeting['activity']); ?></p>
                                <p><strong>Datum:</strong> <?php echo htmlspecialchars($meeting['date']); ?> um <?php echo htmlspecialchars(substr($meeting['time'],0,5)); ?></p>
                                <p><strong>Ort:</strong> <?php echo htmlspecialchars($meeting['location']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
