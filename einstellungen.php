<?php

// Diese Seite stellt dem angemeldeten Benutzer Einstellungen bereit.

include "util.php"; // die Datei „util.php“ inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, zur Anmeldeseite weiterleiten

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="styles.css">
        <title>Einstellungen</title>
    </head>
    <body>
        <div class="user-info"> <!-- die Info über den angemeldeten Benutzer mit einer Schaltfläche „Abmelden“ -->
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = 'abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Einstellungen</h1>
        <button onclick="location.href = 'passwort-aendern';" class="blue-button">Passwort ändern</button>
        <br>
        <button onclick="location.href = 'zur-an-liste';" class="purple-button" style="margin-top: 10px;">Zurück zu den Ausbildungsnachweisen</button>
    </body>
</html>