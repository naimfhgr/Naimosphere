<?php
// config.php
// Datenbank-Verbindung

// 1. Standard-Werte (Dummy-Daten für GitHub)
$host = "127.0.0.1";
$user = "db_user";
$pass = "db_password";
$db   = "db_name";

// 2. Prüfen, ob eine lokale Konfigurationsdatei existiert (auf dem Server)
// Diese Datei enthält die echten Passwörter und wird NICHT mit ins Git committet.
if (file_exists(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}

// 3. Verbindung aufbauen
// Das 'mysqli' nutzt jetzt entweder die Daten aus config.local.php (wenn vorhanden)
// oder die Dummy-Daten von oben.
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Datenbank-Verbindung fehlgeschlagen.");
}

$conn->set_charset("utf8mb4");
?>