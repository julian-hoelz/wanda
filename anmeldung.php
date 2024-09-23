<?php

/*
 * Auf dieser Seite können sich alle registrierten Benutzer anmelden.
 * Sie verfügt über zwei Eingabefelder mit den Beschriftungen „Benutzername“ und „Passwort“
 * und eine Schaltfläche „Anmelden“.
 */

include "util.php"; // die Datei „util.php“ inkludieren

session_start();

if (isset($_SESSION["username"])) { // Wenn bereits ein Benutzer angemeldet ist
    header("Location: zur-an-liste"); // ihn zu seinen Ausbildungsnachweisen weiterleiten
    exit(); // und die Ausführung dieses Skripts stoppen
}

$error = ""; // Variable für Fehlermeldungen

$conn = connect_to_wanda_db(); // Mit Wanda-Datenbank verbinden

// Diese Bedingung ist wahr, wenn der Benutzer mit einem Klick auf die Schaltfläche „Anmelden“ das Anmeldeformular abgesendet hat:
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUsername = strtolower($_POST["username"]); // der eingegebene Benutzername in Kleinbuchstaben
    $inputPassword = $_POST["password"]; // das eingegebene Passwort
    $correctHashedPassword = get_correct_hashed_password($conn, $inputUsername);
    if (is_null($correctHashedPassword)) { // wenn kein Benutzer mit dem eingegebenen Benutzernamen gefunden wurde
        $error = "Der Benutzer wurde nicht gefunden."; // eine entsprechende Fehlermeldung setzen
    } else if (!password_verify($inputPassword, $correctHashedPassword)) { // wenn das eingegebene Passwort inkorrekt ist
        $error = "Das Passwort ist nicht korrekt."; // eine entsprechende Fehlermeldung setzen
    } else { // Es wurde ein Benutzer mit dem eingegebenen Benutzernamen gefunden und das eingegebene Passwort ist korrekt
        $_SESSION["username"] = $inputUsername; // den Benutzernamen in der Sitzung speichern
        // die ID des sich anmeldenden Benutzers ermitteln und in der Sitzung speichern:
        $_SESSION["user-id"] = get_user_id($conn, $inputUsername);
        $passwordSet = get_password_set($conn); // ermitteln, ob der sich anmeldende Benutzer bereits ein eigenes Passwort gesetzt hat
        $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
        // wenn der Benutzer bereits ein eigenes Passwort gesetzt hat, ihn zu seiner Ausbildungsnachweis-Liste weiterleiten;
        // wenn nicht, ihn zur Passwort-setzen-Seite weiterleiten:
        header($passwordSet ? "Location: zur-an-liste" : "Location: passwort-setzen");
        exit(); // die Ausführung dieses Skripts stoppen
    }
}

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="styles.css">
        <title>Anmeldung</title>
    </head>
    <body>
        <h1>Anmeldung</h1>
        <?php if ($error): ?> <!-- wenn es eine Fehlermeldung gibt -->
            <p style="color: red;"><?= htmlspecialchars($error) ?></p> <!-- diese in Rot anzeigen -->
        <?php endif; ?>
        <form method="post"> <!-- das Anmeldeformular -->
            Benutzername:<br>
            <input type="text" name="username" style="margin-bottom: 10px;" required><br>
            Passwort:<br>
            <input type="password" name="password" style="margin-bottom: 15px;" required><br>
            <button type="submit" class="blue-button">Anmelden</button>
        </form>
    </body>
</html>