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
- Air-Quality-API  
- PHP (PDO), MariaDB  
- Chart.js  
- TailwindCSS  
- Figma-Prototyp  
- FHGR ETL-Unterlagen  
- GitHub-Repositories aus dem Unterricht  

## Projektlinks  
**Projektname:**  
NAIMOSPHERE

**Gruppenmitglieder:**  
- Ali Tas (ali.tas@stud.fhgr.ch)  
- Naim El Amri Fernandez (naim.elamrifernandez@stud.fhgr.ch)

**API-URLs:**  
- https://open-meteo.com/en/docs/air-quality-api?ref=freepublicapis.com

**Figma-URL:**  
 - https://www.figma.com/proto/Q2UsQwyPx6wiHlQGTmV7RW/IM3?page-id=0%3A1&node-id=1-2&viewport=224%2C148%2C0.34&t=0LHIwvolOYdPdLNV-1&scaling=scale-down&content-scaling=fixed&starting-point-node-id=1%3A2

**Projekt-URL:**  
- https://212043-1.web.fhgr.education/
