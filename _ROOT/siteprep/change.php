<?php
namespace Dzg\SitePrep;
use Dzg\SitePrep\Details;
use Dzg\SiteData\ChangeData as Data;
use Dzg\Tools\Auth;

require_once __DIR__.'/details.php';
require_once __DIR__.'/../sitedata/change.php';
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
    protected static array $abfrage_db;

    protected static function siteEntryCheck()
    {
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
        $results = Data::getDropEntry();

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

