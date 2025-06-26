# Code der Webseite www.danzigmarken.de #

- Tabellendarstellung der Datenbankeinträge
  (mit Paging, Searching, Selecting)
- Einzeldarstellung
- Einzelbearbeitung
- Anmeldefunktion
- Nutzerbereich
- Druckaufbereitung

Eine `php` `html/css` - 'self made' - Seite.
Es wird größtenteils auf `js` verzichtet.
Die Hauptbilder liegen im Pfad `/data` jeweils in verschiedenen Web-Größen vor
(thumbnail, small, medium, large), hier natürlich nicht hochgeladen.
Ebenso die Datei mit den Zugangsdaten für Datenbank und Mailserver (`account_data.php`, `chmod 0600`).
Das `_ROOT`-Verzeichnis befindet sich außerhalb von `DOCUMENT_ROOT`, damit die Scripte
nicht von extern erreichbar sind. ... Projekt entwickelt sich im 'lerning-by-doing' Modus.

---
Das Befüllen der Datenbank mit neuen Elementen geschieht (noch) in einem anderen, externen Prozess.
