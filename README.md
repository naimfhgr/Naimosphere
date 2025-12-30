# NAIMOSPHERE

## Kurzbeschreibung
NAIMOSPHERE ist ein datenjournalistisches Projekt zur Visualisierung der Luftqualität in der Schweiz. Das System sammelt automatisiert stündliche Messwerte über die Open-Meteo Air Quality API. Der Backend-Prozess folgt strikt dem ETL-Prinzip: Daten werden per Cronjob abgerufen (**Extract**), bereinigt und normalisiert (**Transform**) und historisiert in einer MariaDB gespeichert (**Load**).

Die Frontend-Applikation lädt diese Daten aus der eigenen Datenbank (**Unload**) und bereitet sie visuell auf. Ziel ist es, nicht nur Zahlen zu zeigen, sondern die Luftqualität durch kontextbezogene Empfehlungen (Sport, Gesundheit, Pollen) journalistisch einzuordnen und eine verständliche Data-Story zu erzählen.

## Architektur & Technologien

### Backend
* **Sprache:** PHP (genutzt für API-Calls via cURL und Datenbank-Operationen mit PDO)
* **Datenbank:** MariaDB (Relationale Speicherung mit Primärschlüsseln zur Vermeidung von Duplikaten und inkrementellem Aufbau der Historie)
* **Server:** Apache (Infomaniak Hosting)
* **Automatisierung:** Serverseitiger Cronjob für stündliche Updates

### Frontend
* **Tailwind CSS:** Wurde für das Styling gewählt, um vorhandene Vorkenntnisse effizient zu nutzen und ein responsives, modernes UI schnell umzusetzen.
* **Leaflet.js:** Für die interaktive Kartenansicht der Messstationen.
* **Chart.js:** Zur Visualisierung der historischen Verläufe (Vergleich Stadt vs. Schweiz-Durchschnitt).

## Learnings
* **ETL-Prozesse:** Implementierung eines robusten "Extract-Transform-Load"-Workflows in PHP ohne externe Frameworks.
* **Datenbank-Historisierung:** Umstellung von `TRUNCATE`-Methoden auf eine inkrementelle `INSERT IGNORE`-Logik, um eine echte Langzeit-Historie aufzubauen, die über das 14-Tage-Limit der API hinausgeht.
* **Sicherheit:** Trennung von Code und Credentials durch den Einsatz von `config.local.php` (Server) und `.gitignore` zum Schutz von Datenbank-Passwörtern im Repository.
* **API-Handling:** Umgehung von Server-Restriktionen (z.B. `allow_url_fopen` bei Infomaniak) durch den Einsatz von cURL und effizientes Error-Handling.

## Schwierigkeiten
* **Datenkonsistenz:** Unterschiedliche API-Felder mussten bereinigt und fehlende Werte (NULL) in der Datenbank korrekt typisiert werden.
* **Server-Konfiguration:** Die Einrichtung des Cronjobs auf dem Hosting-Server erforderte Workarounds bezüglich absoluter Pfade und der Datenbank-Verbindung (`localhost` vs. `127.0.0.1`).
* **Synchronisation:** Sicherstellung, dass Frontend-Visualisierung und Backend-Datenbestand zeitlich synchron laufen (Zeitzonen-Handling).

## KI-Nutzung & Hilfsmittel
Gemäss den Vorgaben wurde KI (ChatGPT/Gemini) als "Coding-Coach" eingesetzt für:
* Debugging von spezifischen Fehlermeldungen (Load.php, SQL-Verbindungen, Cronjob-Pfade).
* Erklärung von Best-Practices im Bereich ETL und Datenbank-Design.
* Die Logik, das Konzept und die Architektur des Projekts wurden von den Studierenden selbst entwickelt.

## Benutzte Ressourcen
* Open-Meteo Air Quality API
* PHP Dokumentation (PDO, cURL)
* Chart.js & Leaflet.js Dokumentation
* TailwindCSS Docs
* FHGR Unterrichtsmaterialien (ETL, Datenbanken, GitHub Template)

## Projektlinks & Informationen

**Projektname:**
NAIMOSPHERE

**Gruppenmitglieder:**
* Ali Tas (ali.tas@stud.fhgr.ch)
* Naim El Amri Fernandez (naim.elamrifernandez@stud.fhgr.ch)

**API-Quelle:**
* [Open-Meteo Air Quality API](https://open-meteo.com/en/docs/air-quality-api)

**Design & Prototyp:**
* [Figma Prototyp](https://www.figma.com/proto/Q2UsQwyPx6wiHlQGTmV7RW/IM3?page-id=0%3A1&node-id=1-2&viewport=224%2C148%2C0.34&t=0LHIwvolOYdPdLNV-1&scaling=scale-down&content-scaling=fixed&starting-point-node-id=1%3A2)

**GitHub Repository:**
* [https://github.com/naimfhgr/Naimosphere/](https://github.com/naimfhgr/Naimosphere/)

**Live-Projekt:**
* [https://im3.naimelamri.ch](https://im3.naimelamri.ch)
