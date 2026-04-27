<?php
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
    <title>Registrieren - Ace of Dates</title>
    <link rel="stylesheet" href="../stylings/register.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="register-card">
        <h2>Registrieren</h2>
        <?php if (!empty($message)): ?>
            <div class="register-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" placeholder="deine@email.de" required>

            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" placeholder="********" required>

            <label for="confirm_password">Passwort wiederholen</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="********" required>

            <button type="submit">Registrieren</button>
        </form>
        <div class="login-link">
            Bereits registriert?
            <a href="login.php">Jetzt einloggen</a>
        </div>
    </div>
   <a href="../index.html"><img class="mascot" src="../img/maskotchen.png" alt="Maskottchen"></a>
</body>
</html>
