<?php
namespace Dzg\SitePrep;
use Dzg\SitePrep\Details;
use Dzg\Tools\{Database, Auth};

require_once __DIR__.'/details.php';
require_once __DIR__.'/../tools/database.php';
require_once __DIR__.'/../tools/auth.php';


/***********************
 * Summary of Change
 * Webseite:
 *
 * __public__
 * show()
 */
class Change extends Details
{
    protected static $pdo;
    protected static array $abfrage_db;


    protected static function siteEntryCheck()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connectMyDB();
        }

        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: /auth/login.php");
            exit;
        }

        // PlausiCheck Seiten-ID
        parent::siteEntryCheck();
    }


    protected static function dataPreparation()
    {
        // Details Hauptroutine ausführen
        parent::dataPreparation();

        // Seiten-Check bisher okay
        if (self::$show_form):

        # self:: kommt hier von Details
        $max_file = self::$max_file;
        $spaltennamen = self::$spaltennamen;
        $abfrage_db = [];

        // diese Einträge nicht anzeigen
        unset($spaltennamen['created']);
        unset($spaltennamen['changed']);

        // noch zusätzliche Spalte (im Vgl. zur Detail-Seite) zur Ausgabe anhängen
        #$spaltennamen += ['kat23' => 'Bildursprung'];
        $spaltennamen += ['print' => 'druckbar'];

        // die Spalte(n) am Schluss ausgeben
        $tmp_prn['print'] = $spaltennamen['print'];
        unset($spaltennamen['print']);
        $spaltennamen += $tmp_prn;


        // wenn eine Bildgruppe besteht, dann IDs anzeigen, um Gruppe ändern zu können
        if ($max_file > 1) {
            #$spaltennamen['fid'] = 'Bild.ID';
            #$spaltennamen['gid'] = 'Marken.ID';
        }

        // zusätzliche Vorbereitungen wegen Formularverwendung
        //
        // DropDown-Bezeichnungen holen für Thema, Frankatur, Ansicht, Attest
        $stmt = "WITH
        thema AS (SELECT thema FROM dzg_dirsub2 ORDER BY thema),
        kat15 AS (SELECT kat15 FROM dzg_kat15 ORDER BY kat15),
        kat20 AS (SELECT kat20 FROM dzg_kat20 ORDER BY kat20 DESC),
        kat21 AS (SELECT kat21 FROM dzg_kat21 ORDER BY kat21)
        SELECT * FROM thema, kat15, kat20, kat21 ";

        $results = Database::sendSQL($stmt, [], 'fetchall', 'num');

        $qry_arr = [];
        // abfrage_array nach Spalten (select Statement) aufsplitten
        foreach ($results AS $entry) {
            foreach($entry AS $k=>$v) {
                $qry_arr[$k] []= $v; {
                }
            }
        }

        // doppelte Einträge in den Spalten aufgrund der kombinerten DB-Abfrage löschen
        // [$theme_db, $franka_db, $ansicht_db, $attest_db] = $abfrage_db;
        foreach ($qry_arr AS $col) {
            $abfrage_db []= array_values(array_unique($col));
        }

        // temp. Hilfs-Variablen löschen
        unset($stmt, $results, $qry_arr);

        // globale Variablen setzen
        self::$spaltennamen = $spaltennamen;
        self::$abfrage_db = $abfrage_db;

        endif;      # Seiten-Check okay
    }

}


/*
$_SESSION:

array(21) {
["rootdir"]=> string(0) ""
["loggedin"]=> bool(true)
["userid"]=> int(2)
["su"]=> bool(true)
["status"]=> string(5) "activ"

["sort"]=> string(34) " the.id DESC, sta.kat10, sta.datum"
["dir"]=> string(4) " ASC" ["col"]=> string(10) " sta.kat10"
["filter"]=> string(66) "the.id IS NOT NULL AND sta.deakt=0 AND dat.deakt=0 AND ort.deakt=0"
["version"]=> string(6) "200525"

["siteid"]=> int(3)
["idx2"]=> bool(false)
["main"]=> string(11) "/index2.php"
["lastsite"]=> string(24) "/index2.php?start=45#740"

["start"]=> int(45)
["proseite"]=> int(5)
["groupid"]=> int(652)
["fileid"]=> int(741)
["prev"]=> int(740)
["next"]=> int(742)

["jump"]=> array(5) {
[982]=> array(1) { [736]=> array(2) { [0]=> int(-1) [1]=> int(737) } }
[647]=> array(1) { [737]=> array(2) { [0]=> int(736) [1]=> int(738) } }
[1062]=> array(1) { [738]=> array(2) { [0]=> int(737) [1]=> int(741) } }
[652]=> array(1) { [741]=> array(2) { [0]=> int(738) [1]=> int(742) } }
[1094]=> array(1) { [742]=> array(2) { [0]=> int(741) [1]=> int(-1) } }
}

}
*/