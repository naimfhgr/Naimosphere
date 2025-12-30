<?php
// extract.php
// Holt Rohdaten aus der Open-Meteo Air-Quality-API via cURL
// Ersetzt file_get_contents (wegen allow_url_fopen=0 bei Infomaniak)

function extractData($lat, $lon) {

    // URL zusammenbauen (wie vorher)
    $url = "https://air-quality-api.open-meteo.com/v1/air-quality?"
         . "latitude=$lat&longitude=$lon"
         . "&hourly=european_aqi,pm10,pm2_5,ozone,birch_pollen,grass_pollen"
         . "&timezone=Europe%2FZurich"
         . "&past_days=14"
         . "&forecast_days=0";

    // 1. cURL-Session initialisieren
    $ch = curl_init();

    // 2. Optionen setzen
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Antwort als String zurückgeben, nicht direkt ausgeben
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);          // Maximal 10 Sekunden warten
    curl_setopt($ch, CURLOPT_FAILONERROR, false);   // HTTP-Fehler nicht als cURL-Fehler behandeln (wir prüfen manuell)
    
    // Falls SSL-Probleme auftreten (sollte bei Infomaniak nicht nötig sein, aber zur Sicherheit):
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // 3. Ausführen
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    // 4. Session schließen
    curl_close($ch);

    // Fehlerprüfung
    if ($raw === false) {
        // Netzwerkfehler (z.B. DNS, Timeout)
        echo "   cURL Fehler: $curlError\n";
        return null;
    }

    if ($httpCode !== 200) {
        // API hat geantwortet, aber mit Fehler (z.B. 404 oder 500)
        echo "   API HTTP Status: $httpCode\n";
        return null;
    }

    // JSON dekodieren
    $data = json_decode($raw, true);

    if ($data === null) {
        echo "   JSON Decode Fehler.\n";
        return null;
    }

    return $data;
}
?>