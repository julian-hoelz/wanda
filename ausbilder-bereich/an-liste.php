<?php

// Diese Seite zeigt dem angemeldeten Ausbilder die Listen aller eingereichten und geprüften Ausbildungsnachweise an

include '../util.php'; // die Datei „util.php“ im Überordner inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// prüfen, ob der Benutzer ein Ausbilder ist und somit Zugriff auf diese Seite hat.
// Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet:
check_page_access($conn, "Ausbilder");

// die SQL-Abfrage, um alle Ausbildungsnachweise mit dem Status „Eingereicht“ zu bekommen:
$sql = "SELECT `AusbildungsnachweisID`, `Startdatum`, `Enddatum`, `Ausbildungsjahr`, `BenutzerID`
        FROM `ausbildungsnachweis`
        WHERE `Aktiv` AND `Status` = 'Eingereicht'
        ORDER BY `Startdatum` DESC;";
$result_submitted = $conn->query($sql); // die SQL-Abfrage ausführen

// die SQL-Abfrage, um alle Ausbildungsnachweise mit dem Status „Angenommen“ oder „Abgelehnt“ zu bekommen:
$sql = "SELECT `AusbildungsnachweisID`, `Startdatum`, `Enddatum`, `Ausbildungsjahr`, `BenutzerID`, `Status`
        FROM `ausbildungsnachweis`
        WHERE `Aktiv` AND `Status` IN ('Angenommen', 'Abgelehnt')
        ORDER BY `Startdatum` DESC;";
$result_checked = $conn->query($sql); // die SQL-Abfrage ausführen

?>

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../styles.css">
        <title>Ausbildungsnachweis-Liste</title>
    </head>
    <body>
         <!-- Ein Container, in der der Benutzername des angemeldeten Benutzers
        sowie Schaltflächen zu den Einstellungen und zum Abmelden angezeigt werden: -->
        <div class="user-info">
            <span style="margin-right: 8px;">Angemeldet als <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <button onclick="location.href = '../einstellungen';" class="blue-button" style="margin-right: 3px">Einstellungen</button>
            <button onclick="location.href = '../abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Neu eingereichte Ausbildungsnachweise</h1> <!-- die erste Überschrift der Seite -->
        <?php if ($result_submitted->num_rows > 0): ?> <!-- wenn es mindestens einen neu eingereichten Ausbildungsnachweis gibt -->
            <table class="an-list">
                <thead>
                    <tr>
                        <th>Auszubildender</th>
                        <th>Ausbildungsjahr</th>
                        <th>Ausbildungswoche</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_submitted->fetch_assoc()): ?> <!-- alle neu eingereichten Ausbildungsnachweise durchlaufen -->
                        <?php $an_id = $row["AusbildungsnachweisID"]; ?>
                        <tr>
                            <td><?= htmlspecialchars(get_full_name($conn, $row["BenutzerID"])) ?></td> <!-- die erste Spalte: der volle Name des Auszubildenden -->
                            <td><?= htmlspecialchars($row["Ausbildungsjahr"]) ?></td> <!-- die zweite Spalte: das Ausbildungsjahr -->
                            <td><?= htmlspecialchars(date_range($row["Startdatum"], $row["Enddatum"])) ?></td> <!-- die dritte Spalte: die Ausbildungswoche -->
                            <td> <!-- beide Schaltflächen sind in einer Spalte der Tabelle -->
                                <!-- die Schaltfläche „Ansehen“ (beim Anklicken wird der Ausbildungsnachweis angezeigt): -->
                                <button onclick="location.href = 'an-ansehen?an-id=<?= htmlspecialchars($an_id) ?>';" class="blue-table-button">Ansehen</button>
                                <!-- die Schaltfläche „Löschen“ (beim Anklicken kommt eine Bestätigungsseite, ob man den Ausbildungsnachweis wirklich löschen möchte): -->
                                <button onclick="location.href = 'loeschen-bestaetigen?an-id=<?= htmlspecialchars($an_id) ?>';" class="orange-table-button">Löschen</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?> <!-- wenn es keine neu eingereichten Ausbildungsnachweise gibt -->
            <p>Es gibt aktuell keine neu eingereichten Ausbildungsnachweise.</p>
        <?php endif; ?>
        <h1>Geprüfte Ausbildungsnachweise</h1> <!-- die zweite Überschrift der Seite -->
        <?php if ($result_checked->num_rows > 0): ?> <!-- wenn es mindestens einen geprüften Ausbildungsnachweis gibt -->
            <table class="an-list">
                <thead>
                    <tr>
                        <th>Auszubildender</th>
                        <th>Ausbildungsjahr</th>
                        <th>Ausbildungswoche</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_checked->fetch_assoc()): ?> <!-- alle geprüften Ausbildungsnachweise durchlaufen -->
                        <?php $an_id = $row["AusbildungsnachweisID"]; ?>
                        <tr>
                            <td><?= htmlspecialchars(get_full_name($conn, $row["BenutzerID"])) ?></td>  <!-- die erste Spalte: der volle Name des Auszubildenden -->
                            <td><?= htmlspecialchars($row["Ausbildungsjahr"]) ?></td> <!-- die zweite Spalte: das Ausbildungsjahr -->
                            <td><?= htmlspecialchars(date_range($row["Startdatum"], $row["Enddatum"])) ?></td> <!-- die dritte Spalte: die Ausbildungswoche -->
                            <td><?= htmlspecialchars($row["Status"]) ?></td> <!-- die vierte Spalte: der Status des Ausbildungsnachweises -->
                            <td> <!-- alle drei Schaltflächen sind in einer Spalte der Tabelle -->
                                <!-- die Schaltfläche „Ansehen“ (beim Anklicken wird der Ausbildungsnachweis angezeigt): -->
                                <button onclick="location.href = 'an-ansehen?an-id=<?= htmlspecialchars($an_id) ?>';" class="blue-table-button">Ansehen</button>
                                <!-- die Schaltfläche „Revidieren“ (beim Anklicken wird der Status auf „Eingereicht“ gesetzt): -->
                                <button onclick="location.href = 'an-revidieren?an-id=<?= htmlspecialchars($an_id) ?>';" class="yellow-table-button">Revidieren</button>
                                <!-- die Schaltfläche „Löschen“ (beim Anklicken kommt eine Bestätigungsseite, ob man den Ausbildungsnachweis wirklich löschen möchte): -->
                                <button onclick="location.href = 'loeschen-bestaetigen?an-id=<?= htmlspecialchars($an_id) ?>';" class="orange-table-button">Löschen</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?> <!-- wenn es keine geprüften Ausbildungsnachweise gibt -->
            <p>Es gibt noch keine geprüften Ausbildungsnachweise.</p>
        <?php endif; ?>
    </body>
</html>

<?php

$conn->close();

?>