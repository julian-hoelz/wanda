<?php

// Auf dieser Seite kann der Ausbilder einen Ausbildungsnachweis eines Auszubildenden ansehen.
// Wenn dieser noch nicht geprüft ist, kann er ihn außerdem annehmen oder ablehnen.

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

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, der angesehen werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde:
check_an_exists_not_deleted_and_access($conn, $an_id);

// die SQL-Abfrage, um alle nötigen Daten des anzusehenden Ausbildungsnachweises zu bekommen:
$sql = "SELECT `Startdatum`, `Enddatum`, `Ausbildungsjahr`, `AktivitätMontag`, `AktivitätDienstag`, `AktivitätMittwoch`,
               `AktivitätDonnerstag`, `AktivitätFreitag`, `AzubiAnmerkungen`, `AusbilderAnmerkungen`, `BenutzerID`, `Status`
        FROM ausbildungsnachweis
        WHERE AusbildungsnachweisID = ?;"; 
$result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen
$row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile

$recorder_full_name = get_full_name($conn, $row["BenutzerID"]); // den vollen Namen des Schreibers abfragen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

$from_date = $row["Startdatum"];
$to_date = $row["Enddatum"];
$year_of_training = $row["Ausbildungsjahr"];
$activity = [$row["AktivitätMontag"], $row["AktivitätDienstag"], $row["AktivitätMittwoch"],
             $row["AktivitätDonnerstag"], $row["AktivitätFreitag"]];
$trainee_notes = $row["AzubiAnmerkungen"];
$trainer_notes = $row["AusbilderAnmerkungen"];
$status = $row["Status"];

$date_range = date_range($from_date, $to_date); // die Ausbildungswoche im Format „TT.[MM.][JJJJ] bis TT.MM.JJJJ“
$from_weekday = date("w", strtotime($from_date)) - 1; // der Wochentag des Startdatums (0 = Montag)
$to_weekday = date("w", strtotime($to_date)) - 1; // der Wochentag des Enddatums (0 = Montag)

$of = $recorder_full_name == "Demo-Auszubildender" ? "vom Demo-Auszubildenden" : "von $recorder_full_name"; // String, der angibt, von wem der Ausbildungsnachweis ist

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Ausbildungsnachweis bearbeiten</title>
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
        <!-- die Überschrift der Seite mit der Datumsspanne und dem Ausbildungsjahr: -->
        <h1 style="margin-bottom: 0px;">Ausbildungsnachweis <?= htmlspecialchars($of) ?></h1>
        <h3 style="margin-top: 6px;">
            <!-- Unterüberschrift: die Datumsspanne und das Ausbildungsjahr: -->
            <?= htmlspecialchars($date_range) ?> (<?= htmlspecialchars($year_of_training) ?>. Ausbildungsjahr)
            <?php
            if ($status == "Angenommen" || $status == "Abgelehnt") { // wenn der Ausbildungsnachweis geprüft worden ist
                echo " – " . strtolower($status); // den Status mit einem Gedankenstrich an die Unterüberschrift anhängen
            }
            ?>
        </h3>
        <div class="tabs"> <!-- die Tabs mit den Wochentagen und Anmerkungen -->
            <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
                <?php $wd = WEEKDAYS[$i]; ?>
                <button class="tablink" onclick="openTab(event, 'tab-<?= htmlspecialchars($wd->en) ?>')"><?= htmlspecialchars($wd->de) ?></button>
            <?php endfor; ?>
            <button class="tablink" onclick="openTab(event, 'tab-trainee-notes')">Anmerkungen <?= htmlspecialchars($of) ?></button>
            <button class="tablink" onclick="openTab(event, 'tab-trainer-notes')">Anmerkungen von Ihnen</button>
        </div>
        <form action="an-speichern?an-id=<?= htmlspecialchars($an_id) ?>" method="post">
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
            <div id="tab-trainer-notes" class="tabcontent">
                <div id="editor-trainer-notes"><?= $trainer_notes ?></div>
                <input type="hidden" name="trainer-notes" id="trainer-notes">
            </div>
            <?php if ($status == "Eingereicht"): ?>
                <button type="submit" name="action" value="Angenommen" class="green-button" style="margin-top: 15px;">Annehmen</button>
                <button type="submit" name="action" value="Abgelehnt" class="orange-button">Ablehnen</button>
            <?php endif; ?>
            <button type="submit" name="action" value="" class="purple-button" style="margin-top: 15px;">Anmerkungen speichern</button>
            <button type="button" onclick="location.href = 'an-liste';" class="blue-button">Zurück</button>
        </form>
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
            new Quill("#editor-<?= htmlspecialchars($wd->en) ?>", {theme: "snow", readOnly: true, modules: {toolbar: false}, placeholder: "Der Auszubildende hat für den <?= htmlspecialchars($wd->de) ?> nichts eingetragen."});
        <?php endfor; ?>
        new Quill("#editor-trainee-notes", {theme: "snow", readOnly: true, modules: {toolbar: false}, placeholder: "Der Auszubildende hat keine Anmerkungen eingetragen."});
        var quill_trainer_notes = new Quill("#editor-trainer-notes", {theme: "snow", placeholder: "Hier ist Platz für Ihre Anmerkungen"});

        // Formular:
        var form = document.querySelector("form");
        form.onsubmit = function() {
            document.getElementById("trainer-notes").value = quill_trainer_notes.root.innerHTML;
        }
        </script>
    </body>
</html>