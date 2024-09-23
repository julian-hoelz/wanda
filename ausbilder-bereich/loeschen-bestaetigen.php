<?php

// Auf dieser Seite wird der angemeldete Ausbilder gefragt, ob er einen Ausbildungsnachweis wirklich löschen möchte

include "../util.php"; // die Datei „util.php“ im Überordner inkludieren

/**
 * Diese Funktion wandelt einen Namen in den Genitiv um. Wenn er auf -s oder -z endet, wird ein Apostroph angehängt, ansonsten ein -s.
 * Auch bei Namen auf -x (und in weiteren Fällen) müsste ein Apostroph angehängt werden, aber in der Ausbildungsgruppe gibt es niemanden, der entsprechend heißt.
 * @param string $name der umzuwandelnde Name
 * @return string den umgewandelten Namen
 */
function genitive_name(string $name) {
    if (str_ends_with($name, "s") || str_ends_with($name, "z")) { // wenn der Name auf -s oder -z endet
        return $name . "’"; // ein Apostroph anhängen
    } else {
        return $name . "s"; // sonst ein -s anhängen
    }
}

/**
 * Diese Funktion generiert die Warnung, die dem angemeldeten Ausbilder angezeigt wird.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param string $recorder_full_name der volle Name des Schreibers des Ausbildungsnachweises
 * @return string die generierte Warnung
 */
function warning(mysqli $conn, string $recorder_full_name): string {
    $date_range = date_range_current_an($conn); // die Datumsspanne des aktuellen Ausbildungsnachweises
    if ($recorder_full_name == "Demo-Auszubildender") { // wenn der Schreiber der Demo-Auszubildende ist
        return "Möchten Sie den Ausbildungsnachweis des Demo-Auszubildenden vom $date_range wirklich löschen?";
    } else { // wenn der Schreiber nicht der Demo-Auszubildende ist
        $genitive_name = genitive_name($recorder_full_name); // die passende Genitivendung anhängen
        return "Möchten Sie $genitive_name Ausbildungsnachweis vom $date_range wirklich löschen?";
    }
}

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// prüfen, ob der Benutzer ein Ausbilder ist und somit Zugriff auf diese Seite hat.
// Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet:
check_page_access($conn, "Ausbilder");

if (!isset($_GET["an-id"])) { // wenn „an-id“ in der URL nicht gesetzt ist
    $conn->close(); // wird die Verbindung geschlossen
    header("Location: an-liste"); // wird der Benutzer zu seiner Ausbildungsnachweis-Liste weitergeleitet
    exit(); // und wird die Ausführung dieses Skripts beendet
}

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, bei dem gefragt werden soll, ob er wirklich gelöscht werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde, und ob der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

$recorder_id = get_recorder_id($conn, $an_id); // die ID des Schreibers des Ausbildungsnachweises
$recorder_full_name = get_full_name($conn, $recorder_id); // der volle Name des Schreibers des Ausbildungsnachweises
$warning = warning($conn, $recorder_full_name); // die Warnung generieren (s. diese Funktion)

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
        <p style="margin-bottom: 30px;"><?= htmlspecialchars($warning) ?></p>
        <button onclick="location.href = '../an-loeschen?an-id=<?= htmlspecialchars($an_id) ?>';" class="orange-button">Ja, löschen</button>
        <button onclick="location.href = 'an-liste';" class="blue-button">Abbrechen</button>
    </body>
</html>