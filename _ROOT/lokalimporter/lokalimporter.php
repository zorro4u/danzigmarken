<?php
namespace Dzg\Import;

require_once __DIR__.'/database.php';
require_once __DIR__.'/categories.php';
require_once __DIR__.'/filehandler.php';

session_start();
date_default_timezone_set('Europe/Berlin');

/*
TODO:
Gleiche Dateinamen in versch. Verzeichnissen! Fehler?

Solchen Fehler abfangen und in error-Datei speichern zur Auswertung
---> findDuplicate(name)

komplett:
aus Verzeichnis Dateien lesen,
Dateinamen korrigieren (Leerz, Groß/Klein, etc.)
kategorisieren (splitten) entspr. Tiefstrich
csv-Datei erstellen
--> csv-Datei in Excel bearbeiten (Kategorien: Schreibfehler, Zuordnung, etc.)
csv-Datei einlesen
Web-Bilder erzeugen
in DB schreiben

...
war ursprünglich in python, jetzt nach php transferiert und in Webseite integriert.
*/



/**
 * zentrale Klasse für den Lokalimport
 *
 * -1- (neue) Dateien in Excel-Liste speichern
 * -2- (neue) Daten aus Excel-Liste in DB speichern
 * -3- von (neuen) Excel-Daten webpics erstellen
 * -4- DB in Excel speichern / Backup
 */
class LokalImporter
{
    /**
     * -1- (neue) Dateien in Excel-Liste speichern
     */
    public static function step1()
    {
        $stampsA = Categories::make_list();
        $stampsN = Database::neue_liste_name($stampsA);

        FileHandler::catlist2excel($stampsA);
        #Categories::catlist2excel($stampsN);

        #FileHandler::open_excel();

        #echo count($stampsA);
        #echo count($stampsN);
    }

    /**
     * -2- (neue) Daten aus Excel-Liste in DB speichern
     */
    public static function step2()
    {
        #$stampsA = Categories::make_list();
        #FileHandler::catlist2excel($stampsA);

        $stampsE = FileHandler::read_excel();
        #Database::store_list_to_maria($stampsE);
    }

    /**
     * -3- von (neuen) Excel-Daten webpics erstellen
     */
    public static function step3()
    {
        ## ImageChanger::webimages_from_filelist(FileHandler::read_excel())
        #$stampsE = FileHandler::read_excel();
        #ImageChanger::webimages_from_filelist($stampsE)
    }

    /**
     * -4- DB in Excel speichern / Backup
     */
    public static function step4()
    {
        #Database::make_backup();
        Database::make_backup(dirname(Init::$fullpath_excelfile).Init::SEP.'db_backup.xlsx', false);
    }

}

// EOF