<?php
session_start();
header('Content-Type: application/json');

// Prüfe ob Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Prüfe ob POST-Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Lese JSON-Daten
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['partner_id']) || !isset($input['reaction'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];
$partner_id = intval($input['partner_id']);
$reaction = $input['reaction']; // 'like' oder 'dislike'

// Validiere Reaction
if ($reaction !== 'like' && $reaction !== 'dislike') {
    echo json_encode(['success' => false, 'error' => 'Invalid reaction']);
    exit;
}

// Datenbankverbindung
$host = "db_server";
$dbname = "aceofdates";
$username = "aceofdates";
$password = "123";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Speichere Reaction in der Datenbank
try {
    // Prüfe ob bereits eine Reaction existiert
    $check = $conn->prepare("SELECT id FROM user_reactions WHERE user_id = ? AND partner_id = ?");
    $check->bind_param("ii", $user_id, $partner_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Update existierende Reaction
        $stmt = $conn->prepare("UPDATE user_reactions SET reaction = ? WHERE user_id = ? AND partner_id = ?");
        $stmt->bind_param("sii", $reaction, $user_id, $partner_id);
    } else {
        // Neue Reaction
        $stmt = $conn->prepare("INSERT INTO user_reactions (user_id, partner_id, reaction) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $partner_id, $reaction);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save reaction']);
    }

    $stmt->close();
    $check->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
