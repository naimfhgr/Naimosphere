<?php
// insert_debug.php
// Minimaler Test: DB-Verbindung + ein INSERT + Fehlermeldung anzeigen

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config.php";

echo "Debug: insert_debug.php<br><br>";

// Prüfen, ob $conn existiert
echo "\$conn Typ: " . gettype($conn) . "<br>";
if ($conn instanceof mysqli) {
    echo "mysqli-Verbindung OK.<br><br>";
} else {
    echo "Achtung: \$conn ist keine mysqli-Instanz.<br><br>";
}

// Test-Daten
$time  = "2025-12-02 00:00:00";
$lat   = 47.40;
$lon   = 8.50;
$aqi   = 42;
$pm10  = 12.3;
$pm25  = 7.8;
$ozone = 15.0;
$birch = 0;
$grass = 0;

// SQL-Befehl
$sql = "
INSERT INTO air_quality
(time, latitude, longitude, european_aqi, pm10, pm2_5, ozone, birch_pollen, grass_pollen)
VALUES
('$time', $lat, $lon, $aqi, $pm10, $pm25, $ozone, $birch, $grass)
";

echo "SQL:<br><pre>$sql</pre><br>";

// SQL ausführen
echo "Führe INSERT aus...<br>";

if ($conn->query($sql)) {
    echo "✅ INSERT erfolgreich!<br><br>";
} else {
    echo "❌ INSERT Fehler: " . $conn->error . "<br><br>";
}

// Kontrolle: Anzahl Zeilen in air_quality
$res = $conn->query("SELECT COUNT(*) AS c FROM air_quality");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Zeilen in air_quality: " . $row["c"] . "<br>";
} else {
    echo "Fehler bei COUNT(*): " . $conn->error . "<br>";
}
?>