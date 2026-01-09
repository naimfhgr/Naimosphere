<?php
// load.php
chdir(__DIR__);

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$daysToFetch = (isset($_GET['mode']) && $_GET['mode'] === 'full') ? 14 : 1;

echo "=== START ETL PROZESS (" . date('Y-m-d H:i:s') . ") ===\n";
echo "Modus: " . ($daysToFetch == 1 ? "Update (1 Tag)" : "Full Init (14 Tage)") . "\n\n";

$sql = "INSERT IGNORE INTO air_quality
        (time, latitude, longitude, european_aqi, pm10, pm2_5, ozone, birch_pollen, grass_pollen)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("CRITICAL ERROR: Prepare failed: " . $conn->error);
}

$totalInserted = 0;
$totalErrors = 0;

foreach ($cities as $city) {
    $name = $city["name"];
    $lat  = $city["lat"];
    $lon  = $city["lon"];

    echo "Verarbeite $name... ";

    $apiData = extractData($lat, $lon, $daysToFetch);
    
    if ($apiData === null) {
        echo "[FEHLER] API lieferte keine Daten.\n";
        $totalErrors++;
        continue;
    }

    $rows = transformData($apiData, $lat, $lon);
    
    if (empty($rows)) {
        echo "[INFO] Keine neuen Datenzeilen generiert.\n";
        continue;
    }

    $conn->begin_transaction();
    $citySuccess = 0;

    foreach ($rows as $r) {
        $stmt->bind_param("sddddddii", 
            $r["time"], 
            $r["latitude"], 
            $r["longitude"], 
            $r["european_aqi"], 
            $r["pm10"], 
            $r["pm2_5"], 
            $r["ozone"], 
            $r["birch_pollen"], 
            $r["grass_pollen"]
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $citySuccess++;
            }
        } else {
            echo "\n   SQL Error: " . $stmt->error;
            $totalErrors++;
        }
    }

    $conn->commit();
    $totalInserted += $citySuccess;
    echo "OK ($citySuccess neu eingefügt).\n";
    usleep(200000); 
}

$stmt->close();
$conn->close();

echo "\n=== ETL ABSCHLUSS ===\n";
echo "Gesamt eingefügt: $totalInserted\n";
echo "Fehler aufgetreten: $totalErrors\n";
?>