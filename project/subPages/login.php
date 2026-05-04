<?php
session_start();
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

    if (empty($email) || empty($password_input)) {
        $message = "Bitte alle Felder ausfüllen.";
    } else {
        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $password_hash);
            $stmt->fetch();
            if (password_verify($password_input, $password_hash)) {
                // Login erfolgreich: Session speichern und zu home.php leiten
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;
                header('Location: home.php');
                exit;
            } else {
                $message = "Falsches Passwort.";
            }
        } else {
            $message = "E-Mail nicht gefunden.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login - DateFinder</title>
    <link rel="stylesheet" href="../stylings/login.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div id="login_header">
        <img src="../img/blinder_logo.png" alt="Logo">
        <span id="banner">DateFinder</span>
        <div>Willkommen zurück!</div>
    </div>
    <div class="login-card">
        <?php if (!empty($message)): ?>
            <div class="login-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" placeholder="deine@email.de" required>

            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" placeholder="********" required>

            <button type="submit">Einloggen</button>
        </form>
        <div class="register-link">
            Noch kein Account?
            <a href="register.php">Jetzt registrieren</a>
        </div>
    </div>
   <a href="../index.html"><img class="mascot" src="../img/maskotchen.png" alt="Maskottchen"></a>
</body>
</html>
