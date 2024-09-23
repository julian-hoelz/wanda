<?php

// Dieses Skript löscht einen Ausbildungsnachweis, indem dessen Aktivitätsstatus auf 0 gesetzt wird.

include "util.php"; // die Datei „util.php“ inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Benutzer auf die Anmeldeseite weiterleiten

if (!isset($_GET["an-id"])) { // wenn kein Argument „an-id“ in der URL gesetzt ist
    header("Location: zur-an-liste"); // wird der Benutzer zu seiner Ausbildungsnachweis-List weitergeleitet
    exit(); // und die Ausführung dieses Skripts wird beendet
}

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

$an_id = $_GET["an-id"]; // das Argument „an-id“ aus der URL laden

// prüfen, ob ein Ausbildungnachweis mit der gegebenen URL existiert und nicht gelöscht wurde und ob
// der angemeldete Benutzer Zugriff darauf hat. Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet:
check_an_exists_not_deleted_and_access($conn, $an_id);

// Diese SQL-Anweisung setzt den Ausbildungsnachweis mit der gegebenen ID inaktiv, wodurch er als gelöscht gilt:
$sql = "UPDATE `ausbildungsnachweis` SET `Aktiv` = 0 WHERE `AusbildungsnachweisID` = ?";
exec_sql($conn, $sql, "i", $an_id); // die SQL-Anweisung ausführen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

header("Location: zur-an-liste"); // den Benutzer zu seiner Ausbildungsnachweis-Liste weiterleiten

?>