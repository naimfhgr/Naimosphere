<?php
// config.php
// Datenbank-Verbindung

// Error Reporting aktivieren, damit MySQLi Fehler als Exceptions wirft
// Das hilft dir beim Debuggen enorm!
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Standard-Werte (Dummy-Daten für GitHub)
$host = "127.0.0.1";
$user = "db_user";
$pass = "db_password";
$db   = "db_name";

// 2. Prüfen, ob eine lokale Konfigurationsdatei existiert
if (file_exists(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}

try {
    // 3. Verbindung aufbauen
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Zeichensatz setzen
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Im Fehlerfall:
    // Für Cronjobs/Debugging: Fehler anzeigen
    // Für Live-User: Eigentlich verstecken, aber für IM3 ist Fehlertext ok
    die("Datenbank-Fehler: " . $e->getMessage());
}
?>