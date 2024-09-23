<?php

// Diese Seite zeigt dem Benutzer eine Fehlermeldung an, die aus einem Titel und einer Beschreibung besteht.

http_response_code(403); // Fehlercode

?>


<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="/wanda/styles.css">
        <title>Fehler</title>
    </head>
    <body>
        <!-- Ein Container, in der der Benutzername des angemeldeten Benutzers sowie eine Schaltfläche zum Abmelden angezeigt werden: -->
        <div class="user-info">
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = '/wanda/abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1><?= htmlspecialchars($error) ?></h1> <!-- den Fehler als Überschrift anzeigen -->
        <p><?= htmlspecialchars($description) ?></p> <!-- die Beschreibung als Paragraphen anzeigen -->
        <button onclick="location.href = '/wanda/zur-an-liste';" class="blue-button">Zu den Ausbildungsnachweisen</button>
    </body>
</html>