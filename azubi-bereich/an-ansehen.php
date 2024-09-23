<?php

// Auf dieser Seite kann ein angemeldeter Auszubildender einen Ausbildungsnachweis von sich ansehen

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

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, der angesehen werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde, und ob der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

// die SQL-Abfrage, um alle nötigen Daten des anzusehenden Ausbildungsnachweises zu bekommen:
$sql = "SELECT `Startdatum`, `Enddatum`, `Ausbildungsjahr`, `AktivitätMontag`, `AktivitätDienstag`, `AktivitätMittwoch`,
               `AktivitätDonnerstag`, `AktivitätFreitag`, `AzubiAnmerkungen`, `AusbilderAnmerkungen`, `Status`
        FROM ausbildungsnachweis
        WHERE AusbildungsnachweisID = ?;";
$result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

$row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile

$from_date = $row["Startdatum"];
$to_date = $row["Enddatum"];
$year_of_training = $row["Ausbildungsjahr"];
$activity = [$row["AktivitätMontag"], $row["AktivitätDienstag"], $row["AktivitätMittwoch"], $row["AktivitätDonnerstag"], $row["AktivitätFreitag"]];
$trainee_notes = $row["AzubiAnmerkungen"];
$trainer_notes = $row["AusbilderAnmerkungen"];
$status = $row["Status"];

$date_range = date_range($from_date, $to_date); // die Ausbildungswoche im Format „TT.[MM.][JJJJ] bis TT.MM.JJJJ“
$from_weekday = date("w", strtotime($from_date)) - 1; // der Wochentag des Startdatums (0 = Montag)
$to_weekday = date("w", strtotime($to_date)) - 1; // der Wochentag des Enddatums (0 = Montag)

// Die Anmerkungen des Ausbilders sollen angezeigt werden, wenn er welche eingetragen hat oder der Ausbildungsnachweis schon geprüft worden ist:
$show_trainer_notes = $trainer_notes || $status != "Eingereicht";

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Ausbildungsnachweis ansehen</title>
        <script src="../quill.js"></script> <!-- das Quill-Skript inkludieren -->
        <link rel="stylesheet" href="../styles.css">
        <link href="../quill.snow.css" rel="stylesheet"> <!-- das Quill-Stylesheet inkludieren -->
        <!-- Style-Anpassungen für die Quill-Editoren: -->
        <style>
        .ql-editor {
            min-height: 200px; /* die Mindesthöhe in Pixeln */
            font-size: medium; /* mittlere Schriftgröße */
        }
        </style>
    </head>
    <body>
        <!-- Ein Container, in der der Benutzername des angemeldeten Benutzers sowie eine Schaltfläche zum Abmelden angezeigt werden: -->
        <div class="user-info">
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = '../abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1> <!-- die Überschrift der Seite mit der Datumsspanne und dem Ausbildungsjahr -->
            Ausbildungsnachweis <?= htmlspecialchars($date_range) ?> (<?= htmlspecialchars($year_of_training) ?>. Ausbildungsjahr)
            <?php
            if ($status !== "Eingereicht") { // wenn der Ausbildungsnachweis geprüft worden ist
                echo " – " . strtolower($status); // den Status mit einem Gedankenstrich an die Überschrift anhängen
            }
            ?>
        </h1>
        <div class="tabs"> <!-- die Tabs mit den Wochentagen und Anmerkungen -->
            <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
                <?php $wd = WEEKDAYS[$i]; ?>
                <button class="tablink" onclick="openTab(event, 'tab-<?= htmlspecialchars($wd->en) ?>')"><?= htmlspecialchars($wd->de) ?></button>
            <?php endfor; ?>
            <?php if ($show_trainer_notes): ?>
                <button class="tablink" onclick="openTab(event, 'tab-trainee-notes')">Anmerkungen von Ihnen</button>
                <button class="tablink" onclick="openTab(event, 'tab-trainer-notes')">Anmerkungen vom Ausbilder</button>
            <?php else: ?>
                <button class="tablink" onclick="openTab(event, 'tab-trainee-notes')">Anmerkungen</button>
            <?php endif; ?>
        </div>
        <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
            <?php $wd = WEEKDAYS[$i]; ?>
            <div id="tab-<?= htmlspecialchars($wd->en) ?>" class="tabcontent">
                <div id="editor-<?= htmlspecialchars($wd->en) ?>"><?= $activity[$i] ?></div>
                <input type="hidden" name="activity-<?= htmlspecialchars($wd->en) ?>" id="activity-<?= htmlspecialchars($wd->en) ?>">
            </div>
        <?php endfor; ?>
        <div id="tab-trainee-notes" class="tabcontent">
            <div id="editor-trainee-notes"><?= $trainee_notes ?></div>
        </div>
        <?php if ($show_trainer_notes): ?>
            <div id="tab-trainer-notes" class="tabcontent">
                <div id="editor-trainer-notes"><?= $trainer_notes ?></div>
            </div>
        <?php endif; ?>
        <button onclick="location.href = 'an-liste';" class="blue-button" style="margin-top: 15px;">Zurück</button>
        <script>
        // Diese Funktion öffnet den Tab mit dem übergebenen Namen:
        function openTab(event, tabName) {
            var tablinks = document.getElementsByClassName("tablink");
            var tabcontent = document.getElementsByClassName("tabcontent");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            for (let i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            event.currentTarget.className += " active";
            document.getElementById(tabName).style.display = "block";
        }

        // Diese Funktion öffnet den ersten Tab (meistens „Montag“):
        function openFirstTab() {
            var tablinks = document.getElementsByClassName("tablink");
            tablinks[0].style.display = "block";
            tablinks[0].className += " active";
            document.getElementsByClassName("tabcontent")[0].style.display = "block";
        }

        openFirstTab(); // beim Öffnen der Seite den ersten Tab öffnen

        // Quill-Editoren anlegen:
        <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
            <?php $wd = WEEKDAYS[$i]; ?>
            new Quill("#editor-<?= htmlspecialchars($wd->en) ?>", {theme: "snow", readOnly: true, modules: {toolbar: false}, placeholder: "Sie haben für den <?= htmlspecialchars($wd->de) ?> nichts eingetragen."});
        <?php endfor; ?>
        new Quill("#editor-trainee-notes", {theme: "snow", readOnly: true, modules: {toolbar: false}, placeholder: "Sie haben keine Anmerkungen eingetragen."});
        <?php if ($show_trainer_notes): ?>
            new Quill("#editor-trainer-notes", {theme: "snow", readOnly: true, modules: {toolbar: false}, placeholder: "Der Ausbilder hat keine Anmerkungen eingetragen."});
        <?php endif; ?>
        </script>
    </body>
</html>