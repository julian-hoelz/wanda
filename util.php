<?php

// Dieses Skript wird von vielen anderen inkludiert und stellt Funktionen bereit, die immer wieder benötigt werden.

// Die Namen der 12 Monate:
const MONTH_NAMES = ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli",
                     "August", "September", "Oktober", "November", "Dezember"];

/**
 * Diese Klasse repräsentiert ein Datum und stellt Methoden bereit, um dieses in einen String umzuwandeln.
 */
class Date {

    public int $year; // das Jahr
    public int $month; // der Monat
    public int $day; // der Tag

    public function __construct(string $odb) { // odb = out of database
        $this->year = (int) substr($odb, 0, 4); // die ersten vier Zeichen des Datums sind das Jahr
        $this->month = (int) substr($odb, 5, 2); // das sechste und siebte Zeichen des Datums sind der Monat
        $this->day = (int) substr($odb, 8, 2); // das neunte und zehnte Zeichen des Datums dind der Tag
    }

    /**
     * Diese Funktion wandelt das Datum in einen String im deutschen Datumsformat (TT. <Name des Monats> JJJJ) um.
     * @return string das umgewandelte Datum
     */
    public function fullStr(): string {
        return $this->dayAndMonthStr() . " " . $this->year;
    }

    /**
     * Diese Funktion gibt den Tag und den Monat des Datums im Format „TT. <Name des Monats>“ zurück.
     * @return string der Tag und der Monat des Datums im Format „TT. <Name des Monats>“
     */
    public function dayAndMonthStr(): string {
        return $this->dayStr() . " " . MONTH_NAMES[$this->month - 1];
    }

    /**
     * Diese Funktion gibt den Tag des Datums im Format „TT.“ zurück.
     * @return string der Tag des Datums im Format „TT.“
     */
    public function dayStr(): string {
        return sprintf("%02d.", $this->day);
    }

}

/**
 * Diese Klasse repräsentiert einen Wochentag mit dessen englischem und deutschem Namen.
 * Der englische Name wird dazu genutzt, ihn in HTML- und JavaScript-Codes einzubetten, und steht deshalb in Kleinbuchstaben.
 * Der deutsche Name wird in der Benutzeroberfläche angezeigt.
 */
class Weekday {

    public string $en; // der englische Name des Wochentags in Kleinbuchstaben
    public string $de; // der deutsche Name des Wochentags

    public function __construct(string $en, string $de) {
        $this->en = $en;
        $this->de = $de;
    }

}

// Die 5 Wochentage mit ihren englischen und deutschen Namen (die englischen Namen stehen in Kleinbuchstaben, weil sie dazu genutzt werden,
// sie in HTML- und JavaScript-Codes einzubetten):
const WEEKDAYS = [new Weekday("monday", "Montag"), new Weekday("tuesday", "Dienstag"), new Weekday("wednesday", "Mittwoch"),
                  new Weekday("thursday", "Donnerstag"), new Weekday("friday", "Freitag")];

/**
 * Diese Funktion prüft, ob ein Benutzer angemeldet ist. Wenn nicht, wird der Benutzer zur Anmeldeseite weitergeleitet.
 */
function check_logged_in(): void {
    if (!isset($_SESSION["username"])) { // wenn kein Benutzer angemeldet ist
        header("Location: /wanda/anmeldung"); // den Benutzer zur Anmeldeseite weiterleiten
        exit(); // die Ausführung dieses Skripts beenden
    }
}

/**
 * Diese Funktion prüft, ob der Benutzer die übergebene Rolle und somit Zugriff auf die Seite hat.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param string $required_role // die erforderliche Rolle
 */
function check_page_access(mysqli $conn, string $required_role): void {
    $user_id = $_SESSION["user-id"];
    // die SQL-Abfrage, um die Rolle des angemeldeten Benutzers zu bekommen:
    $sql = "SELECT `Rolle` FROM `benutzer`
            WHERE `BenutzerID` = ?;";
    $result = exec_sql($conn, $sql, "i", $user_id); // die SQL-Abfrage ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $user_role = $row["Rolle"];
    if ($user_role != $required_role) { // wenn die Rolle des Benutzers nicht die erforderliche ist
        $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
        error_page_page_access_denied(); // eine Fehlerseite anzeigen, die dem Benutzer mitteilt, dass er keinen Zugriff auf die Seite hat
        exit(); // die Ausführung dieses Skripts stoppen
    }
}

/**
 * Diese Funktion prüft, ob es einen Ausbildungsnachweis mit der übergebenen ID gibt und ob der angemeldete Benutzer Zugriff auf diesen hat.
 * Wenn es keinen entsprechenden Ausbildungsnachweis gibt oder der Benutzer keinen Zugriff darauf hat, wird eine entsprechende Fehlerseite angezeigt.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param int $an_id die Ausbildungsnachweis-ID
 */
function check_an_exists_not_deleted_and_access(mysqli $conn, int $an_id): void {
    // die SQL-Abfrage, um die ID des Anlegers und den Aktivitätsstatus des Ausbildungsnachweises zu bekommen:
    $sql = "SELECT `BenutzerID`, `Aktiv`
            FROM `ausbildungsnachweis`
            WHERE `AusbildungsnachweisID` = ?;";
    $result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen
    if ($result->num_rows == 0) { // wenn es keinen Ausbildungsnachweis mit der übergebenen ID gibt
        $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
        error_page("Ausbildungsnachweis nicht gefunden", "Sie sollen doch nicht an der URL herumspielen.");
        exit(); // die Ausführung dieses Skripts stoppen
    }
    $user_id = $_SESSION["user-id"];
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    if (get_role($conn, $user_id) == "Auszubildender" && $user_id != $row["BenutzerID"]) { // wenn ein Auszubildender angemeldet ist und seine ID nicht mit der ID des Schreibers übereinstimmt
        $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
        error_page("Zugriff verweigert!", "Sie haben keinen Zugriff auf diesen Ausbildungsnachweis.");
        exit(); // die Ausführung dieses Skripts stoppen
    }
    if (!$row["Aktiv"]) { // wenn der Ausbildungsnachweis nicht aktiv ist, also gelöscht wurde
        $conn->close(); // die Verbindung mit der Wanda-Datenbank schließen
        error_page("Ausbildungsnachweis gelöscht", "Dieser Ausbildungsnachweis wurde gelöscht. Sie können einen Administrator bitten, ihn wiederherzustellen.");
        exit(); // die Ausführung dieses Skripts stoppen
    }
}

/**
 * Diese Funktion zeigt eine Fehlerseite an. Nach dem Aufruf muss noch `exit()` aufgerufen werden.
 * @param string $error die Überschrift der Fehlerseite
 * @param string $description die Beschreibung auf der Fehlerseite
 */
function error_page(string $error, string $description): void {
    include "fehlerseite.php";
}

/**
 * Diese Funktion zeigt eine Fehlerseite an, die dem Benutzer mitteilt, dass er keinen Zugriff die Seite hat.
 * Nach dem Aufruf dieser Funktion muss noch `exit()` aufgerufen werden.
 */
function error_page_page_access_denied() {
    error_page("Zugriff verweigert!", "Sie haben keinen Zugriff auf diese Seite.");
}

/**
 * Diese Funktion stellt eine Verbindung mit der Wanda-Datenbank her und gibt sie zurück.
 * @return mysqli die Verbindung mit der Wanda-Datenbank
 */
function connect_to_wanda_db(): mysqli {
    $server_name = "localhost";
    $username = "root";
    $password = "";
    $dbname = "wanda";
    try {
        $conn = mysqli_connect($server_name, $username, $password, $dbname);
        return $conn;
    } catch (mysqli_sql_exception $e) { // wenn die Verbindung nicht hergestellt werden konnte
        echo "Verbindung mit der Datenbank fehlgeschlagen: ". $e->getMessage() . '.<br>';
        exit(); // die Ausführung dieses Skripts stoppen
    }
}

/**
 * Diese Funktion bindet Variablen an eine vorbereitete SQL-Anweisung, führt die Anweisung aus und gibt das Ergebnis zurück.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param string $sql die SQL-Anweisung
 * @param string $types die Datentypen für das Binden der Variablen an die SQL-Anweisung
 * @param array $vars die Variablen, die an die SQL-Anweisung gebunden werden sollen
 * @return bool|mysqli_result siehe mysqli_stmt->get_result()
 */
function exec_sql(mysqli $conn, string $sql, string $types, ...$vars): bool|mysqli_result {
    $stmt = $conn->prepare($sql); // die Anweisung vorbereiten
    $stmt->bind_param($types, ...$vars); // die Argumente in die Anweisung einbinden
    if (!$stmt->execute()) { // die Anweisung ausführen; wenn die Ausführung fehlschlägt
        die("Fehler: $stmt->error"); // Fehlermeldung anzeigen und die Ausführung dieses Skripts stoppen
    }
    $result = $stmt->get_result(); // das Ergebnis der Ausführung
    return $result; // das Ergebnis zurückgeben
}

/**
 * Diese Funktion gibt die ID des Benutzers mit dem übergebenen Benutzernamen zurück. Diese ID ist in der Wanda-Datenbank gespeichert. 
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param string $username der Benutzername, zu dem die ID zurückgegeben werden soll
 * @return int die ID des Benutzers mit dem übergebenen Benutzernamen
 */
function get_user_id(mysqli $conn, string $username): int {
    // die SQL-Anfrage, um die ID des Benutzers mit dem übergebenen Benutzernamen zu bekommen
    $sql = "SELECT `BenutzerID` FROM `benutzer`
            WHERE `Benutzername` = ?;";
    $result = exec_sql($conn, $sql, "s", $username); // die SQL-Abfrage ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $user_id = $row["BenutzerID"];
    return $user_id;
}

/**
 * Diese Funktion gibt die ID des Schreibers des Ausbildungsnachweises mit der übergebenen ID zurück.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param int $an_id die Ausbildungsnachweis-ID
 * @return int die ID des Schreibers des Ausbildungsnachweises mit der übergebenen ID
 */
function get_recorder_id(mysqli $conn, int $an_id): int {
    // die SQL-Abfrage, um die ID des Schreibers des Ausbildungsnachweises zu bekommen
    $sql = "SELECT `BenutzerID` FROM `ausbildungsnachweis`
            WHERE `AusbildungsnachweisID` = ?;";
    $result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $recorder_id = $row["BenutzerID"];
    return $recorder_id;
}

/**
 * Diese Funktion gibt den vollen Namen des Benutzers mit der übergebenen ID zurück.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param int $user_id die Benutzer-ID
 * @return string der volle Name des Benutzers mit der übergebenen ID
 */
function get_full_name(mysqli $conn, int $user_id): string {
    // die SQL-Abfrage, um den vollen Namen des Benutzers mit der übergebenen ID zu bekommen
    $sql = "SELECT `VollerName` FROM `benutzer`
            WHERE `BenutzerID` = ?;";
    $result = exec_sql($conn, $sql, "i", $user_id); // die SQL-Abfrage ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $full_name = $row["VollerName"];
    return $full_name;
}

/**
 * Diese Funktion gibt die Rolle des Benutzers mit der übergebenen ID zurück.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param int $user_id die Benutzer-ID
 * @return string die Rolle des Benutzers mit der übergebenen ID
 */
function get_role(mysqli $conn, int $user_id): string {
    // die SQL-Abfrage, um die Rolle des Benutzers mit der übergebenen ID zu bekommen
    $sql = "SELECT `Rolle` FROM `benutzer`
            WHERE `BenutzerID` = ?;";
    $result = exec_sql($conn, $sql, "i", $user_id); // die SQL-Abfrage ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $role = $row["Rolle"];
    return $role;
}

/**
 * Diese Funktion fragt das korrekte, gehashte Passwort aus der Wanda-Datenbank
 * für den Benutzer mit dem übergebenen Benutzernamen ab. 
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param string $input_username der Benutzername, den der Benutzer eingegeben hat
 * @return string|null das korrekte, gehashte Passwort des Benutzers mit dem übergebenen Benutzernamen,
 *                     wenn es einen Benutzer mit diesem Benutzernamen gibt; andernfalls `null`
 */
function get_correct_hashed_password(mysqli $conn, string $input_username): string|null {
    // die SQL-Abfrage, um den Benutzernamen und das gehashte aller Benutzer zu bekommen
    $sql = "SELECT `Benutzername`, `GehashtesPasswort`
            FROM `benutzer`;";
    $result = $conn->query($sql); // die SQL-Abfrage ausführen
    while ($row = $result->fetch_assoc()) { // alle Ergebniszeilen durchlaufen
        if ($row["Benutzername"] == $input_username) { // wenn der Benutzername in der Tabelle mit dem eingegebenen übereinstimmt
            return $row["GehashtesPasswort"]; // das gehashte Passwort zurückgeben
        }
    }
    return null;
}

/**
 * Diese Funktion gibt den booleschen Wert zurück, der in der Spalte „PasswortGesetzt“ in der Tabelle „Benutzer“ der Wanda-Datenbank für den angemeldeten Benutzer gespeichert ist. Dieser Wert steht dafür, ob der Benutzer bereits ein eigenes Passwort gesetzt hat.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @return bool der Wert der Spalte „PasswortGesetzt“ in der Tabelle „Benutzer“ der Wanda-Datenbank.
 */
function get_password_set(mysqli $conn): bool {
    $user_id = $_SESSION["user-id"];
    // die SQL-Anweisung, um abzufragen, ob der Benutzer bereits ein eigenes Passwort gesetzt hat:
    $sql = "SELECT `PasswortGesetzt` FROM `benutzer`
            WHERE `BenutzerID` = ?;";
    $result = exec_sql($conn, $sql, "i", $user_id); // die SQL-Anweisung ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $passwordSet = $row["PasswortGesetzt"];
    return $passwordSet;
}

/**
 * Diese Funktion hasht ein Passwort und speichert es in der Wanda-Datenbank für den angemeldeten Benutzer.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @param string $password das zu speichernde Passwort
 */
function store_password(mysqli $conn, string $password): void {
    $user_id = $_SESSION["user-id"];
    $hashedNewPassword = password_hash($password, PASSWORD_DEFAULT); // das Passwort hashen
    // die SQL-Anweisung, um das Passwort in der Datenbanktabelle zu speichern
    $sql = "UPDATE `benutzer`
            SET `GehashtesPasswort` = ?
            WHERE `BenutzerID` = ?;";
    exec_sql($conn, $sql, "si", $hashedNewPassword, $user_id); // die SQL-Anweisung ausführen
}

/**
 * Diese Funktion gibt einen Zeitraum im Format „TT. bis TT. <Name des Monats> JJJJ“ zurück.
 * Wenn die Daten nicht im selben Monat oder Jahr liegen, wird das Format angepasst.
 * @param string $from_date_str das Startdatum
 * @param string $to_date_str das Enddatum
 * @return string der Zeitraum im Format „TT. bis TT. <Name des Monats> JJJJ“
 */
function date_range(string $from_date_str, string $to_date_str): string {
    $from_date = new Date($from_date_str); // das Startdatum als Date-Objekt (eigene Klasse)
    $to_date = new Date($to_date_str); // das Enddatum als Date-Objekt (eigene Klasse)
    if ($from_date->year != $to_date->year) // bei unterschiedlichen Jahren
        return $from_date->fullStr() . " bis " . $to_date->fullStr(); // beide Daten voll ausgeschrieben zurückgeben
    if ($from_date->month != $to_date->month) // bei unterschiedlichen Monaten
        return $from_date->dayAndMonthStr() . " bis " . $to_date->fullStr(); // nur Tag und Monat des ersten Datums zurückgeben
    return $from_date->dayStr() . " bis " . $to_date->fullStr(); // sonst: nur den Tag des ersten Datums zurückgeben
}

/**
 * Diese Funktion gibt den Zeitraum des aktuellen Ausbildungsnachweises zurück.
 * Siehe `date_range()`.
 * @param mysqli $conn eine Verbindung mit der Wanda-Datenbank
 * @return string der Zeitraum im Format „TT. bis TT. <Name des Monats> JJJJ“
 */
function date_range_current_an(mysqli $conn): string {
    $an_id = $_GET["an-id"];
    // die SQL-Abfrage, um das Startdatum und das Enddatum des Ausbildungsnachweises zu bekommen:
    $sql = "SELECT `Startdatum`, `Enddatum`
            FROM `ausbildungsnachweis`
            WHERE `AusbildungsnachweisID` = ?;";
    $result = exec_sql($conn, $sql, "i", $an_id); // die SQL-Abfrage ausführen
    $row = $result->fetch_assoc(); // die erste und einzige Ergebniszeile
    $from_date = $row["Startdatum"];
    $to_date = $row["Enddatum"];
    return date_range($from_date, $to_date);
}

?>