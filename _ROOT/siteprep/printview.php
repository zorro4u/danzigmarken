<?php
namespace Dzg\SitePrep;
use Dzg\Tools\{Database, Auth, Tools};

date_default_timezone_set('Europe/Berlin');
session_start();

require_once __DIR__.'/../tools/loader_tools.php';


/**
 * die gesamte Datenbank als Druckversion anzeigen
 * dadurch die Möglichkeit, die Ausgaben als PDF-drucken zu speichern
 */
class PrintView
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
        $sql = "SELECT thema FROM dzg_dirsub2";

        $theme_array = Database::sendSQL($sql, [], 'fetchall', 'num');

        // [[x],[y],...] -> [x,y,...]
        foreach ($theme_array as $entry_array) {
            $list []= $entry_array[0];
        }
        $theme_list = array_reverse($list);

        self::$theme_list = $theme_list;

        return $theme_list;
    }


    protected static function idListeHolen(string $theme=''): array
    {
        $col = "dat.id oid, dat.id did, sta.id sid";
        $col = "dat.id";
        $idlist = [];

        if (!empty($_SESSION['filter'])) {
            $filter = (str_contains($_SESSION['filter'], "the.thema"))
                ? "{$_SESSION['filter']}"
                : "{$_SESSION['filter']} AND the.thema='{$theme}'";
        } else {
            $filter = "the.thema='{$theme}' AND dat.deakt=0";
        }

        $sort = (!empty($_SESSION['sort']))
            ? "{$_SESSION['sort']}, "
            : "the.id DESC, ";
        $sort0 = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";
        $sort .= $sort0;

        #$filter = "the.thema='{$theme}' AND ort.deakt=0";
        #$sort = "the.id, sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14,
        #sta.kat15, sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23,
        #sta.id, dat.id";

        $sql = "SELECT {$col}
            FROM dzg_file dat
            LEFT JOIN dzg_group sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 the ON the.id=dat.id_thema
            LEFT JOIN dzg_dirsub2 sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_dirliste dir ON dir.id=dat.id_dirliste
            LEFT JOIN dzg_filesuffix suf ON suf.id=dat.id_suffix
            LEFT JOIN dzg_dirsub1 sub1 ON sub1.id
            LEFT JOIN dzg_fileprefix pre ON pre.id_sub1=sub1.id
            WHERE {$filter} AND pre.prefix='t_' AND dat.print=1
            ORDER BY {$sort}";

        $result = Database::sendSQL($sql, [], 'fetchall', 'num');

        // [[x],[y],...] -> [x,y,...]
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
        $stmt = "SELECT * FROM dzg_katbezeichnung";
        $result = Database::sendSQL($stmt, [], 'fetchall', 'num');
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
        $sort = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        $stmt = "WITH
            dzgfile AS (
                SELECT id_group FROM dzg_file WHERE id=:id)

            SELECT
                dat.id fid, sta.id gid, dat.name, the.thema,
                dat.changed changed, dat.created created, sta.changed s_changed, sta.created s_created,
                list.webroot, sub1.sub1, sub2.sub2, pre.prefix, dat.name, suf.suffix,
                sta.*, dat.*
            FROM dzg_file AS dat
            LEFT JOIN dzg_group AS sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
            LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_dirliste AS list ON list.id=dat.id_dirliste
            LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix
            LEFT JOIN dzg_dirsub1 AS sub1 ON sub1.id
            LEFT JOIN dzg_fileprefix AS pre ON pre.id_sub1=sub1.id
            WHERE sta.id IN (SELECT * FROM dzgfile)
                AND dat.deakt=0
            ORDER BY {$sort}";

        $data = [':id' => $akt_file_id];    # int
        $results = Database::sendSQL($stmt, $data, 'fetchall');

        // Abfrage verarbeiten
        //
        if (empty($results)) {
            $error_arr []= "ID not found ... #{$akt_file_id}";

        } else {
            // Gruppen-ID
            $gid = (int)$results[0]['gid'];

            $i = $j = 0;
            $ffn = [];

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



