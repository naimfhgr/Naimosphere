# NAIMOSPHERE

## Kurzbeschreibung

NAIMOSPHERE ist ein datenjournalistisches Projekt zur Visualisierung der Luftqualität in der Schweiz. Das System sammelt automatisiert stündliche Messwerte über die Open-Meteo Air Quality API. Der Backend-Prozess folgt strikt dem ETL-Prinzip: Daten werden per Cronjob abgerufen (**Extract**), bereinigt und normalisiert (**Transform**) und historisiert in einer MariaDB gespeichert (**Load**).

Die Frontend-Applikation lädt diese Daten aus der eigenen Datenbank (**Unload**) und bereitet sie visuell auf. Ziel ist es, nicht nur Zahlen zu zeigen, sondern die Luftqualität durch kontextbezogene Empfehlungen (Sport, Gesundheit, Pollen) journalistisch einzuordnen und eine verständliche Data-Story zu erzählen.

## Architektur & Technologien

### Backend

- **Sprache:** PHP (genutzt für API-Calls via cURL).
- **Datenbank-Anbindung:** MySQLi (Object-Oriented) mit **Prepared Statements** für maximale Sicherheit gegen SQL-Injections.
- **Datenbank:** MariaDB (Relationale Speicherung). Nutzung von `INSERT IGNORE` zur Vermeidung von Duplikaten und **Transaktionen** zur Beschleunigung des Schreibprozesses.
- **Server:** Apache (Infomaniak Hosting).
- **Automatisierung:** Serverseitiger Cronjob für stündliche Updates.

### Frontend

- **Tailwind CSS:** Wurde für das Styling gewählt, um vorhandene Vorkenntnisse effizient zu nutzen und ein responsives, modernes UI schnell umzusetzen.
- **Leaflet.js:** Für die interaktive Kartenansicht der Messstationen.
- **Chart.js:** Zur Visualisierung der historischen Verläufe (Vergleich Stadt vs. Schweiz-Durchschnitt).
- **W3.org:** Für die SVG Icons in unserem Projekt.

## Learnings

- **ETL-Prozesse:** Implementierung eines robusten "Extract-Transform-Load"-Workflows in PHP ohne externe Frameworks.
- **Datenbank-Performance:** Nutzung von **Transaktionen** (`begin_transaction` / `commit`) im Load-Prozess, um hunderte Datensätze effizient in einem Rutsch zu speichern, statt die Datenbank mit einzelnen Anfragen zu blockieren.
- **Query-Optimierung:** Lösung des "N+1 Query Problems" im API-Endpoint (`unload.php`), um die Serverlast bei der Übersichtskarte zu minimieren (Reduktion von N Abfragen auf 2).
- **Sicherheit:** Trennung von Code und Credentials durch den Einsatz von `config.local.php` (Server) und `.gitignore` zum Schutz von Datenbank-Passwörtern im Repository.
- **API-Handling:** Umgehung von Server-Restriktionen (z.B. `allow_url_fopen` bei Infomaniak) durch den Einsatz von cURL und effizientes Error-Handling.

## Schwierigkeiten

- **Datenkonsistenz:** Unterschiedliche API-Felder mussten bereinigt und fehlende Werte (NULL) in der Datenbank typensicher behandelt werden (insbesondere beim Binding der Parameter).
- **Chart-Rendering:** Das korrekte Handling von Datenlücken ("SpanGaps") und das Vermeidung von "Flickering" beim Neuladen der Charts erforderte eine robuste Logik im JavaScript.
- **Server-Konfiguration:** Die Einrichtung des Cronjobs auf dem Hosting-Server erforderte Workarounds bezüglich absoluter Pfade (`chdir(__DIR__)`) und der Datenbank-Verbindung.

## KI-Nutzung & Hilfsmittel

Gemäss den Vorgaben wurde KI (ChatGPT/Gemini) als "Coding-Coach" eingesetzt für:

- Debugging von spezifischen Fehlermeldungen (SQL Syntax, Cronjob-Pfade).
- Erklärung von Best-Practices im Bereich Datenbank-Sicherheit (Prepared Statements) und Effizienz (Transaktionen).
- Die Logik, das Konzept und die Architektur des Projekts wurden von den Studierenden selbst entwickelt.

## Benutzte Ressourcen

- Open-Meteo Air Quality API
- PHP Dokumentation (MySQLi, cURL)
- Chart.js & Leaflet.js Dokumentation
- TailwindCSS Docs
- FHGR Unterrichtsmaterialien (ETL, Datenbanken, GitHub Template)

## Projektlinks & Informationen

**Projektname:**
NAIMOSPHERE

**Gruppenmitglieder:**

- Ali Tas (ali.tas@stud.fhgr.ch)
- Naim El Amri Fernandez (naim.elamrifernandez@stud.fhgr.ch)

**API-Quelle:**

- [Open-Meteo Air Quality API](https://open-meteo.com/en/docs/air-quality-api)

**Design & Prototyp:**

- [Figma Prototyp](https://www.figma.com/proto/Q2UsQwyPx6wiHlQGTmV7RW/IM3?page-id=0%3A1&node-id=1-2&viewport=224%2C148%2C0.34&t=0LHIwvolOYdPdLNV-1&scaling=scale-down&content-scaling=fixed&starting-point-node-id=1%3A2)

**GitHub Repository:**

- [https://github.com/naimfhgr/Naimosphere/](https://github.com/naimfhgr/Naimosphere/)

**Live-Projekt:**

- [https://im3.naimelamri.ch](https://im3.naimelamri.ch)
