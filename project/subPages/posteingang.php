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

$query = $conn->prepare("SELECT dr.id, dr.activity, dr.date, dr.time, dr.location, dr.status, u.id AS requester_id, prof.personality, prof.image_path, up.age, up.gender, prof.hobby FROM date_requests dr JOIN users u ON dr.requester_id = u.id LEFT JOIN profiles prof ON u.id = prof.id LEFT JOIN user_preferences up ON u.id = up.id WHERE dr.receiver_id = ? AND dr.status = 'pending' ORDER BY dr.created_at DESC");
$query->bind_param('i', $user_id);
$query->execute();
$requests_result = $query->get_result();
$requests = [];
while ($row = $requests_result->fetch_assoc()) {
    $requests[] = $row;
}
$query->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posteingang - Ace of Dates</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../stylings/home.css">
</head>
<body>
    <div class="home-container">
        <nav class="navbar">
            <a href="../index.html" class="nav-logo"><img src="../img/blinder_logo.png" alt="Logo"></a>
            <div class="nav-menu">
                <a href="home.php" class="nav-item">Date Suche</a>
                <a href="posteingang.php" class="nav-item active">Posteingang</a>
                <a href="profile_view.php" class="nav-item">Profil</a>
                <a href="next_date.php" class="nav-item">Next Date</a>
            </div>
        </nav>

        <main class="page-shell">
            <div class="inbox-card">
                <h1>Posteingang</h1>
                <?php if (count($requests) === 0): ?>
                    <p>Keine neuen Anfragen vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card" id="request-<?php echo $request['id']; ?>">
                            <div class="request-header">
                                <img src="<?php echo htmlspecialchars($request['image_path'] ?? '../img/profile_placeholder.png'); ?>" alt="Anfragender" class="request-avatar">
                                <div>
                                    <h2><?php echo htmlspecialchars($request['personality'] ?? 'Unbekannt'); ?></h2>
                                    <p><?php echo htmlspecialchars($request['age'] ?? ''); ?> Jahre · <?php echo htmlspecialchars($request['gender'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="request-details">
                                <p><strong>Art des Treffens:</strong> <?php echo htmlspecialchars($request['activity']); ?></p>
                                <p><strong>Datum:</strong> <?php echo htmlspecialchars($request['date']); ?> um <?php echo htmlspecialchars(substr($request['time'],0,5)); ?></p>
                                <p><strong>Ort:</strong> <?php echo htmlspecialchars($request['location']); ?></p>
                            </div>
                            <div class="request-actions">
                                <button class="accept-btn" onclick="respondToRequest(<?php echo $request['id']; ?>, 'accept')">Annehmen</button>
                                <button class="reject-btn" onclick="respondToRequest(<?php echo $request['id']; ?>, 'reject')">Ablehnen</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
        function respondToRequest(requestId, response) {
            fetch('../api/date_response.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ request_id: requestId, response: response })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const item = document.getElementById('request-' + requestId);
                    if (item) {
                        item.remove();
                    }
                } else {
                    alert(data.error || 'Fehler beim Verarbeiten der Aktion.');
                }
            })
            .catch(() => alert('Fehler beim Verbinden mit dem Server.'));
        }
    </script>
</body>
</html>
