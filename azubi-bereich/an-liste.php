<?php

// Diese Seite zeigt dem angemeldeten Auszubildenden die Liste seiner Ausbildungsnachweise an

include '../util.php'; // die Datei „util.php“ im Überordner inkludieren

session_start();

check_logged_in(); // prüfen, ob ein Benutzer angemeldet ist. Wenn nicht, den Anwender auf die Anmeldeseite weiterleiten

$conn = connect_to_wanda_db(); // eine Verbindung mit der Wanda-Datenbank herstellen

// prüfen, ob der Benutzer ein Auszubildender ist und somit Zugriff auf diese Seite hat.
// Wenn nicht, wird er zur angepassten Fehlerseite weitergeleitet:
check_page_access($conn, "Auszubildender");

$user_id = $_SESSION["user-id"]; // die ID des angemeldeten Benutzers

// die SQL-Abfrage, um die in der Tabelle anzuzeigenden Ausbildungsnachweis-Daten zu bekommen
$sql = "SELECT `AusbildungsnachweisID`, `Startdatum`, `Enddatum`, `Ausbildungsjahr`, `Status`
        FROM `ausbildungsnachweis`
        WHERE `Aktiv` AND `BenutzerID` = ?
        ORDER BY `Startdatum` DESC;";
$result = exec_sql($conn, $sql, "i", $user_id); // die SQL-Abfrage ausführen

$conn->close(); // die Verbindung mit der Wanda-Datenbank schließen

?>

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
            <button onclick="location.href = '../einstellungen';" class="blue-button" style="margin-right: 4px">Einstellungen</button>
            <button onclick="location.href = '../abmelden';" class="orange-button">Abmelden</button>
        </div>
        <h1>Ihre Ausbildungsnachweise</h1> <!-- die Überschrift der Seite -->
        <!-- eine Schaltfläche zum Anlegen eines neuen Ausbildungsnachweises: -->
        <button onclick="location.href = 'an-anlegen'" class="blue-button" style="margin-bottom: 15px;">Ausbildungsnachweis anlegen</button>
        <?php if ($result->num_rows > 0): ?> <!-- wenn mindestens ein Ausbildungsnachweis angelegt ist -->
            <table class="an-list">
                <thead>
                    <tr>
                        <th>Nummer</th>
                        <th>Ausbildungsjahr</th>
                        <th>Ausbildungswoche</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $n = $result->num_rows ?> <!-- die Anzahl der Ausbildungsnachweise -->
                    <?php while($row = $result->fetch_assoc()): ?> <!-- alle Ausbildungsnachweise durchlaufen -->
                        <?php
                        $an_id = $row["AusbildungsnachweisID"];
                        $status = $row["Status"];
                        // Daten für die Schaltflächen „Bearbeiten“/„Ansehen“ und „Einreichen“/„Zurückziehen“:
                        if ($status == "In Bearbeitung") {
                            // die Seite, auf die man gelangt, wenn man auf „Bearbeiten“/„Ansehen“ klickt:
                            $view_or_edit_page = "an-bearbeiten";
                            $view_or_edit_button_text = "Bearbeiten"; // der Text der Schaltfläche „Bearbeiten“/„Ansehen“
                            $view_or_edit_button_class = "purple-table-button"; // die CSS-Klasse der Schaltfläche „Bearbeiten“/„Ansehen“
                            $status_button_text = "Einreichen"; // der Text der Schaltfläche „Einreichen“/„Zurückziehen“
                            $status_button_class = "cyan-table-button"; // die CSS-Klasse der Schaltfläche „Einreichen“/„Zurückziehen“
                        } else {
                            // die Seite, auf die man gelangt, wenn man auf „Bearbeiten“/„Ansehen“ klickt:
                            $view_or_edit_page = "an-ansehen";
                            $view_or_edit_button_text = "Ansehen"; // der Text der Schaltfläche „Bearbeiten“/„Ansehen“
                            $view_or_edit_button_class = "blue-table-button"; // die CSS-Klasse der Schaltfläche „Bearbeiten“/„Ansehen“
                            $status_button_text = "Zurückziehen"; // der Text der Schaltfläche „Einreichen“/„Zurückziehen“
                            $status_button_class = "yellow-table-button"; // die CSS-Klasse der Schaltfläche „Einreichen“/„Zurückziehen“
                        }
                        // Farbe des Status:
                        if ($status == "Angenommen") {
                            $status_color = "green";
                        } elseif ($status == "Abgelehnt") {
                            $status_color = "red";
                        } else {
                            $status_color = "black";
                        }
                        // Seite, auf die der Benutzer weitergeleitet wird, wenn er auf „Einreichen“/„Zurückziehen“ klickt:
                        if ($status == "Angenommen") {
                            $page_on_submit_or_retract = "zurueckziehen-bestaetigen?an-id=$an_id";
                        } else {
                            $page_on_submit_or_retract = "status-toggeln?an-id=$an_id";
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($n) ?></td> <!-- die erste Spalte der Tabelle: die absteigende Nummer des Ausbildungsnachweises -->
                            <td><?= htmlspecialchars($row["Ausbildungsjahr"]) ?></td> <!-- die zweite Spalte der Tabelle: das Ausbildungsjahr -->
                            <td><?= htmlspecialchars(date_range($row["Startdatum"], $row["Enddatum"])) ?></td> <!-- die dritte Spalte der Tabelle: die Datumsspanne -->
                            <!-- die vierte Spalte der Tabelle: der Status des Ausbildungsnachweises in der entsprechenden Farbe: -->
                            <td style="min-width: 110px; color: <?= htmlspecialchars($status_color) ?>;"><?= htmlspecialchars($status) ?></td>
                            <td> <!-- alle drei Schaltflächen sind in einer Spalte der Tabelle -->
                                <!-- die Schaltfläche „Bearbeiten“/„Ansehen“ (beim Anklicken wird die Bearbeitungs- oder Ansichtsseite geöffnet): -->
                                <button onclick="location.href = '<?= htmlspecialchars($view_or_edit_page) ?>?an-id=<?= htmlspecialchars($an_id) ?>';" class="<?= htmlspecialchars($view_or_edit_button_class) ?>"><?= htmlspecialchars($view_or_edit_button_text) ?></button>
                                <!-- die Schaltfläche „Einreichen“/„Zurückziehen“ (beim Anklicken wird der Status zwischen „In Bearbeitung“ und „Eingereicht“ gewechselt): -->
                                <button onclick="location.href = '<?= htmlspecialchars($page_on_submit_or_retract) ?>'" class="<?= htmlspecialchars($status_button_class) ?>"><?= htmlspecialchars($status_button_text) ?></button>
                                <!-- die Schaltfläche „Löschen“ (beim Anklicken kommt eine Bestätigungsseite, ob man den Ausbildungsnachweis wirklich löschen möchte): -->
                                <button onclick="location.href = 'loeschen-bestaetigen?an-id=<?= htmlspecialchars($an_id) ?>';" class="orange-table-button">Löschen</button>
                            </td>
                        </tr>
                        <?php $n--; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Noch keine Ausbildungsnachweise.</p>
        <?php endif; ?>
    </body>
</html>