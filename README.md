# NAIMOSPHERE
## Kurzbeschreibung
Dieses Projekt sammelt automatisiert Luftqualitätsdaten über die Air-Quality-API. Die Daten werden regelmässig per Cronjob abgerufen (Extract), bereinigt und angereichert (Transform) und danach in einer eigenen MariaDB gespeichert (Load). Die Webseite lädt die gespeicherten Werte wieder aus der Datenbank (Unload) und visualisiert die Entwicklung der Luftqualität journalistisch in einer Data-Story.

## Learnings
- API-Daten automatisiert abrufen und strukturiert verarbeiten  
- ETL-Prozesse in PHP mit PDO korrekt umsetzen  
- Datenbankmodell für zeitabhängige Messwerte erstellen  
- Cronjobs konfigurieren und für Datenzyklen nutzen  
- Saubere Verbindung zwischen Backend und Frontend herstellen  

## Schwierigkeiten
- Unterschiedliche API-Felder bereinigen und transformieren  
- Koordinaten zuverlässig Standorten zuordnen
- Fehlerbehandlung bei API-Timeouts oder leeren Antworten  
- Datenzyklen und Visualisierung zeitlich synchronisieren  

## Benutzte Ressourcen
### Ressourcen für Recherche, Planung und Coding
(Werkzeuge und Quellen, die wir zur Unterstützung genutzt haben)

- Moodle-Unterlagen des Moduls (inkl. FHGR-Beispiele und Erklärungen)  
- ChatGPT  
- Google Gemini  
- Perplexity  
- Einfache SQL-Referenzen und Hilfeseiten  
- Dokumentationen zu PHP, JavaScript und ETL-Grundlagen  

### Externe Ressourcen in der Webseite
(Tools und Datenquellen, die im Projekt direkt eingebunden sind)

- Open-Meteo API (Luftqualitätsdaten)  
- Leaflet.js (Kartenanzeige)  
- Chart.js (Verlaufsgrafik)  
- Google Fonts (Inter-Schrift)  
- Tailwind CSS CDN (Layout und Styling) 


## Projektlinks  
**Projektname:**  
NAIMOSPHERE

**Gruppenmitglieder:**  
- Ali Tas (ali.tas@stud.fhgr.ch)  
- Naim El Amri Fernandez (naim.elamrifernandez@stud.fhgr.ch)

**API-URL:**  
- https://open-meteo.com/en/docs/air-quality-api?ref=freepublicapis.com

**Figma-URL:**  
 - https://www.figma.com/proto/Q2UsQwyPx6wiHlQGTmV7RW/IM3?page-id=0%3A1&node-id=1-2&viewport=224%2C148%2C0.34&t=0LHIwvolOYdPdLNV-1&scaling=scale-down&content-scaling=fixed&starting-point-node-id=1%3A2

**Projekt-URL:**  
- https://212043-1.web.fhgr.education/
