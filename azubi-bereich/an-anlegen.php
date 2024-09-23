<?php

// Auf dieser Seite kann ein angemeldeter Auszubildender einen neuen Ausbildungsnachweis anlegen

include "../util.php"; // die Datei „util.php“ im Überordner inkludieren

const APPRENTICESHIP_START = new DateTime("2023-08-14"); // der Tag, an dem unsere Ausbildung begann (ein Montag)
const APPRENTICESHIP_END = new DateTime("2026-06-26"); // der Tag, an dem unsere Ausbildung endet (ein Freitag)

const SECOND_YEAR_START = new DateTime("2024-08-05"); // der Tag, an dem das zweite Ausbildungsjahr beginnt
const THIRD_YEAR_START = new DateTime("2025-08-11"); // der Tag, an dem das dritte Ausbildungsjahr beginnt

/**
 * Diese Funktion prüft zwei Daten (Startdatum und Enddatum) darauf, ob sie korrekt eine Ausbildungswoche einschließen.
 * @param DateTime $from_date das Startdatum
 * @param DateTime $to_date das Enddatum
 * @return bool `true`, wenn die Daten gültig sind; andernfalls `false`
 */
function validate_dates(DateTime $from_date, DateTime $to_date): bool {
    // 7 Ungültigkeitskriterien:
    if ($from_date > $to_date) // wenn das Startdatum nach dem Enddatum liegt
        return false; // `false` zurückgeben (die Daten sind ungültig)
    if ($from_date < APPRENTICESHIP_START) // wenn das Startdatum vor dem Ausbildungsbeginn liegt
        return false;
    if ($to_date > APPRENTICESHIP_END) // wenn das Enddatum nach dem Ausbildungsende liegt
        return false;
    $wd_from_date = date("w", $from_date->getTimestamp()); // der Wochentag (wd = weekday) des Startdatums
    if ($wd_from_date == 0 || $wd_from_date == 6) // wenn der Wochentag des Startdatums ein Samstag oder ein Sonntag ist
        return false;
    $wd_to_date = date("w", $to_date->getTimestamp()); // der Wochentag (wd = weekday) des Enddatums
    if ($wd_to_date == 0 || $wd_to_date == 6) // wenn der Wochentag des Enddatums ein Samstag oder ein Sonntag ist
        return false;
    $interval = $from_date->diff($to_date); // wie viel Zeit zwischen dem Startdatum und dem Enddatum liegt
    if ($interval->days > 4) // wenn das Enddatum mehr als 4 Tage nach dem Startdatum liegt
        return false;
    // Wenn der Wochentag des Startdatums größer ist als der Wochentag des Enddatums
    // (dies verhindert, dass zwischen den Daten ein Wochenende liegt):
    if ($wd_from_date > $wd_to_date)
        return false;
    // Wenn keines der Ungültigkeitskriterien zutrifft:
    return true; // `true` zurückgeben (die Daten sind gültig)
}

/**
 * Prüft, ob bereits ein Ausbildungsnachweis mit dem übergebenen Startdatum und Enddatum angelegt wurde.
 * @param mysqli $conn eine Verbindung zur Datenbank „wanda“
 * @param string $from_date das Startdatum
 * @param string $to_date das Enddatum
 * @return bool `true`, wenn bereits ein Ausbildungsnachweis mit dem übergebenen Startdatum und Enddatum angelegt wurde;
 *              andernfalls `false`
 */
function an_with_dates_already_created(mysqli $conn, string $from_date, string $to_date): bool {
    $user_id = $_SESSION["user-id"]; // Die ID des angemeldeten Benutzers
    // Abfrage für „Startdatum“ und „Enddatum“ aller Einträge in der Datenbank „ausbildungsnachweis“
    // des angemeldeten Benutzers, die aktiv sind (nicht gelöscht wurden):
    $sql = "SELECT `Startdatum`, `Enddatum` FROM ausbildungsnachweis WHERE `Aktiv` AND BenutzerID = ?;";
    // die Benutzer-ID in die Anweisung einfügen und diese ausführen, das Ergebnis in `$result` speichern:
    $result = exec_sql($conn, $sql, "i", $user_id);
    while ($row = $result->fetch_assoc()) { // jeden Eintrag des Ergebnisses durchlaufen
        // wenn die übergebenen Daten mit denen aus dem Eintrag übereinstimmen
        if ($row["Startdatum"] === $from_date && $row["Enddatum"] === $to_date) {
            return true; // `true` zurückgeben (es wurde bereits ein Ausbildungsnachweis mit den übergebenen Daten angelegt)
        }
    }
    // Wenn nach dem Durchlaufen jeder Zeile des Ergebnisses kein Ausbildungsnachweis mit den übergebenen Daten gefunden wurde:
    return false; // `false` zurückgeben (es wurde noch kein Ausbildungsnachweis mit den übergebenen Daten angelegt)
}

/**
 * Diese Funktion berechnet das Ausbildungsjahr, in dem das Startdatum einer Woche liegt
 * @param DateTime $from_date das Startdatum
 * @return int das Ausbildungsjahr
 */
function calc_ausbjahr(DateTime $from_date): int {
    if ($from_date < SECOND_YEAR_START) // wenn das Datum vor dem Tag liegt, an dem das zweite Ausbildungsjahr beginnt
        return 1; // Rückgabe: erstes Ausbildungsjahr
    if ($from_date < THIRD_YEAR_START) // wenn das Datum vor dem Tag liegt, an dem das dritte Ausbildungsjahr beginnt
        return 2; // Rückgabe: zweites Ausbildungsjahr
    return 3; // sonst: Rückgabe: drittes Ausbildungsjahr
}

/**
 * Diese Funktion trägt einen neuen Ausbildungsnachweis in die Tabelle „ausbildungsnachweis“ der Wanda-Datenbank ein.
 * Dabei werden Werte in die Spalten „Startdatum“, „Enddatum“, „Ausbildungsjahr“ und „BenutzerID“ geschrieben.
 * @param mysqli $conn eine Verbindung zur Wanda-Datenbank
 * @param string $from_date das Startdatum der Woche, das eingetragen werden soll
 * @param string $to_date das Enddatum der Woche, das eingetragen werden soll
 * @param int $ausbjahr das Ausbildungsjahr, das eingetragen werden soll
 * @return void die Funktion gibt nichts zurück
 */
function insert_an_into_db(mysqli $conn, string $from_date, string $to_date, int $ausbjahr): void {
    $user_id = $_SESSION["user-id"]; // Die Benutzer-ID wird aus der Sitzung geladen und in der Variable „$user_id“ gespeichert
    // Die folgende SQL-Anweisung erzeugt einen neuen Eintrag in der Tabelle „Ausbildungsnachweis“ der Wanda-Datenbank.
    // Dabei werden Werte in die Spalten „Startdatum“, „Enddatum“, „Ausbildungsjahr“ und „BenutzerID“ geschrieben.
    $sql = "INSERT INTO `ausbildungsnachweis`(`Startdatum`, `Enddatum`, `Ausbildungsjahr`, `BenutzerID`) VALUES (?, ?, ?, ?);";
    exec_sql($conn, $sql, "ssii", $from_date, $to_date, $ausbjahr, $user_id); // die Werte in die Anweisung einfügen und diese ausführen
}

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Benutzer auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// prüfen, ob der Benutzer ein Auszubildender ist und somit Zugriff auf diese Seite hat.
// Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet.
check_page_access($conn, "Auszubildender");

$error = ""; // Variable für Fehlermeldungen

// Diese Bedingung ist wahr, wenn der Benutzer das unten stehende Formular durch Klicken
// auf die Schaltfläche „Ausbildungsnachweis anlegen“ abgeschickt hat:
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // den in das Datumsfeld „from-date“ (Startdatum) eingetragenen Wert (String: „JJJJ-MM-TT“) auslesen
    // und in der Variable „$from_date“ speichern:
    $from_date = $_POST["from-date"];
    // den in das Datumsfeld „to-date“ (Enddatum) eingetragenen Wert (String: „JJJJ-MM-TT“) auslesen
    // und in der Variable „to-date“ speichern:
    $to_date = $_POST["to-date"];
    $dt_from_date = new DateTime($from_date); // das Startdatum als DateTime-Objekt
    $dt_to_date = new DateTime($to_date); // Das Enddatum als DateTime-Objekt
    // Zwei Bedingungen, die zu einer Fehlermeldung führen:
    if (!validate_dates($dt_from_date, $dt_to_date)) { // wenn die eingegebenen Daten ungültig sind
        $error = "Bitte geben Sie gültige Daten an.";
    // wenn bereits ein Ausbildungsnachweis mit den eingegebenen Daten angelegt wurde:
    } elseif (an_with_dates_already_created($conn, $from_date, $to_date)) {
        $error = "Sie haben bereits einen Ausbildungsnachweis mit diesen Daten.";
    } else { // wenn kein Fehler erkannt wurde
        $ausbjahr = calc_ausbjahr($dt_from_date); // das Ausbildungsjahr anhand des Startdatums berechnen
        insert_an_into_db($conn, $from_date, $to_date, $ausbjahr); // einen neuen Datenbankeintrag erzeugen
        $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
        header("Location: an-liste"); // den Benutzer auf die Seite „an-liste“ in diesem Unterordner weiterleiten
        exit(); // die Ausführung dieses Skripts stoppen
    }
}

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../styles.css">
        <title>Ausbildungsnachweis anlegen</title>
    </head>
    <body>
        <!-- Drei Funktionen in einem Skript: -->
        <script>
        // Diese Funktion gibt das Datum zurück, das in derselben Woche des übergebenen Datums und am gewünschten Wochentag liegt.
        function getDateOfWeek(date, desiredWeekday) {
            let givenDate = new Date(date); // eine Kopie des übergebenen Datums
            let givenWeekday = givenDate.getDay(); // der Wochentag des übergebenen Datums
            // die Differenz in Tagen, die zwischen dem Wochentag des übergebenen Datums und dem gewünschten Wochentag liegen:
            let difference = desiredWeekday - givenWeekday;
            if (givenWeekday === 0) // Wenn ein Sonntag übergeben wurde (in JavaScript beginnt die Woche am Sonntag), ...
                // dann muss die Differenz um 7 verringert werden, um den gewünschten Wochentag der tatsächlichen Woche zu bekommen
                difference -= 7;
            givenDate.setDate(givenDate.getDate() + difference); // die Differenz in Tagen zu dem Datum addieren
            return givenDate; // das geänderte Datum zurückgeben
        }

        // Diese Funktion wird aufgerufen, wenn der Benutzer die Schaltfläche „Aktuelle Woche“ anklickt.
        // Sie setzt den Montag und den Freitag der aktuellen Woche in die Datumsfelder.
        function putCurrentWeek() {
            let currentDate = new Date(); // das aktuelle Datum
            let monday = getDateOfWeek(currentDate, 1); // der Montag der aktuellen Woche
            let friday = getDateOfWeek(monday, 5); // der Freitag der aktuellen Woche
            // setzt den Montag in das Datumsfeld mit der Beschriftung „Ausbildungswoche vom:“:
            document.getElementById("from-date").valueAsDate = monday;
            // setzt den Freitag in das Datumsfeld mit der Beschriftung „Bis:“:
            document.getElementById("to-date").valueAsDate = friday;
        }

        // Diese Funktion wird aufgerufen, wenn der Benutzer ein Datum in das Datumsfeld mit der Beschriftung
        // „Ausbildungswoche vom:“ eingibt. Sie setzt den Freitag der Woche, in der das eingegebene Datum liegt,
        // in das Datumsfeld mit der Beschriftung „Bis:“, wenn es kein Samstag und kein Sonntag ist.
        function putFriday() {
            // das in das Datumsfeld mit der Beschriftung „Ausbildungswoche vom:“ eingegebene Datum:
            let fromDate = document.getElementById("from-date").valueAsDate;
            let fromDateWeekday = fromDate.getDay(); // der Wochentag des eingegebenen Datums
            if (fromDateWeekday === 0 || fromDateWeekday === 6) // Wenn das eingegebene Datum ein Samstag oder ein Sonntag ist, ...
                return; // ... ist das eingegebene Datum ungültig und es wird nichts in das andere Datumsfeld eingetragen
            let friday = getDateOfWeek(fromDate, 5); // der Freitag der Woche, in der das eingegebene Datum liegt
            document.getElementById("to-date").valueAsDate = friday; // setzt den Freitag in das Datumsfeld mit der Beschriftung „Bis:“
        }
        </script> <!-- Ende des Skripts -->
        <!-- Ein Container, in der der Benutzername des angemeldeten Benutzers
        sowie eine Schaltfläche zum Abmelden angezeigt werden: -->
        <div class="user-info">
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = '../abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Neuen Ausbildungsnachweis anlegen</h1> <!-- die Überschrift der Seite -->
        <!-- Wenn eine Fehlermeldung gespeichert ist, diese in Rot anzeigen: -->
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Das Formular, in das Startdatum und Enddatum eingegeben werden: -->
        <form method="post">
            <div class="grid-container"> <!-- Erzeugt ein 3x2-Raster, wie es im Stylesheet festgelegt ist -->
                <div>Ausbildungswoche vom:</div>
                <div>Bis:</div>
                <div></div> <!-- ein einfacher Weg, eine Zelle des Rasters zu überspringen -->
                <!-- das Datumsfeld für das Startdatum: -->
                <div><input type="date" oninput="putFriday()" name="from-date" id="from-date" required></div>
                <!-- das Datumsfeld für das Enddatum: -->
                <div><input type="date" name="to-date" id="to-date" required></div>
                <!-- die Schaltfläche „Aktuelle Woche“, die den Montag und Freitag der aktuellen Woche in die
                Datumsfelder eingibt: -->
                <div><button type="button" onclick="putCurrentWeek();" class="blue-button">Aktuelle Woche</button></div>
            </div>
            <br>
            <!-- Die Schaltfläche „Ausbildungsnachweis anlegen“, die das Formular abschickt -->
            <button type="submit" class="blue-button" style="margin-top: 15px;">Ausbildungsnachweis anlegen</button>
            <!-- Die Schaltfläche „Abbrechen“, die den Benutzer zu seiner Ausbildungsnachweis-Liste zurückleitet -->
            <button type="button" onclick="location.href = 'an-liste';" class="orange-button">Abbrechen</button>
        </form>
    </body>
</html>