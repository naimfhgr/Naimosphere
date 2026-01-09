<?php
// extract.php
// Holt Rohdaten aus der Open-Meteo Air-Quality-API via cURL
// Ersetzt file_get_contents (wegen allow_url_fopen=0 bei Infomaniak)

// NEU: Parameter $pastDays hinzugefügt mit Standardwert 14
function extractData($lat, $lon, $pastDays = 14) {

    // URL zusammenbauen
    // forecast_days=0 ist gut, spart Daten!
    $url = "https://air-quality-api.open-meteo.com/v1/air-quality?"
         . "latitude=$lat&longitude=$lon"
         . "&hourly=european_aqi,pm10,pm2_5,ozone,birch_pollen,grass_pollen"
         . "&timezone=Europe%2FZurich"
         . "&past_days=$pastDays" 
         . "&forecast_days=0";

    // 1. cURL-Session initialisieren
    $ch = curl_init();

    // 2. Optionen setzen
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Antwort als String zurückgeben
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);          // Maximal 10 Sekunden warten
    curl_setopt($ch, CURLOPT_FAILONERROR, false);   // HTTP-Fehler manuell prüfen
    
    // 3. Ausführen
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    // 4. Session schließen
    curl_close($ch);

    // Fehlerprüfung
    if ($raw === false) {
        echo "   cURL Fehler: $curlError\n";
        return null;
    }

    if ($httpCode !== 200) {
        echo "   API HTTP Status: $httpCode\n";
        return null;
    }

    $data = json_decode($raw, true);

    if ($data === null) {
        echo "   JSON Decode Fehler.\n";
        return null;
    }

    return $data;
}
?>