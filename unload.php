<?php
// unload.php
require_once "config.php";

header("Cache-Control: no-cache, must-revalidate");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

$cities = [
    "Basel"      => ["lat" => 47.56, "lon" => 7.59],
    "Bern"       => ["lat" => 46.95, "lon" => 7.45],
    "Chur"       => ["lat" => 46.85, "lon" => 9.53],
    "Genf"       => ["lat" => 46.20, "lon" => 6.15],
    "Lausanne"   => ["lat" => 46.52, "lon" => 6.63],
    "Lugano"     => ["lat" => 46.00, "lon" => 8.95],
    "Luzern"     => ["lat" => 47.05, "lon" => 8.31],
    "Sion"       => ["lat" => 46.23, "lon" => 7.36],
    "St. Gallen" => ["lat" => 47.42, "lon" => 9.38],
    "Zürich"     => ["lat" => 47.38, "lon" => 8.54],
];

function respond_json(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function categorize_aqi(?int $aqi): ?string {
    if ($aqi === null) return null;
    if ($aqi <= 20)  return "SEHR GUT";
    if ($aqi <= 50)  return "GUT";
    if ($aqi <= 80)  return "MODERAT";
    if ($aqi <= 120) return "UNGESUND";
    return "SEHR UNGESUND";
}

function get_status_class(?int $aqi): string {
    if ($aqi === null) return "unknown";
    if ($aqi <= 50) return "good";
    if ($aqi <= 80) return "moderate";
    return "poor";
}

function clean_val($val) {
    if ($val === null || $val === "") return null;
    $v = round((float)$val);
    return ($v > 0) ? $v : null;
}

$mode = isset($_GET["mode"]) ? $_GET["mode"] : "detail";
$city = isset($_GET["city"]) ? trim($_GET["city"]) : "";

// --- MODE: OVERVIEW ---
if ($mode === "overview") {
    $overviewData = [];

    $stmtTime = $conn->prepare("SELECT MAX(time) as latest_time FROM air_quality");
    $stmtTime->execute();
    $timeRes = $stmtTime->get_result()->fetch_assoc();
    $latestTime = $timeRes['latest_time'] ?? null;
    $stmtTime->close();

    if ($latestTime) {
        $stmt = $conn->prepare("SELECT latitude, longitude, european_aqi FROM air_quality WHERE time = ?");
        $stmt->bind_param("s", $latestTime);
        $stmt->execute();
        $result = $stmt->get_result();

        $dbRows = [];
        while ($row = $result->fetch_assoc()) {
            $dbRows[] = $row;
        }
        $stmt->close();

        foreach ($cities as $name => $coords) {
            $foundAqi = null;
            $latRef = $coords["lat"];
            $lonRef = $coords["lon"];

            foreach ($dbRows as $dbRow) {
                if (abs($dbRow['latitude'] - $latRef) < 0.01 && abs($dbRow['longitude'] - $lonRef) < 0.01) {
                    $foundAqi = (int)$dbRow['european_aqi'];
                    break; 
                }
            }

            $overviewData[] = [
                "city" => $name, 
                "lat" => $latRef, 
                "lng" => $lonRef, 
                "aqi" => $foundAqi, 
                "status" => get_status_class($foundAqi)
            ];
        }
    } else {
        foreach ($cities as $name => $coords) {
            $overviewData[] = ["city" => $name, "lat" => $coords["lat"], "lng" => $coords["lon"], "aqi" => null, "status" => "unknown"];
        }
    }
    respond_json(["success" => true, "locations" => $overviewData]);
}

// --- MODE: DETAIL ---
if ($city === "" || !array_key_exists($city, $cities)) {
    respond_json(["success" => false, "error" => "Stadt fehlt oder ungültig."], 400);
}

$lat = $cities[$city]["lat"];
$lon = $cities[$city]["lon"];
$range = isset($_GET['range']) ? $_GET['range'] : '14d';

// Latest Data
$stmt = $conn->prepare("
    SELECT * FROM air_quality
    WHERE ABS(latitude - ?) < 0.01 AND ABS(longitude - ?) < 0.01
      AND time <= NOW()
    ORDER BY time DESC LIMIT 1
");
$stmt->bind_param("dd", $lat, $lon);
$stmt->execute();
$latest = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$latest) {
    $latest = ["european_aqi" => null, "pm10" => 0, "pm2_5" => 0, "ozone" => 0, "birch_pollen" => 0, "grass_pollen" => 0];
}

// Zeiträume
$now = new DateTime();
$now->setTime(23, 59, 59); 

$startDate = clone $now;
$startDate->setTime(0, 0, 0);

$sqlFormat = '%Y-%m-%d';
$intervalStep = 'P1D'; 
$labelFormat = 'd.m.';   

switch ($range) {
    case '1m': $startDate->modify('-1 month'); break;
    case '3m': 
        $startDate->modify('-3 months');
        $sqlFormat = '%x-%v';
        $intervalStep = 'P1W';
        $labelFormat = '\K\W W'; 
        break;
    case '6m': 
        $startDate->modify('-6 months');
        $sqlFormat = '%x-%v';
        $intervalStep = 'P1W';
        $labelFormat = '\K\W W';
        break;
    case '1y': 
        $startDate->modify('-1 year');
        $sqlFormat = '%x-%v';
        $intervalStep = 'P1W';
        $labelFormat = '\K\W W';
        break;
    default: 
        $startDate->modify('-13 days');
        break;
}

// Template generieren
$templateData = [];
$labels = [];
$period = new DatePeriod($startDate, new DateInterval($intervalStep), $now);

foreach ($period as $dt) {
    if ($range === '3m' || $range === '6m' || $range === '1y') {
        $key = $dt->format('o-W');
    } else {
        $key = $dt->format('Y-m-d');
    }
    $templateData[$key] = null;
    $labels[] = $dt->format($labelFormat);
}

$sqlStartDate = $startDate->format('Y-m-d H:i:s');

// A) City Data
$sqlCity = "
    SELECT DATE_FORMAT(time, '$sqlFormat') as timeKey, AVG(european_aqi) as val
    FROM air_quality
    WHERE ABS(latitude - ?) < 0.01 AND ABS(longitude - ?) < 0.01
      AND time >= ? AND time <= NOW()
    GROUP BY timeKey
";
$stmt = $conn->prepare($sqlCity);
$stmt->bind_param("dds", $lat, $lon, $sqlStartDate);
$stmt->execute();
$res = $stmt->get_result();

$cityDataFilled = $templateData;
while ($r = $res->fetch_assoc()) {
    $k = $r['timeKey'];
    if (array_key_exists($k, $cityDataFilled)) {
        $cityDataFilled[$k] = clean_val($r['val']);
    }
}
$stmt->close();

// B) Swiss Data
$sqlSwiss = "
    SELECT DATE_FORMAT(time, '$sqlFormat') as timeKey, AVG(european_aqi) as val
    FROM air_quality
    WHERE time >= ? AND time <= NOW()
    GROUP BY timeKey
";
$stmt = $conn->prepare($sqlSwiss);
$stmt->bind_param("s", $sqlStartDate);
$stmt->execute();
$res = $stmt->get_result();

$swissDataFilled = $templateData;
while ($r = $res->fetch_assoc()) {
    $k = $r['timeKey'];
    if (array_key_exists($k, $swissDataFilled)) {
        $swissDataFilled[$k] = clean_val($r['val']);
    }
}
$stmt->close();

// Trimmen (Zukunft abschneiden)
$finalCityVals = array_values($cityDataFilled);
$finalSwissVals = array_values($swissDataFilled);

$lastValidIndex = -1;
$totalPoints = count($labels);

for ($i = $totalPoints - 1; $i >= 0; $i--) {
    if ($finalCityVals[$i] !== null || $finalSwissVals[$i] !== null) {
        $lastValidIndex = $i;
        break;
    }
}

if ($lastValidIndex >= 0) {
    $labels = array_slice($labels, 0, $lastValidIndex + 1);
    $finalCityVals = array_slice($finalCityVals, 0, $lastValidIndex + 1);
    $finalSwissVals = array_slice($finalSwissVals, 0, $lastValidIndex + 1);
}

// Pollutants / Output
$pBirch = isset($latest["birch_pollen"]) ? (float)$latest["birch_pollen"] : 0;
$pGrass = isset($latest["grass_pollen"]) ? (float)$latest["grass_pollen"] : 0;
$maxPollen = max($pBirch, $pGrass);

$pollutants = [
    "Feinstaub (PM10)" => $latest["pm10"] ?? 0,
    "Feinstaub (PM2.5)" => $latest["pm2_5"] ?? 0,
    "Ozon" => $latest["ozone"] ?? 0,
    "Pollen" => $maxPollen
];
arsort($pollutants);
$mainPollutant = array_key_first($pollutants);

respond_json([
    "success" => true,
    "city" => $city,
    "current" => [
        "aqi" => isset($latest["european_aqi"]) ? (int)$latest["european_aqi"] : null,
        "level" => categorize_aqi(isset($latest["european_aqi"]) ? (int)$latest["european_aqi"] : null),
        "main_pollutant" => $mainPollutant,
        "pollen" => $maxPollen
    ],
    "history" => [
        "labels" => $labels,
        "city_values" => $finalCityVals,
        "swiss_values" => $finalSwissVals
    ]
]);
?>