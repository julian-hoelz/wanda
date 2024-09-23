<?php

// Dieses Skript meldet den angemeldeten Benutzer ab und leitet den Anwender auf die Anmeldeseite weiter.

session_start();
session_destroy(); // alle Sitzungsdaten zerstören
header("Location: anmeldung"); // den Benutzer zur Anmeldeseite weiterleiten

?>
