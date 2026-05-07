<?php
namespace Dzg\Importer;

require_once __DIR__.'/database.php';
require_once __DIR__.'/categories.php';
require_once __DIR__.'/filehandler.php';

require_once __DIR__.'/../tools/auth.php';
use Dzg\Tools\Auth;

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



    /**
     * Startpunkt für Aufruf von einer Webseite aus,
     * mit Authent-Check
     *
     * - 1: (neue) Dateien in Excel-Liste speichern
     * - 2: (neue) Daten aus Excel-Liste in DB speichern
     * - 3: von (neuen) Excel-Daten webpics erstellen
     * - 4: DB in Excel speichern / Backup
    */
    public static function start(?int $switch = 1): void
    {
        // Nutzer nicht angemeldet oder kein Admin, dann weg hier ...
        if (!Auth::isCheckedIn() || $_SESSION['su'] != 1) {
            #header("location: /auth/login.php"); exit;
            header('HTTP/1.0 403 Forbidden');
            echo "Forbidden";
            exit();
        };

        if ($switch == 1) {
            self::step1();

        } elseif ($switch == 2) {
            self::step2();

        } elseif ($switch == 3) {
            self::step3();

        } elseif ($switch == 4) {
            self::step4();
        };
    }
}


// EOF