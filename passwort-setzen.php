<?php

// Auf diese Seite gelangt der Benutzer, wenn er sich das erste Mal anmeldet. Hier kann er sein Passwort setzen.

include "util.php"; // die Datei „util.php“ inkludieren

/**
 * Diese Funktion prüft, ob der Benutzer schon ein eigenes Passwort gesetzt hat. Wenn ja, wird er zu einer Fehlerseite weitergeleitet, die ihm anzeigt, dass er keinen Zugriff auf die Seite hat.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @return void
 */
function checkPasswordAlreadySet(mysqli $conn) {
    $passwordAlreadySet = get_password_set($conn);
    if ($passwordAlreadySet) {
        error_page_page_access_denied(); // Fehlerseite anzeigen
        exit(); // die Ausführung dieses Skripts stoppen
    }
}

/**
 * Diese Funktion aktualisiert die Tabelle „benutzer“ in der Wanda-Datenbank. „PasswortGesetzt“ wird auf 1 (wahr) gesetzt.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @return void
 */
function updateUserTablePasswordSet(mysqli $conn) {
    $user_id = $_SESSION["user-id"];
    // die SQL-Anweisung, um „PasswortGesetzt“ auf 1 (wahr) zu setzen:
    $sql = "UPDATE `benutzer`
            SET `PasswortGesetzt` = 1
            WHERE `BenutzerID` = ?;";
    exec_sql($conn, $sql, "i", $user_id); // die SQL-Anweisung ausführen
}

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

checkPasswordAlreadySet($conn); // prüfen, ob der Benutzer bereits ein eigenes Passwort gesetzt hat. Wenn ja, wird er auf eine Fehlerseite weitergeleitet

$error = ""; // Variable für eine Fehlermeldung

// die eingegebenen Daten prüfen und entsprechend damit umgehen:

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputPassword = $_POST["password"];
    $inputPasswordConfirm = $_POST["password-confirm"];
    if ($inputPassword != $inputPasswordConfirm) { // wenn die eingegebenen Passwörter nicht übereinstimmen
        $error = "Die Passwörter stimmen nicht überein."; // entsprechende Fehlermeldung
    } elseif ($inputPassword == "Qwert123") { // wenn das eingegebene Passwort das Standardpasswort ist
        $error = "Bitte geben Sie ein neues Passwort ein."; // entsprechende Fehlermeldung
    } else { // wenn nichts Fehlerhaftes eingegeben wurde
        store_password($conn, $inputPassword); // das eingegebene Passwort speichern
        updateUserTablePasswordSet($conn); // in der Tabelle „benutzer“ speichern, dass ein eigenes Passwort gesetzt wurde
        header("Location: zur-an-liste"); // den Benutzer zu seiner Ausbildungsnachweis-Liste weiterleiten
    }
}

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

?>


<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="styles.css">
        <title>Passwort setzen</title>
    </head>
    <body>
        <div class="user-info"> <!-- die Info über den angemeldeten Benutzer mit einer Schaltfläche „Abmelden“ -->
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = 'abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Passwort setzen</h1> <!-- die Überschrift der Seite -->
        <?php if ($error): ?> <!-- wenn eine Fehlermeldung gesetzt ist -->
            <p style="color: red;"><?= htmlspecialchars($error) ?></p> <!-- diese anzeigen -->
        <?php endif; ?>
        <form method="post"> <!-- das Formular mit den Passwort-Eingabefeldern und einer Schaltfläche „Passwort setzen“ -->
            Passwort:<br>
            <input type="password" name="password" style="margin-bottom: 10px;" required><br>
            Passwort bestätigen:<br>
            <input type="password" name="password-confirm" style="margin-bottom: 20px;" required><br>
            <button type="submit" class="blue-button">Passwort setzen</button>
        </form>
    </body>
</html>