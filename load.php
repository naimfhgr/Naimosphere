<?php
// load.php
// Holt Daten von der API und speichert sie in der Tabelle air_quality

require_once "config.php";
require_once "extract.php";
require_once "transform.php";

header("Content-Type: text/plain; charset=utf-8");

// Standort (Zürich)
$lat = 47.38;
$lon = 8.54;

echo "Debug: load.php\n";

// 1) API abrufen
$apiData = extractData($lat, $lon);

if ($apiData === null) {
    die("API konnte nicht geladen oder geparst werden.\n");
}

echo "API geladen.\n";

// 2) Daten transformieren
$rows = transformData($apiData, $lat, $lon);

$count = count($rows);
echo "Anzahl Datensätze nach transform(): $count\n";

if ($count === 0) {
    die("Keine Datensätze nach transform().\n");
}

// 3) Daten speichern
$ok  = 0;
$err = 0;

foreach ($rows as $r) {

    $time         = $conn->real_escape_string($r["time"]);
    $latDB        = (float)$r["latitude"];
    $lonDB        = (float)$r["longitude"];
    $aqi          = (int)$r["european_aqi"];
    $pm10         = (float)$r["pm10"];
    $pm2_5        = (float)$r["pm2_5"];
    $ozone        = (float)$r["ozone"];
    $birch        = (int)$r["birch_pollen"];
    $grass        = (int)$r["grass_pollen"];

    $sql = "
        INSERT INTO air_quality 
        (time, latitude, longitude, european_aqi, pm10, pm2_5, ozone, birch_pollen, grass_pollen)
        VALUES 
        ('$time', $latDB, $lonDB, $aqi, $pm10, $pm2_5, $ozone, $birch, $grass)
    ";

    // WICHTIG: Jetzt mit kompletter Fehlerausgabe
    if ($conn->query($sql)) {
        $ok++;
    } else {
        $err++;
        echo "INSERT Fehler: " . $conn->error . "\n";
        echo "Fehlerhafte SQL-Zeile:\n$sql\n\n";
    }
}

echo "FERTIG – Daten wurden verarbeitet.\n";
echo "Erfolgreich eingefügt: $ok\n";
echo "Fehler: $err\n";

// 4) Kontrolle: Anzahl Zeilen in der Tabelle
$res = $conn->query("SELECT COUNT(*) AS c FROM air_quality");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Zeilen aktuell in air_quality: " . $row["c"] . "\n";
} else {
    echo "Fehler bei COUNT(*): " . $conn->error . "\n";
}

?>