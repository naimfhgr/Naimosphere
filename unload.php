<?php
// unload.php
// Liefert Daten aus air_quality als JSON für das Frontend

require_once "config.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

// Parameter: wie viele Einträge?
$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 48;
if ($limit <= 0 || $limit > 500) {
    $limit = 48;
}

// Neueste Werte zuerst
$sql = "
    SELECT 
        id, time, latitude, longitude,
        european_aqi, pm10, pm2_5, ozone,
        birch_pollen, grass_pollen
    FROM air_quality
    ORDER BY time DESC
    LIMIT $limit
";

$res = $conn->query($sql);

if (!$res) {
    http_response_code(500);
    echo json_encode(["error" => "DB-Fehler: " . $conn->error]);
    exit;
}

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
