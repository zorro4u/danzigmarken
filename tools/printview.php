<?php
session_start();
date_default_timezone_set('Europe/Berlin');

// Datenbank- & Auth-Funktionen laden
#require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Auth.php";
require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Header.php";
use Dzg\Cls\{Database, Auth, Tools, Header};

// Seite anzeigen
PrintView::show();

// Datenbank schließen
$pdo = Null;


/***********************
 * Webseite:
 * die gesamte Datenbank als Druckversion anzeigen
 * dadurch die Möglichkeit, die Ausgaben als PDF-drucken zu speichern
 *
 * __public__
 * show()
 */
class PrintView
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $pdo;
    protected static array $sub2_list;
    private static int $akt_file_id;
    private static int $akt_file_idx;
    private static array $spaltennamen;
    private static array $stamps;
    private static int $max_file;
    private static int $gid;
    private static bool $showForm;
    private static string $status_message;


    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connect_mariadb();
        } else {
        }

        self::site_entry_check();

        Header::html_meta_load();
        self::seitenausgabe();

        // Datenbank schließen
        self::$pdo = Null;
    }


    protected static function site_entry_check()
    {
        // Nutzer angemeldet?
        if (!Auth::is_checked_in()) Auth::check_user();

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


    protected static function sub2_list(): array
    {
        if (empty(self::$sub2_list)) {
            self::set_sub2_list();
        }
        return self::$sub2_list;
    }
    protected static function set_sub2_list()
    {
        self::$sub2_list = self::sub2_liste_holen();
    }
    protected static function sub2_liste_holen(): array
    {
        $pdo_db = self::$pdo;
        $sql = "SELECT sub2 FROM dzg_dirsub2";

        $sub2_array = Database::db_fetch($sql, "all", $pdo_db);

        // [[x],[y],...] -> [x,y,...]
        foreach ($sub2_array as $entry_array) {
            $list []= $entry_array[0];
        }
        $sub2_list = array_reverse($list);

        self::$sub2_list = $sub2_list;

        // Datenbank schließen
        unset($pdo_db);
        return $sub2_list;
    }


    protected static function idliste_holen(string $sub2=''): array
    {
        $pdo_db = self::$pdo;
        $col = "ort.id oid, dat.id did, sta.id sid";
        $col = "dat.id";

        $filter = (!empty($_SESSION['filter']))
            ? "{$_SESSION['filter']}"
            : "the.sub2='{$sub2}' AND ort.deakt=0";
            #"the.id<>0 AND sta.deakt=0 AND dat.deakt=0";

        $sort = (!empty($_SESSION['sort']))
            ? "{$_SESSION['sort']}, "
            : "the.id DESC, ";
        $sort0 = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";
        $sort .= $sort0;

        #$filter = "the.sub2='{$sub2}' AND ort.deakt=0";
        #$sort = "the.id, sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14,
        #sta.kat15, sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23,
        #sta.id, dat.id";

        $sql = "SELECT {$col}
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

        $result = Database::db_fetch($sql, "all", $pdo_db);

        // [[x],[y],...] -> [x,y,...]
        foreach ($result as $entry_array) {
            $idlist []= $entry_array[0];
        }

        // Datenbank schließen
        unset($pdo_db);

        return $idlist;
    }


    protected static function data_preparation(int $file_id)
    {
        $pdo_db = self::$pdo;
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
            'kat22',  // 'Notiz.2',
            #'kat23',  // 'Bildherkunft',
            #'kat24',  // '',
            ##'created', // 'erfasst',
            ##'changed', // 'geändert',
            ##'gid',     // 'Gruppen.ID',
            'fid',     // 'id',
        ];

        // anzuzeigende Bezeichnung für Spaltenkategorie; überschreibt DB-Bezeichnung
        $nameof_spalten = [
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
        try {
            $qry = $pdo_db->query($stmt);
            $result = $qry->fetchAll();
        } catch(PDOException $e) {die($e->getMessage().': details.inc_katbezeichnung');}

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

        $stmt = "SELECT
            dat.id fid, sta.id gid, ort.name, the.sub2 thema,
            dat.changed changed, dat.created created, sta.changed s_changed, sta.created s_created,
            list.webroot, sub1.sub1, sub2.sub2, pre.prefix, ort.name_orig, suf.suffix, sta.*, dat.*
            FROM dzg_file AS dat
                LEFT JOIN dzg_fileplace AS ort ON ort.id_datei=dat.id
                LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
                LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=ort.id_sub2
                LEFT JOIN dzg_dirliste AS list ON list.id=ort.id_dirliste
                LEFT JOIN dzg_filesuffix AS suf ON suf.id=ort.id_suffix
                LEFT JOIN dzg_dirsub1 AS sub1 ON sub1.id
                LEFT JOIN dzg_fileprefix AS pre ON pre.id_sub1=sub1.id
            WHERE sta.id=(SELECT id_stamp FROM dzg_file WHERE id=:id) AND dat.deakt=0
            ORDER BY {$sort}";

        try {
            $qry = $pdo_db->prepare($stmt);
            $qry->bindParam(':id', $akt_file_id, PDO::PARAM_INT);
            $qry->execute();
            $results = $qry->fetchAll(PDO::FETCH_ASSOC);  // {key}: Spaltenname
        } catch(PDOException $e) {die($e->getMessage().': details.inc_Hauptdatenabfrage');}

        // Datenbank schließen
        unset($pdo_db);

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
                                    '/m_'.$v['name_orig'].$v['suffix'];
                    #$ffn['small'] = $v['webroot'].'/large/'.$v['sub2'].
                    #   '/l_'.$v['name_orig'].$v['suffix'];
                } else {
                    $ffn[$v['sub1']] = $v['webroot'].'/'.$v['sub1'].'/'.$v['sub2'].'/'.
                                        $v['prefix'].$v['name_orig'].$v['suffix'];
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

        self::$showForm = ($error_msg === "") ? True : False;
        self::$status_message = Tools::status_out($success_msg, $error_msg);
    }


    protected static function einzelseite_erzeugen(int $file_id): string
    {
        self::data_preparation($file_id);

        $output = self::$status_message;
        if (self::$showForm):

        $output .= '<div class="grid-container-detail">';
        $output .= '<div class="content detail">';

        // < CENTRAL >
        $output .= "<div class='center-detail'>";

        // < MAIN >
        $output .= "<div class='main-detail'>";


        // linke Seite, Detail-Angaben
        //
        $output .= "
        <div class='main-detail-left'>
        <div class='detail-kat-tab'>
        <table style='padding-top: 6px;'><tbody>";

        $tfoot = 'color:hsl(0, 0%, 90%); font-size: 80%; font-style: italic; padding-top: 15px;';

        $akt_file_idx = self::$akt_file_idx;
        $stamps = self::$stamps;

        foreach (self::$spaltennamen as $spalte_db => $spalte_web) {

            // Fussnote zeigen, wenn angemeldet
            if ($spalte_db === 'created') {
                $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';
                $output .= (Auth::is_checked_in())
                    ? "</tbody><tfoot><tr><td class='detail-key' style='".$tfoot.
                        "padding-bottom: 0px;'>{$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-bottom: 0px;'>
                       {$data}</td></tr>"
                    : "</tbody><tfoot></tfoot>";

            } elseif ($spalte_db === 'changed') {
                $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';
                $output .= ($data && Auth::is_checked_in())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 0px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 0px;'>
                       {$data}</td></tr>"
                    : "";

            } elseif ($spalte_db === 'gid') {
                $data = self::$gid;
                $output .= (Auth::is_checked_in())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 6px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 6px;'>
                       {$data}</td></tr></tfoot>"
                    : "";

            } elseif ($spalte_db === 'fid') {   #style='".$tfoot."padding-top: 8px;'
                $data = self::$akt_file_id;
                $output .= (Auth::is_checked_in())
                    ? "<tr><td class='detail-key fid' >{$spalte_web}</td>
                       <td class='detail-val fid' >{$data}</td></tr></tfoot>"
                    : "";

            // ab hier die Infos für alle
            } elseif ($spalte_web === 'Ansicht') {
                $data = htmlspecialchars($stamps[$akt_file_idx][$spalte_db]);
                $output .= "<tr><td colspan='2'
                    style='padding-top:6px;border-bottom:1px solid hsl(0,0%,90%);'></td></tr>
                    <tr><td class='detail-key' style='padding-top:6px;'>{$spalte_web}</td>
                    <td class='detail-val'>{$data}</td></tr>";

            } else {
                if ($spalte_web === 'Datum') {
                    $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';

                } else {
                    $data = (!empty($stamps[$akt_file_idx][$spalte_db]))
                        ? htmlspecialchars($stamps[$akt_file_idx][$spalte_db])
                        : '';
                }

                $output .= "
                    <tr><td class='detail-key'>{$spalte_web}</td>
                    <td class='detail-val'>{$data}</td></tr>";
            }
        }

        $output .= "</table></div>";   # Tabelle, < /detail-kat-tab >
        $output .= "</div>";           # ende linke Seite, < /main-detail-left >


        // rechte Seite, Bilder
        //
        $output .= "
            <div class='main-detail-right'>
            <table class=detail-pic><tbody><tr><td>
            <div class='detail-pic'>";

        $output .= "
            <img src='/".htmlspecialchars($stamps[$akt_file_idx]['small']).
            "' width='300' height='' alt='".
            htmlspecialchars($stamps[$akt_file_idx]['name'])."'>";

        $output .= "</div></td></tr></tbody>";
        $output .= "</table>";


        // rechte Seite, Thumbnail-Grid
        $output .= "<div class='detail-thumb-grid detail-gal'>";

        if (self::$max_file > 1) {
            foreach ($stamps as $idx => $file) {
                if ($idx != $akt_file_idx) {
                    $output .= "<div class='detail-thumb' title='#{$file['fid']}'><img src='/".
                    htmlspecialchars($file['thumb']).
                    "' width='70' height='70' alt='#{$file['fid']}'></div>";
                } else {
                    $output .= "<div class='detail-thumb_akt' title='#{$file['fid']}'><img src='/".
                    htmlspecialchars($file['thumb']).
                    "' width='70' height='70' alt='#{$file['fid']}'></div>";
                }
            }
        }
        $output .= "</div>";  # ende thumb-grid
        $output .= "</div>";  # ende rechte Seite, < /main-detail-right >
        $output .= "</div>";  # ende < /MAIN-DETAIL >

        #$output .= '<div class="onlyprint"><hr></div>';
        $output .= "</div>";   # ende < /CENTER >
        $output .= '</div>';   # ende < /content detail >
        $output .= '</div>';   # ende < /grid-container-detail >

        endif;  # Seite anzeigen

        return $output;
    }
    # class= noprint, onlyprint,


    /***********************
     * zentrale html Ausgabe
     */
    protected static function seitenausgabe()
    {
        // bei Aufruf per Admin-Seite, Filter zurücksetzen
        if (isset($_GET['sub2']) && (int)$_GET['sub2'] === 100) {
            $_SESSION['thema'] = "- alle -";
            unset($_SESSION['filter']);
        }

        $sub2_liste = (empty($_SESSION['thema']) || $_SESSION['thema'] === "- alle -")
            ? self::sub2_list()
            : $sub2_liste = [$_SESSION['thema']];

        // kontinuierliche Einzelseitenausgabe
        foreach ($sub2_liste as $thema) {
            $id_liste = self::idliste_holen($thema);

            echo "<br><hr><center>{$thema}</center><hr><br>";

            $i = 0;
            foreach ($id_liste as $id) {
                // html-Ausgabe
                echo self::einzelseite_erzeugen($id);

                // Ausgabe begrenzen
                // (komplett: 2.000 Einträge, 1.000 Seiten, 500 Blätter) max: Kork=900
                // auf eine A4 Seite passen ca. 2 Einträge (id)
                // cut = 40 Einträge/Thema --> 20 Seiten (gesamt x5: 100 Seiten, 50 Blätter)
                $id_cut = 40;
                ++$i;
                if ($i == $id_cut) break;
            }
        }
    }
}



