<?php

// Diese Seite leitet den Benutzer auf die Liste mit seinen Ausbildungsnachweisen weiter.

include "util.php"; // die Datei „util.php“ inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Benutzer auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

$role = get_role($conn, $_SESSION["user-id"]); // die Rolle des angemeldeten Benutzers aus der Wanda-Datenbank abfragen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

// je nachdem, ob der angemeldet Benutzer ein Ausbilder ist oder nicht, ihn zur entsprechenden
// Seite weiterleiten:
$location = $role == "Ausbilder" ? "ausbilder-bereich/an-liste" : "azubi-bereich/an-liste";
header("Location: $location");

?>