<?php
// load.php
// 1. WICHTIG für Cronjobs: Arbeitsverzeichnis auf den aktuellen Ordner setzen

chdir(__DIR__);

require_once "config.php";
require_once "extract.php";
require_once "transform.php";

header("Content-Type: text/plain; charset=utf-8");

$cities = [
    ["name" => "Basel",      "lat" => 47.56, "lon" => 7.59],
    ["name" => "Bern",       "lat" => 46.95, "lon" => 7.45],
    ["name" => "Chur",       "lat" => 46.85, "lon" => 9.53],
    ["name" => "Genf",       "lat" => 46.20, "lon" => 6.15],
    ["name" => "Lausanne",   "lat" => 46.52, "lon" => 6.63],
    ["name" => "Lugano",     "lat" => 46.00, "lon" => 8.95],
    ["name" => "Luzern",     "lat" => 47.05, "lon" => 8.31],
    ["name" => "Sion",       "lat" => 46.23, "lon" => 7.36],
    ["name" => "St. Gallen", "lat" => 47.42, "lon" => 9.38],
    ["name" => "Zürich",     "lat" => 47.38, "lon" => 8.54],
];

echo "Debug: load.php\n\n";

// SCHRITT 1: ALTE DATEN LÖSCHEN (Clean Slate)
// echo "Lösche alte Daten (TRUNCATE)... ";
// if ($conn->query("TRUNCATE TABLE air_quality")) {
//     echo "OK.\n\n";
// } else {
//     die("Fehler beim Leeren der Tabelle: " . $conn->error);
// }

$ok  = 0;
$err = 0;

foreach ($cities as $city) {
    $lat = $city["lat"];
    $lon = $city["lon"];

    echo "== " . $city["name"] . " ==\n";

    // Holt jetzt 14 Tage Historie (dank extract.php Änderung)
    $apiData = extractData($lat, $lon);
    
    if ($apiData === null) {
        echo "   API-Fehler.\n";
        $err++;
        continue;
    }

    $rows = transformData($apiData, $lat, $lon);
    echo "   Datensätze: " . count($rows) . "\n";

    foreach ($rows as $r) {
        $time  = $conn->real_escape_string($r["time"]);
        $latDB = (float)$r["latitude"];
        $lonDB = (float)$r["longitude"];
        $aqi   = is_null($r["european_aqi"]) ? "NULL" : (int)$r["european_aqi"];
        $pm10  = is_null($r["pm10"])         ? "NULL" : (float)$r["pm10"];
        $pm2_5 = is_null($r["pm2_5"])        ? "NULL" : (float)$r["pm2_5"];
        $ozone = is_null($r["ozone"])        ? "NULL" : (float)$r["ozone"];
        $birch = is_null($r["birch_pollen"]) ? "NULL" : (int)$r["birch_pollen"];
        $grass = is_null($r["grass_pollen"]) ? "NULL" : (int)$r["grass_pollen"];

        // INSERT IGNORE verhindert Abbruch bei Duplikaten (zur Sicherheit)
        $sql = "INSERT IGNORE INTO air_quality
                (time, latitude, longitude, european_aqi, pm10, pm2_5, ozone, birch_pollen, grass_pollen)
                VALUES ('$time', $latDB, $lonDB, $aqi, $pm10, $pm2_5, $ozone, $birch, $grass)";

        if ($conn->query($sql)) {
            $ok++;
        } else {
            $err++;
            // Optional: Fehlerausgabe aktivieren falls nötig
            // echo "Fehler: " . $conn->error . "\n";
        }
    }
    echo "   Importiert.\n\n";
}

echo "FERTIG.\n";
echo "Erfolgreich: $ok\n";
echo "Fehler: $err\n";
?>