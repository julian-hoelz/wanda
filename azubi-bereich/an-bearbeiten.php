<?php

// Auf dieser Seite kann ein angemeldeter Auszubildender einen ausgewählten Ausbildungsnachweis bearbeiten

include "../util.php"; // die Datei „util.php“ im Überordner inkludieren

/**
 * diese Funktion gibt die Aktivität für einen bestimmten Tag zurück, die mit der Methode POST übermittelt wurde.
 * @param string $name der Name der Aktivität
 * @return string|null die Aktivität, die mit der Methode POST übermittelt wurde, wenn sie gesetzt ist, andernfalls `null`
 */
function get_activity(string $name): string|null {
    if (isset($_POST[$name])) {
        return $_POST[$name];
    } else {
        return null;
    }
}

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

$an_id = $_GET["an-id"]; // die ID des Ausbildungsnachweises, der bearbeitet werden soll

// prüfen, ob es einen Ausbildungsnachweis mit der gegebenen ID gibt, der nicht gelöscht wurde, und ob der angemeldete Auszubildende Zugriff darauf hat:
check_an_exists_not_deleted_and_access($conn, $an_id);

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// Speichern:

$last_save = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $activity_monday = get_activity("activity-monday");
    $activity_tuesday = get_activity("activity-tuesday");
    $activity_wednesday = get_activity("activity-wednesday");
    $activity_thursday = get_activity("activity-thursday");
    $activity_friday = get_activity("activity-friday");
    $trainee_notes = $_POST["trainee-notes"];

    // die SQL-Anweisung, um die Aktivitäten und Anmerkungen in der Datenbank zu speichern:
    $sql = "UPDATE `ausbildungsnachweis`
            SET `AktivitätMontag` = ?, `AktivitätDienstag` = ?, `AktivitätMittwoch` = ?, `AktivitätDonnerstag` = ?,
                `AktivitätFreitag` = ?, `AzubiAnmerkungen` = ?, `Status` = 'In Bearbeitung'
            WHERE AusbildungsnachweisID = ?;";
    // die SQL-Anweisung ausführen:
    exec_sql($conn, $sql, "ssssssi", $activity_monday, $activity_tuesday, $activity_wednesday, $activity_thursday, $activity_friday, $trainee_notes, $an_id);

    $last_save = "Zuletzt gespeichert um " . date("H:i") . " Uhr";
}

// die SQL-Abfrage, um alle nötigen Daten des zu bearbeitenden Ausbildungsnachweises zu bekommen:
$sql = "SELECT `Startdatum`, `Enddatum`, `Ausbildungsjahr`, `AktivitätMontag`, `AktivitätDienstag`, `AktivitätMittwoch`,
               `AktivitätDonnerstag`, `AktivitätFreitag`, `AzubiAnmerkungen`, `AusbilderAnmerkungen`, `Status`
        FROM ausbildungsnachweis
        WHERE AusbildungsnachweisID = ?;";
$result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

$row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
$status = $row["Status"];

if ($status !== "In Bearbeitung") { // wenn der Ausbildungsnachweis nicht in Bearbeitung ist
    $error = "Ausbildungsnachweis nicht in Bearbeitung"; // Titel auf der Fehlerseite
    $description = "Dieser Ausbildungsnachweis ist nicht in Bearbeitung. Sie können ihn folglich nicht bearbeiten."; // Beschreibung auf der Fehlerseite
    include "../fehlerseite.php"; // Fehlerseite anzeigen
    exit(); // die Ausführung dieses Skripts stoppen
}

$from_date = $row["Startdatum"];
$to_date = $row["Enddatum"];
$year_of_training = $row["Ausbildungsjahr"];
$activity = [$row["AktivitätMontag"], $row["AktivitätDienstag"], $row["AktivitätMittwoch"],
             $row["AktivitätDonnerstag"], $row["AktivitätFreitag"]];
$trainee_notes = $row["AzubiAnmerkungen"];
$trainer_notes = $row["AusbilderAnmerkungen"];

$date_range = date_range($from_date, $to_date); // die Ausbildungswoche im Format „TT.[MM.][JJJJ] bis TT.MM.JJJJ“
$from_weekday = date("w", strtotime($from_date)) - 1; // der Wochentag des Startdatums (0 = Montag)
$to_weekday = date("w", strtotime($to_date)) - 1; // der Wochentag des Enddatums (0 = Montag)

$dt_from_date = new DateTime($from_date); // das Startdatum als DateTime-Objekt
$dt_to_date = new DateTime($to_date); // das Enddatum als DateTime-Objekt

$dt_now = new DateTime(); // aktuelle Datum und Uhrzeit
$dt_now->setTime(0, 0); // um 00:00 Uhr

if ($dt_from_date <= $dt_now && $dt_now <= $dt_to_date) { // wenn das aktuelle Datum innerhalb der Datumsspanne (der Ausbildungswoche) liegt
    $current_weekday = date("N") - 1; // der aktuelle Wochentag
    $tab_to_open = $current_weekday - $from_weekday; // der Tab, der geöffnet werden soll
} else { // wenn das aktuelle Datum außerhalb der Datumsspanne (der Ausbildungswoche) liegt
    $tab_to_open = 0; // der erste Tab (Montag) soll geöffnet werden
}

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
        <h1>Ausbildungsnachweis <?= htmlspecialchars($date_range) ?> (<?= htmlspecialchars($year_of_training) ?>. Ausbildungsjahr)</h1>
        <!-- das Formular mit den Quill-Editoren, die den entsprechenden Tabs zugeordnet sind: -->
        <form method="post">
            <div class="tabs"> <!-- die Tabs mit den Wochentagen und Anmerkungen -->
                <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
                    <?php $wd = WEEKDAYS[$i]; ?>
                    <button type="button" class="tablink" onclick="openTab(event, 'tab-<?= htmlspecialchars($wd->en) ?>')"><?= htmlspecialchars($wd->de) ?></button>
                <?php endfor; ?>
                <?php if ($trainer_notes): ?>
                    <button type="button" class="tablink" onclick="openTab(event, 'tab-trainee-notes')">Anmerkungen von Ihnen</button>
                    <button type="button" class="tablink" onclick="openTab(event, 'tab-trainer-notes')">Anmerkungen vom Ausbilder</button>
                <?php else: ?>
                    <button type="button" class="tablink" onclick="openTab(event, 'tab-trainee-notes')">Anmerkungen</button>
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
                <input type="hidden" name="trainee-notes" id="trainee-notes">
            </div>
            <?php if ($trainer_notes): ?>
                <div id="tab-trainer-notes" class="tabcontent">
                    <div id="editor-trainer-notes"><?= $trainer_notes ?></div>
                </div>
            <?php endif; ?>
            <button type="submit" class="blue-button" style="margin-top: 15px;">Speichern</button>
            <?php if ($last_save): ?>
                <?= htmlspecialchars($last_save) ?>
            <?php else: ?>
                Noch nicht gespeichert
            <?php endif; ?>
            <br>
            <button type="button" onclick="location.href = 'an-liste'" class="orange-button" style="margin-top: 5px;">Zurück</button>
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

        // beim Öffnen der Seite den Tab des aktuellen Tages oder den ersten Tab öffnen:
        var tablinks = document.getElementsByClassName("tablink");
        var tab_to_open = <?= htmlspecialchars($tab_to_open) ?>;
        tablinks[tab_to_open].style.display = "block";
        tablinks[tab_to_open].className += " active";
        document.getElementsByClassName("tabcontent")[tab_to_open].style.display = "block";

        // Quill-Editoren anlegen:
        <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
            <?php $wd = WEEKDAYS[$i]; ?>
            var quill_<?= htmlspecialchars($wd->en) ?> = new Quill("#editor-<?= htmlspecialchars($wd->en) ?>", {theme: "snow", placeholder: "Betriebliche Tätigkeiten, Unterweisungen bzw. überbetriebliche Unterweisungen (z. B. im Handwerk), betrieblicher Unterricht, sonstige Schulungen, Themen des Berufsschulunterrichts"});
        <?php endfor; ?>
        var quill_trainee_notes = new Quill("#editor-trainee-notes", {theme: "snow", placeholder: "Hier ist Platz für Ihre Anmerkungen"});
        <?php if ($trainer_notes): ?>
            new Quill("#editor-trainer-notes", {theme: "snow", readOnly: true, modules: {toolbar: false}});
        <?php endif; ?>

        // Formular:
        var form = document.querySelector("form");
        form.onsubmit = function() {
            <?php for ($i = $from_weekday; $i <= $to_weekday; $i++): ?>
                <?php $wd = WEEKDAYS[$i]; ?>
                document.getElementById("activity-<?= htmlspecialchars($wd->en) ?>").value = quill_<?= htmlspecialchars($wd->en) ?>.root.innerHTML;
            <?php endfor; ?>
            document.getElementById("trainee-notes").value = quill_trainee_notes.root.innerHTML;
        }
        </script>
    </body>
</html>