<?php

// Dieses Skript toggelt den Status eines Ausbildungsnachweises zwischen „In Bearbeitung“ und „Eingereicht“

include "../util.php"; // die Datei „util.php“ im Überordner inkludieren

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

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, dessen Status getoggelt werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, er nicht gelöscht wurde und der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

// die SQL-Abfrage, um den Status des Ausbildungsnachweises zu bekommen
$sql = "SELECT `Status`
        FROM `ausbildungsnachweis`
        WHERE `AusbildungsnachweisID` = ?;";
$result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen
$row = $result->fetch_assoc(); // die erste (und einzige) Ergebniszeile
$status = $row["Status"];

$new_status = $status == "In Bearbeitung" ? "Eingereicht" : "In Bearbeitung"; // getoggelter Status

// die SQL-Anweisung, um den Status zu ändern
$sql = "UPDATE `ausbildungsnachweis`
        SET `Status` = ?
        WHERE `AusbildungsnachweisID` = ?;";
exec_sql($conn, $sql, "si", $new_status, $an_id); // die SQL-Anweisung ausführen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

header("Location: an-liste"); // den Benutzer zu seinen Ausbildungsnachweisen weiterleiten

?>