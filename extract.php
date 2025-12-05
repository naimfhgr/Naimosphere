<?php
// extract.php
// Holt Rohdaten aus der Open-Meteo Air-Quality-API

function extractData($lat, $lon) {

    $url = "https://air-quality-api.open-meteo.com/v1/air-quality?"
         . "latitude=$lat&longitude=$lon"
         . "&hourly=european_aqi,pm10,pm2_5,ozone,birch_pollen,grass_pollen"
         . "&timezone=Europe%2FZurich";

    $raw = file_get_contents($url);

    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);

    if ($data === null) {
        return null;
    }

    return $data;
}

// Test: Nur wenn extract.php direkt im Browser aufgerufen wird
if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {

    header("Content-Type: application/json; charset=utf-8");

    $lat = 47.38;
    $lon = 8.54;

    $data = extractData($lat, $lon);

    if ($data === null) {
        http_response_code(500);
        echo json_encode(["error" => "API nicht erreichbar oder JSON-Fehler"]);
    } else {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>
