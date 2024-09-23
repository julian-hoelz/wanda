<?php

// Dieses Skript speichert die Änderungen des angemeldeten Ausbilders an einem Ausbildungsnachweis.

include "../util.php"; // die Datei „util.php“ im Überordner inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// prüfen, ob der Benutzer ein Ausbilder ist und somit Zugriff auf diese Seite hat.
// Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet:
check_page_access($conn, "Ausbilder");

// Diese Variablen müssen gesetzt sein, sonst wird die Verbindung geschlossen und der Benutzer zu seiner Ausbildungsnachweis-Liste weitergeleitet:
if (!(isset($_GET["an-id"]) && isset($_POST["trainer-notes"]) && isset($_POST["action"]))) {
    $conn->close();
    header("Location: an-liste");
    exit();
}

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, an dem Änderungen gespeichert werden sollen
// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde, und ob der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

$trainer_notes = $_POST["trainer-notes"]; // die vom Ausbilder eingetragenen Anmerkungen

// die SQL-Anweisung, um die Anmerkungen zu speichern:
$sql = "UPDATE `ausbildungsnachweis`
        SET `AusbilderAnmerkungen` = ?
        WHERE `AusbildungsnachweisID` = ?;";
exec_sql($conn, $sql, "si", $trainer_notes, $an_id); // die SQL-Anweisung ausführen

$new_status = $_POST["action"]; // der neue Status – je nachdem, welche Schaltfläche gedrückt wurde

if ($new_status) {
    // die SQL-Anweisung, um den neuen Status zu speichern:
    $sql = "UPDATE `ausbildungsnachweis`
            SET `Status` = ?
            WHERE `AusbildungsnachweisID` = ?;";
    exec_sql($conn, $sql, "si", $new_status, $an_id); // die SQL-Anweisung ausführen
}

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

header("Location: an-liste"); // den Benutzer auf die Seite mit seiner Ausbildungsnachweis-Liste weiterleiten

?>