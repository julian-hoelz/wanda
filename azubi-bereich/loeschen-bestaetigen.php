<?php

// Auf dieser Seite wird der angemeldete Auszubildende gefragt, ob er einen Ausbildungsnachweis wirklich löschen möchte.
// Er kann auf „Ja, löschen“ oder auf „Abbrechen“ klicken

include '../util.php'; // die Datei „util.php“ im Überordner inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// prüfen, ob der Benutzer ein Auszubildender ist und somit Zugriff auf diese Seite hat.
// Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet:
check_page_access($conn, "Auszubildender");

if (!isset($_GET["an-id"])) { // wenn „an-id“ in der URL nicht gesetzt ist
    $conn->close(); // wird die Verbindung geschlossen
    header("Location: an-liste"); // wird der Benutzer zu seiner Ausbildungsnachweis-Liste weitergeleitet
    exit(); // und wird die Ausführung dieses Skripts beendet
}

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, bei dem gefragt werden soll, ob er gelöscht werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde, und ob der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

$dateRange = date_range_current_an($conn); // die Ausbildungswoche im Format „TT.[MM.][JJJJ] bis TT.MM.JJJJ“

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../styles.css">
        <title>Löschen bestätigen</title>
    </head>
    <body>
        <!-- Ein Container, in der der Benutzername des angemeldeten Benutzers sowie eine Schaltfläche zum Abmelden angezeigt werden: -->
        <div class="user-info">
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = '../abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Ausbildungsnachweis löschen</h1> <!-- die Überschrift der Seite -->
        <p style="margin-bottom: 30px;">Möchten Sie Ihren Ausbildungsnachweis vom <?= htmlspecialchars($dateRange) ?> wirklich löschen?</p>
        <button onclick="location.href = '../an-loeschen?an-id=<?= htmlspecialchars($an_id) ?>';" class="orange-button">Ja, löschen</button>
        <button onclick="location.href = 'an-liste';" class="blue-button">Abbrechen</button>
    </body>
</html>