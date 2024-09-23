<?php

// Auf dieser Seite kann der Benutzer sein Passwort ändern

include "util.php"; // die Datei „util.php“ inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$error = ""; // Variable für eine Fehlermeldung
$confirmation_msg = ""; // Variable für eine Bestätigungsmeldung

// die eingegebenen Daten prüfen und entsprechend damit umgehen

// Diese Bedingung ist wahr, wenn der Benutzer das unten stehende Formular durch Klicken
// auf die Schaltfläche „Ausbildungsnachweis anlegen“ abgeschickt hat:
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen
    $inputCurrentPassword = $_POST["current-password"];
    $inputPassword = $_POST["new-password"];
    $inputPasswordConfirm = $_POST["new-password-confirm"];
    $correctHashedCurrentPassword = get_correct_hashed_password($conn, $_SESSION["username"]); // das korrekte, gehashte Passwort ermitteln
    if (!password_verify($inputCurrentPassword, $correctHashedCurrentPassword)) { // wenn das eingegebene aktuelle Passwort nicht mit dem korrekten übereinstimmt
        $error = "Das eingegebene Passwort ist nicht korrekt."; // Fehlermeldung setzen
    } elseif ($inputPassword != $inputPasswordConfirm) { // wenn die eingegebenen neuen Passwörter nicht übereinstimmen
        $error = "Die Passwörter stimmen nicht überein."; // Fehlermeldung setzen
    } elseif ($inputPassword == $inputCurrentPassword) { // wenn kein neues Passwort eingegeben wurde
        $error = "Bitte geben Sie ein neues Passwort ein."; // Fehlermeldung setzen
    } else { // wenn nichts Fehlerhaftes eingegeben wurde
        store_password($conn, $inputPassword); // neues Passwort in der Datenbank speichern
        $confirmation_msg = "Ihr Passwort wurde erfolgreich geändert."; // Bestätigungsmeldung setzen
    }
    $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
}

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="styles.css">
        <title>Passwort ändern</title>
    </head>
    <body>
        <div class="user-info"> <!-- die Info über den angemeldeten Benutzer mit einer Schaltfläche „Abmelden“ -->
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = 'abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Passwort ändern</h1> <!-- die Überschrift der Seite -->
        <?php if ($error): ?> <!-- wenn eine Fehlermeldung gesetzt ist -->
            <p style="color: red;"><?= htmlspecialchars($error) ?></p> <!-- diese in Rot anzeigen -->
        <?php endif; ?>
        <?php if ($confirmation_msg): ?> <!-- wenn eine Bestätigungsmeldung gesetzt ist -->
            <p style="color: green;"><?= htmlspecialchars($confirmation_msg) ?></p> <!-- diese in Grün anzeigen -->
        <?php endif; ?>
        <form method="post"> <!-- das Formular mit den Eingabefeldern -->
            Aktuelles Passwort:<br>
            <input type="password" name="current-password" style="margin-bottom: 10px;" required><br>
            Neues Passwort:<br>
            <input type="password" name="new-password" style="margin-bottom: 10px;" required><br>
            Neues Passwort bestätigen:<br>
            <input type="password" name="new-password-confirm" style="margin-bottom: 20px;" required><br>
            <!-- die Schaltfläche „Passwort ändern“ (beim Anklicken wird das Passwort geändert, falls die eingegebenen Daten korrekt sind) -->
            <button type="submit" class="blue-button">Passwort ändern</button>
        </form>
        <!-- die Schaltfläche „Zurück zu den Einstellungen“ (beim Anklicken wird der Benutzer zu den Einstellungen zurückgeleitet) -->
        <button onclick="location.href = 'einstellungen';" class="purple-button" style="margin-top: 6px;">Zurück zu den Einstellungen</button>
    </body>
</html>