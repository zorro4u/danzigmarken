<?php
namespace Dzg;
use Dzg\Tools\{Auth, Tools};

date_default_timezone_set('Europe/Berlin');
session_start();

require_once __DIR__.'/../sitedata/printview.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * die gesamte Datenbank als Druckversion anzeigen
 * dadurch die Möglichkeit, die Ausgaben als PDF-drucken zu speichern
 */
class PrintviewPrep
{
    protected static array $theme_list;
    protected static int $akt_file_id;
    protected static int $akt_file_idx;
    protected static array $spaltennamen;
    protected static array $stamps;
    protected static int $max_file;
    protected static int $gid;
    protected static bool $show_form;
    protected static string $status_message;


    protected static function siteEntryCheck()
    {
        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: /auth/login.php");
            exit;
        }

        // Nutzer kein Admin? Dann auch weg hier ...
        if ((int)$_SESSION['su'] !== 1) {
            header("location: {$_SESSION['lastsite']}");
            exit;
        }
    }


    protected static function themeList(): array
    {
        if (empty(self::$theme_list)) {
            self::setthemeList();
        }
        return self::$theme_list;
    }
    protected static function setthemeList()
    {
        self::$theme_list = self::themeListeHolen();
    }
    protected static function themeListeHolen(): array
    {
        $theme_array = PrintviewData::getTheme();

        // [[x],[y],...] -> [x,y,...]
        $list = [];
        foreach ($theme_array as $entry_array) {
            $list []= $entry_array[0];
        }
        $theme_list = array_reverse($list);

        self::$theme_list = $theme_list;

        return $theme_list;
    }


    protected static function idListeHolen(string $theme=''): array
    {
        $result = PrintviewData::getIDlist($theme);

        // [[x],[y],...] -> [x,y,...]
        $idlist = [];
        foreach ($result as $entry_array) {
            $idlist []= $entry_array[0];
        }
        return $idlist;
    }


    protected static function dataPreparation(int $file_id)
    {
        $error_arr = [];
        $success_msg = "";

        $akt_file_id = $file_id;
        self::$akt_file_id = $akt_file_id;

        // im Formular anzuzeigende DB-Spalten (incl. Reihenfolge); Name muss mit DB übereinstimmen
        $show_db_spalten = [
            'thema',  // 'Thema',
            'kat10',  // 'Postamt',
            'kat11',  // 'AMT',
            'datum',  // 'Datum',
            'kat12',  // 'StempNr.',
            'kat13',  // 'Wolff',
            'kat14',  // 'Michel',
            'kat15',  // 'Frankatur',
            'kat16',  // 'Zielort',
            'kat17',  // 'Notiz.1',
            #'kat18',  // '',
            #'kat19',  // '',
            'kat20',  // 'Ansicht',
            'kat21',  // 'Attest',
            #'kat22',  // 'Notiz.2',
            #'kat23',  // 'Bildherkunft',
            #'kat24',  // '',
            ##'created', // 'erfasst',
            ##'changed', // 'geändert',
            ##'gid',     // 'Gruppen.ID',
            'fid',     // 'id',
            #'name',     // 'Dateiname',
        ];

        // anzuzeigende Bezeichnung für Spaltenkategorie; überschreibt DB-Bezeichnung
        $nameof_spalten = [
            #'name'  => 'Datei',
            'fid'   => 'Bild.ID',
            'gid'   => 'Gruppen.ID',
            'thema' => 'Thema',
            'datum' => 'Datum',
            'kat23' => 'Bildherkunft',
            'created' => 'erfasst',
            'changed' => 'bearbeitet',
        ];

        // Kateg.bezeichnung (kat10-kat29) aus DB holen
        $result = PrintviewData::getKat();

        foreach ($result AS $entry)
            $nameof_col_db[$entry[1]] = $entry[2];

        // beides zusammenführen php-Init/DB
        $col_array = [];
        // anzuzeigende Spalten laut in php-Init. (thema, datum, kat0...)
        foreach ($show_db_spalten as $i) {

            // wenn dafür ein Nameneintrag in DB existiert,
            if (isset($nameof_col_db[$i]))
                // diesen verwenden / Vorrang (bei Verwendung elseif)
                $col_array[$i] = $nameof_col_db[$i];

            // wenn aber Bezeichnung für Spalte auch hier in php-Init. vergeben,
            if (isset($nameof_spalten[$i]))
                // diesen verwenden / Vorrang (bei Verwendung if)
                $col_array[$i] = $nameof_spalten[$i];

            // wenn nirgends ein Name vergeben wurde
            if (!isset($nameof_col_db[$i]) && !isset($nameof_spalten[$i]))
                $col_array[$i] = '[ - nix - ]';
        }
        self::$spaltennamen = $col_array;


        //-------------------------------------------------
        // Hauptdatenabfrage
        // alle Dateien der angegebenen Gruppen.ID holen
        //
        $results = PrintviewData::getMainData($akt_file_id);

        // Abfrage verarbeiten
        //
        if (empty($results)) {
            $error_arr []= "ID not found ... #{$akt_file_id}";

        } else {
            // Gruppen-ID
            $gid = (int)$results[0]['gid'];

            $i = $j = 0;
            $ffn = [];
            $stamps = [];

            foreach ($results as $k=>$v) {

                // für die aktuelle Datei die 5 Fullfilenames zusammensetzen,
                // original, large, medium, small, thumb
                // $ffn['original'=>... , 'large'=> ... , ...]
                // webroot / sub1 / sub2 / prefix datei suffix
                // data / original / Lochungen / 1_Dzg1_LO_1921-01-15.jpg

                // small -> export.medium
                // (Anzeigebilder ändern -> auszudruckende Bildqualität ohne Wasserzeichen)
                if ($v['sub1'] == 'small') {
                    $ffn['small'] = $v['webroot'].'/export.medium/'.$v['sub2'].
                                    '/m_'.$v['name'].$v['suffix'];
                    #$ffn['small'] = $v['webroot'].'/large/'.$v['sub2'].
                    #   '/l_'.$v['name'].$v['suffix'];
                } else {
                    $ffn[$v['sub1']] = $v['webroot'].'/'.$v['sub1'].'/'.$v['sub2'].'/'.
                                        $v['prefix'].$v['name'].$v['suffix'];
                }

                $j++;   // Bildzähler

                // nach 5 Durchläufen pro Datei (idx=4) (original, large, ...)
                // das Summen-Array der ffn speichern
                if ($j % 5 == 0 and $j > 0) {

                    // einen der 5 DB-Ergebnisse pro Datei in ein seperates Haupt-Array speichern
                    $stamps[$i] = $v;

                    // komplette ffn-Liste dem Haupt-Array anhängen
                    $stamps[$i] += $ffn;

                    // Index-Nr der aktuellen Datei in der Gruppe ermitteln
                    if ($v['fid'] == $akt_file_id)
                        self::$akt_file_idx = $i;

                    $i++;    // Dateizähler
                    $ffn = [];
                }
            }
            self::$stamps = $stamps;

            // Anzahl Dateien/Bilder pro Gruppe
            self::$max_file = $i;     # count($stamps);

            // Gruppen-ID global setzen
            self::$gid = $gid;
        }

        $error_msg = (!empty($error_arr))
            ? implode("<br>", $error_arr)
            : "";

        self::$show_form = ($error_msg === "") ? True : False;
        self::$status_message = Tools::statusOut($success_msg, $error_msg);
    }

}

