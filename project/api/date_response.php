<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['request_id']) || !isset($input['response'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = intval($input['request_id']);
$response = $input['response'];

if ($response !== 'accept' && $response !== 'reject') {
    echo json_encode(['success' => false, 'error' => 'Invalid response']);
    exit;
}

$host = "db_server";
$dbname = "aceofdates";
$username = "aceofdates";
$password = "123";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
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

$stmt = $conn->prepare("SELECT requester_id, receiver_id FROM date_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->bind_param('ii', $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['success' => false, 'error' => 'Anfrage nicht gefunden oder bereits bearbeitet']);
    $conn->close();
    exit;
}

$status = $response === 'accept' ? 'accepted' : 'rejected';
$update = $conn->prepare("UPDATE date_requests SET status = ? WHERE id = ?");
$update->bind_param('si', $status, $request_id);
if (!$update->execute()) {
    echo json_encode(['success' => false, 'error' => 'Konnte Anfrage nicht aktualisieren']);
    $update->close();
    $conn->close();
    exit;
}
$update->close();

if ($response === 'reject') {
    $requester_id = $request['requester_id'];
    $receiver_id = $request['receiver_id'];

    $check = $conn->prepare("SELECT id FROM user_reactions WHERE user_id = ? AND partner_id = ?");
    $check->bind_param('ii', $receiver_id, $requester_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE user_reactions SET reaction = 'dislike' WHERE user_id = ? AND partner_id = ?");
        $stmt->bind_param('ii', $receiver_id, $requester_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO user_reactions (user_id, partner_id, reaction) VALUES (?, ?, 'dislike')");
        $stmt->bind_param('ii', $receiver_id, $requester_id);
    }
    $stmt->execute();
    $stmt->close();
    $check->close();

    $check2 = $conn->prepare("SELECT id FROM user_reactions WHERE user_id = ? AND partner_id = ?");
    $check2->bind_param('ii', $requester_id, $receiver_id);
    $check2->execute();
    $check2->store_result();
    if ($check2->num_rows > 0) {
        $stmt2 = $conn->prepare("UPDATE user_reactions SET reaction = 'dislike' WHERE user_id = ? AND partner_id = ?");
        $stmt2->bind_param('ii', $requester_id, $receiver_id);
    } else {
        $stmt2 = $conn->prepare("INSERT INTO user_reactions (user_id, partner_id, reaction) VALUES (?, ?, 'dislike')");
        $stmt2->bind_param('ii', $requester_id, $receiver_id);
    }
    $stmt2->execute();
    $stmt2->close();
    $check2->close();
}

echo json_encode(['success' => true]);
$conn->close();
