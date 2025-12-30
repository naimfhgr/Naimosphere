<?php
// transform.php

function transformData($apiData, $lat, $lon) {

    $result = [];

    if (!isset($apiData["hourly"]["time"])) {
        return [];
    }

    $times = $apiData["hourly"]["time"];
    $aqi   = $apiData["hourly"]["european_aqi"]    ?? [];
    $pm10  = $apiData["hourly"]["pm10"]            ?? [];
    $pm25  = $apiData["hourly"]["pm2_5"]           ?? [];
    $ozone = $apiData["hourly"]["ozone"]           ?? [];
    $birch = $apiData["hourly"]["birch_pollen"]    ?? [];
    $grass = $apiData["hourly"]["grass_pollen"]    ?? [];

    $count = count($times);

    for ($i = 0; $i < $count; $i++) {

        $timeRaw = $times[$i];                 // 2025-12-02T00:00
        $timeSql = str_replace("T", " ", $timeRaw) . ":00"; // 2025-12-02 00:00:00

        $result[] = [
            "time"         => $timeSql,
            "latitude"     => (float)$lat,
            "longitude"    => (float)$lon,
            "european_aqi" => $aqi[$i]   ?? null,
            "pm10"         => $pm10[$i]  ?? null,
            "pm2_5"        => $pm25[$i]  ?? null,
            "ozone"        => $ozone[$i] ?? null,
            "birch_pollen" => $birch[$i] ?? null,
            "grass_pollen" => $grass[$i] ?? null,
        ];
    }

    return $result;
}
?>