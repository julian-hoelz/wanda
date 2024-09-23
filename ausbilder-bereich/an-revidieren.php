<?php

// Dieses Skript aktualisiert die Datenbanktabelle „ausbildungsnachweis“.
// Es setzt den Status des in der URL übergebenen Ausbildungsnachweises auf „Eingereicht“.

include "../util.php"; // die Datei „util.php“ im Überordner inkludieren

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

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, der revidiert werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde, und ob der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

// die SQL-Anweisung, mit der der Status des Ausbildungsnachweises auf „Eingereicht“ gesetzt wird:
$sql = "UPDATE `ausbildungsnachweis`
        SET `Status` = 'Eingereicht'
        WHERE AusbildungsnachweisID = ?;";
exec_sql($conn, $sql, "i", $an_id); // die SQL-Anweisung ausführen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

header("Location: an-liste"); // den Benutzer zu seiner Ausbildungsnachweis-Liste weiterleiten

?>