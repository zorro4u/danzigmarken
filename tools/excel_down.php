<?php
session_start();
$_SESSION['siteid'] = 15;

/*
require_once __DIR__.'/includes/download.inc.php';   // zentrale Datenvorbereitung

require_once __DIR__.'/assets/templates/html-head-details.php';
require_once __DIR__.'/assets/templates/html-body.php';

require_once __DIR__.'/assets/templates/download.temp.php';

require_once __DIR__.'/assets/templates/footer.temp.php';
*/

require_once $_SERVER['DOCUMENT_ROOT'].'/functions/database.func.php';     // Datenbank-Verbindung

// php composer.phar update
// php composer.phar install
// php composer.phar dump-autoload
require_once $_SERVER['DOCUMENT_ROOT'].'/static/vendor/autoload.php';
#use avadim\FastExcelReader\Excel as rExcel;
use avadim\FastExcelWriter\Excel as wExcel;


Download::excelDownload();




/**
 * Summary of Download
 */
class Download
{
    // Vordefinierte Spaltennamen der DB-Tabelle,
    // die auch für csv-Datei genutzt werden.
    public const DB_SPALTEN = [
        "thema", "datum", "webroot", "localroot", "sub1", "sub2", "name", "suffix",
        "dat_create", "dat_change", "dat_access", "ort_deakt", "ort_ghost", "dat_deakt",
        "dat_ghost", "sta_deakt", "sta_ghost", "sta_mirror", "oid", "did", "sid", "tid", "speicherort",
    ];
    public const KAT_COUNT = 20;      // um diese Zahl werden die Spalten erweitert
    public const KAT_TEXT = "kat";    // Bezeichner
    public const SEP = "\\";



    // ______________
    public static function dbSpalten() :array
    {
        if (empty(self::$db_spalten)) {
            self::setDBspalten();
        }
        return self::$db_spalten;
    }
    protected static function setDBspalten() :void
    {
        // Sp0/Sp1 (thema, datum) aufheben
        $tmp1 = array_slice(self::DB_SPALTEN, 0, 2);

        // ab Sp2 (dateiname) alles auslagern
        $tmp2 = array_slice(self::DB_SPALTEN, 2);

        // kat10..29 array bilden
        for ($zaehler=0; $zaehler < self::KAT_COUNT; $zaehler++) {
            $kat_array []= self::KAT_TEXT.(string)($zaehler + 10);
        }

        // gesicherten Spalten am Ende wieder anhängen
        self::$db_spalten = array_merge($tmp1, $kat_array, $tmp2);
    }
    private static array $db_spalten;



    // Spaltennamen, kommt aus excel_read
    public static function colName() :array
    {
        if (empty(self::$col_name)) {
            self::setColName(self::dbSpalten());
        }
        return self::$col_name;
    }
    protected static function setColName(array $value) :void
    {
        self::$col_name = $value;
    }
    private static array $col_name;



    // ______________
    protected static function siteEntryCheck()
    {
        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!isset($_SESSION['userid'])) {
            header("location: /auth/login.php");
            exit;
        }

        // Nutzer kein Admin? Dann auch weg hier ...
        if ((int)$_SESSION['su'] !== 1) {
            header("location: {$_SESSION['lastsite']}");
            exit;
        }
    }



    // ______________
    /**
     * importiert den gesamten DB-Bestand
     */
    public static function bestandsabfrage()
    {
        $col = "the.sub2 thema, sta.datum,
            sta.kat10, sta.kat11, sta.kat12, sta.kat13, sta.kat14,
            sta.kat15, sta.kat16, sta.kat17, sta.kat18, sta.kat19,
            dat.kat20, dat.kat21, dat.kat22, dat.kat23, dat.kat24,
            dat.kat25, dat.kat26, dat.kat27, dat.kat28, dat.kat29,
            dir.webroot, sub1.sub1, sub2.sub2, ort.name, suf.suffix,
            ort.dat_create, ort.dat_change, ort.dat_access,
            ort.id oid, dat.id did, sta.id sid";

        $filter = (!empty($_SESSION['filter']))
            ? $_SESSION['filter']
            : 'ort.deakt=0';

        $sort = (!empty($_SESSION['sort']))
            ? "{$_SESSION['sort']},"
            : "the.id DESC, sta.kat10, sta.datum,";

        $sort0 = " sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        $sort1 = "the.id, sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        $sort .= $sort0;

        if (isset($_SESSION['start'], $_SESSION['proseite'])) {
            $start = $_SESSION['start'];
            $proseite = $_SESSION['proseite'];
        } else {
            $start = 0;
            $proseite = 5;
        }

        $stmt1 = "SELECT {$col}
            FROM dzg_fileplace ort
            LEFT JOIN dzg_file dat ON dat.id=ort.id_datei
            LEFT JOIN dzg_group sta ON sta.id=dat.id_stamp
            LEFT JOIN dzg_dirsub2 the ON the.id=dat.id_thema
            LEFT JOIN dzg_dirsub2 sub2 ON sub2.id=ort.id_sub2
            LEFT JOIN dzg_dirliste dir ON dir.id=ort.id_dirliste
            LEFT JOIN dzg_filesuffix suf ON suf.id=ort.id_suffix
            LEFT JOIN dzg_dirsub1 sub1 ON sub1.id
            LEFT JOIN dzg_fileprefix pre ON pre.id_sub1=sub1.id
            WHERE {$filter} AND pre.prefix='t_'
            ORDER BY {$sort}";
# LIMIT :start, :proseite";

        $dblist = Database::sendSQL($stmt1, [], 'fetchall', 'num');

        // DB-Spaltennamen holen
        $stmt2 = $stmt1." LIMIT 1";
        $colname = array_keys(Database::sendSQL($stmt2, [], 'fetch'));

        // Kat-Bezeichnung holen
        $stmt3 = "SELECT spalte, bezeichnung FROM dzg_katbezeichnung ORDER BY spalte";
        $kat_name = Database::sendSQL($stmt3, [], 'fetchall', 'num');

        // und tauschen
        foreach ($kat_name as $v) {
            for ($i=0; $i < count($colname); ++$i) {
                if ($colname[$i] == $v[0]) {
                    $colname[$i] = $v[1];
                }
            }
        }

        // Speicherort (thumb) bilden und an DB-Liste hängen, [webroot,sub1,sub2,name,suffix]
        $colname []= "thumbnail";
        for ($i=0; $i < count($dblist); ++$i)
        {
            $ffnparts = array_slice($dblist[$i], 22, 5);
            $suff = array_pop($ffnparts);
            $name = array_pop($ffnparts);
            array_push($ffnparts, "t_".$name.$suff);
            $dblist[$i] []= implode(self::SEP, $ffnparts);

            // erzeugt den Fullfilename der Thumb-Datei aus den einzelnen Komponenten
            #$dblist[$i]['thumb'] = $dblist[$i]['webroot'].'/'.$dblist[$i]['sub1'].'/'.$dblist[$i]['sub2'].'/'.$dblist[$i]['prefix'].$dblist[$i]['name'].$dblist[$i]['suffix'];
        }

        // Rückgabe, 1.Zeile: Spaltennamen, Rest: Daten
        #return array_merge([$colname], $dblist);

        self::setColName($colname);
        return $dblist;
    }



    /**
     * FastExcelWriter, Datei wird immer neu angelegt
     */
    public static function writeExcel(array $datalist, string $fullfilename) :bool
    {
        $status = false;

        if (!empty($datalist)) {
            $header = self::colName();        # Spaltennamen aus excel-import
            $header_row = [];
            foreach ($header as $colname) {
                $header_row[$colname] = "@";   # als 'Text' formatiert
            }
            $row_options = ['font-style' => 'bold'];

            $excel = wExcel::create(['dzg']);
            $sheet = $excel->getsheet('dzg');
            $sheet->writeHeader($header_row, $row_options);
            $sheet->writeArrayTo('A2', $datalist);

            $excel->save($fullfilename);

            $status = true;
        }
        return $status;
    }



    // ______________
    public static function excelDownload()
    {
        self::siteEntryCheck();

        $now = date('YmdHis');  # SQL datetime format "Y-m-d H:i:s"
        $today = date('Ymd');

        $file_dir = __DIR__."/../download";
        $file_name = $now;
        $file_ext = ".xlsx";
        $ffn = $file_dir.'/'.$file_name.$file_ext;
        #$file = basename($ffn);

        $file_save_as = "dzg-list_".$today.$file_ext;

        $data = self::bestandsabfrage();
        self::writeExcel($data, $ffn);

        if (file_exists($ffn)) {
            ob_clean();
            // Download header
            // Redirect output to a client’s web browser (Excel2007)
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename=\"{$file_save_as}\"");
            header("Cache-Control: max-age=0");
            // If you're serving to IE 9, then the following may be needed
            header("Cache-Control: max-age=1");

            // If you're serving to IE over SSL, then the following may be needed
            header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
            header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header ('Pragma: public'); // HTTP/1.0

            // ---
            #header('Content-Type: application/octet-stream');
            #header("Content-Type:  application/vnd.ms-excel");
            #header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            #header("Cache-Control: private",false);
            header("Content-Description: File Transfer");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($ffn));

            flush();
            readfile($ffn);
            #rename($ffn, $file_dir.'/'.$now.$file_ext);      # Datei umbenennen
            #unlink($ffn);                                    # Datei löschen
        }
    }
}

