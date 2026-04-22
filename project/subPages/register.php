<?php
$host = "localhost";
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password_input = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Einfache Validierung
    if (empty($email) || empty($password_input) || empty($confirm_password)) {
        $message = "Bitte alle Felder ausfüllen.";
    } elseif ($password_input !== $confirm_password) {
        $message = "Die Passwörter stimmen nicht überein.";
    } else {
        // Prüfen ob Email bereits existiert
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Diese E-Mail ist bereits registriert.";
        } else {
            // Passwort hashen
            $password_hash = password_hash($password_input, PASSWORD_DEFAULT);

            // User speichern
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $password_hash);

            if ($stmt->execute()) {
                $message = "Registrierung erfolgreich!";
            } else {
                $message = "Fehler bei der Registrierung.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrieren - AceOfDates</title>
</head>
<body>

    <h2>Registrieren</h2>

    <?php if (!empty($message)): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>E-Mail:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Passwort:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Passwort wiederholen:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <button type="submit">Registrieren</button>
    </form>

</body>
</html>
