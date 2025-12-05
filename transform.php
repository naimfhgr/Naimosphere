<?php
// transform.php
// Bereitet API-Daten für die Datenbank vor

function transformData($apiData, $lat, $lon) {

    $result = [];

    if (!isset($apiData["hourly"]["time"])) {
        return [];
    }

    $count = count($apiData["hourly"]["time"]);

    for ($i = 0; $i < $count; $i++) {

        // Zeit von "2025-12-02T00:00" zu "2025-12-02 00:00:00"
        $timeRaw = $apiData["hourly"]["time"][$i];
        $timeSql = str_replace("T", " ", $timeRaw) . ":00";

        $result[] = [
            "time"         => $timeSql,
            "latitude"     => (float)$lat,
            "longitude"    => (float)$lon,
            "european_aqi" => isset($apiData["hourly"]["european_aqi"][$i]) ? (int)$apiData["hourly"]["european_aqi"][$i] : 0,
            "pm10"         => isset($apiData["hourly"]["pm10"][$i])        ? (float)$apiData["hourly"]["pm10"][$i]        : 0,
            "pm2_5"        => isset($apiData["hourly"]["pm2_5"][$i])       ? (float)$apiData["hourly"]["pm2_5"][$i]       : 0,
            "ozone"        => isset($apiData["hourly"]["ozone"][$i])       ? (float)$apiData["hourly"]["ozone"][$i]       : 0,
            "birch_pollen" => isset($apiData["hourly"]["birch_pollen"][$i])? (int)$apiData["hourly"]["birch_pollen"][$i]  : 0,
            "grass_pollen" => isset($apiData["hourly"]["grass_pollen"][$i])? (int)$apiData["hourly"]["grass_pollen"][$i]  : 0,
        ];
    }

    return $result;
}

// Optionaler Test: nur bei Direktaufruf im Browser
if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Content-Type: application/json; charset=utf-8");
    require_once "extract.php";

    $lat = 47.38;
    $lon = 8.54;
    $apiData = extractData($lat, $lon);
    $rows = transformData($apiData, $lat, $lon);

    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
