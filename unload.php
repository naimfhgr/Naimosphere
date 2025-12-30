<?php
// unload.php
require_once "config.php";

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

$mode = isset($_GET["mode"]) ? $_GET["mode"] : "detail";
$city = isset($_GET["city"]) ? trim($_GET["city"]) : "";

// --- MODUS 1: OVERVIEW ---
if ($mode === "overview") {
    $overviewData = [];
    foreach ($cities as $name => $coords) {
        $lat = $coords["lat"];
        $lon = $coords["lon"];
        
        $stmt = $conn->prepare("
            SELECT european_aqi FROM air_quality
            WHERE ABS(latitude - ?) < 0.01 AND ABS(longitude - ?) < 0.01
              AND time <= NOW()
            ORDER BY time DESC LIMIT 1
        ");
        $stmt->bind_param("dd", $lat, $lon);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        
        $aqi = $row ? (int)$row["european_aqi"] : null;
        
        $overviewData[] = [
            "city" => $name, "lat" => $lat, "lng" => $lon, "aqi" => $aqi, "status" => get_status_class($aqi)
        ];
        $stmt->close();
    }
    respond_json(["success" => true, "locations" => $overviewData]);
}

// --- MODUS 2: DETAIL ---
if ($city === "" || !array_key_exists($city, $cities)) {
    respond_json(["success" => false, "error" => "Stadt fehlt."], 400);
}

$lat = $cities[$city]["lat"];
$lon = $cities[$city]["lon"];

// 1. Aktuelle Werte
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

if (!$latest) respond_json(["success" => false, "error" => "Keine Daten"], 404);

// 2. Historie Stadt
$stmtHistory = $conn->prepare("
    SELECT DATE(time) as dayDate, AVG(european_aqi) as avgAqi
    FROM air_quality
    WHERE ABS(latitude - ?) < 0.01 AND ABS(longitude - ?) < 0.01
      AND time <= NOW()
    GROUP BY DATE(time)
    ORDER BY dayDate DESC
    LIMIT 14
");
$stmtHistory->bind_param("dd", $lat, $lon);
$stmtHistory->execute();
$histResult = $stmtHistory->get_result();
$cityHistory = [];
while ($r = $histResult->fetch_assoc()) {
    $cityHistory[$r['dayDate']] = round($r['avgAqi']);
}
$stmtHistory->close();

// 3. Historie Schweiz
$stmtSwiss = $conn->prepare("
    SELECT DATE(time) as dayDate, AVG(european_aqi) as avgAqi
    FROM air_quality
    WHERE time <= NOW()
    GROUP BY DATE(time)
    ORDER BY dayDate DESC
    LIMIT 14
");
$stmtSwiss->execute();
$swissResult = $stmtSwiss->get_result();
$swissHistory = [];
while ($r = $swissResult->fetch_assoc()) {
    $swissHistory[$r['dayDate']] = round($r['avgAqi']);
}
$stmtSwiss->close();

$labels = array_reverse(array_keys($cityHistory));
$dataCity = [];
$dataSwiss = [];
$formattedLabels = [];

foreach ($labels as $date) {
    $dataCity[] = $cityHistory[$date] ?? null;
    $dataSwiss[] = $swissHistory[$date] ?? null;
    $d = date_create($date);
    $formattedLabels[] = date_format($d, "d.m.");
}

// Pollen Maximum berechnen (Birke oder Gras)
$maxPollen = max((float)$latest["birch_pollen"], (float)$latest["grass_pollen"]);

// Schadstoff ermitteln
$pollutants = [
    "Feinstaub (PM10)" => $latest["pm10"],
    "Feinstaub (PM2.5)" => $latest["pm2_5"],
    "Ozon" => $latest["ozone"],
    "Pollen" => $maxPollen
];
arsort($pollutants);
$mainPollutant = array_key_first($pollutants);

respond_json([
    "success" => true,
    "city" => $city,
    "current" => [
        "aqi" => (int)$latest["european_aqi"],
        "level" => categorize_aqi((int)$latest["european_aqi"]),
        "main_pollutant" => $mainPollutant,
        "pollen" => $maxPollen // HIER NEU: Pollenwert senden
    ],
    "history" => [
        "labels" => $formattedLabels,
        "city_values" => $dataCity,
        "swiss_values" => $dataSwiss
    ]
]);
?>