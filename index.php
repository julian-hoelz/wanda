<?php

// Diese Seite wird aufgerufen, wenn der Benutzer nur „localhost/wanda“ in die Suchleiste eingibt.
// Je nachdem, ob ein Benutzer angemeldet ist, leitet sie den Anwender auf die entsprechende Seite weiter.

session_start();

if (isset($_SESSION["username"])) { // wenn ein Benutzer angemeldet ist
    header("Location: zur-an-liste"); // ihn zu seiner Ausbildungsnachweis-Liste weiterleiten
} else {
    header('Location: anmeldung'); // sonst ihn zur Anmeldeseite weiterleiten
}

?>