<?php
namespace Dzg\SitePrep;
use Dzg\Tools\{Database, Auth, Tools};

require_once __DIR__.'/../tools/loader_tools.php';

/*
-- MVC Design --
Model,          Data
View,           Ansicht
Controller,     Steuerung: Data <--> Ansicht
*/


/***********************
 * Summary of Details
 */
class Details
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static int $akt_file_id;
    protected static int $akt_file_idx;
    protected static array $spaltennamen;
    protected static array $stamps;
    protected static int $max_file;
    protected static int $gid;
    protected static int $prev;
    protected static int $next;
    protected static array $error_arr;
    protected static bool $show_form;
    protected static string $status_message;


    function __construct() {}
    function __destruct() {}


    /***********************
     * Summary of siteEntryCheck
     *
     * Seitenaufruf nur mit file_id möglich.
     * Test, ob ID eine korrekte Zahl ist.
     */
    protected static function siteEntryCheck()
    {
        $error_arr = [];
        $status = true;
        $akt_file_id = 0;

        if (!isset($_GET['id'])) {
            $error_msg = 'Seite ohne ID-Angabe funktioniert nicht.';

        } else {
            $get_id = $_GET['id'];

            // Plausi-Check file_ID ...
            //
            // mariadb: (abs)BIGINT: 2^64-1, 18.446.744.073.709.551.615 > 18.44*10^18,
            // mariadb: BIGINT: 2^64/2-1, +-9.223.372.036.854.775.807 > 9.22*10^18,
            // mariadb: (abs)INT: 2^32-1, 4.294.967.295 > 4.29*10^9,
            // mariadb: INT: 2^32/2-1, +-2.147.483.647 > 2.14*10^9,
            //
            // php, int max: 2^64, 9.223.372.036.854.775.807, 9.22*10^18, 9.22E+18,
            // dann Umwandlung in float/double
            // [0-9]:Zahl mit 1 bis 19 Ziffern, 10^19-1, zu große Zahl > php.int.max
            // [0-9]:Zahl mit 1 bis 18 Ziffern,  10^18-1, zu kleine Zahl < php.int.max
            //
            // Umwandlung in (int) ergibt bei string mit Buchst -> Zahl ohne Buchstaben,
            // Vgl mit Originalstring dann negativ.
            // Zahl grösser 9*10^18 (php.int.max) wird autom. in double/float gewandelt,
            // die Wandlung von (float) in (int) ergibt -> neg. Integerzahl,
            // Wert-Vgl mit Original dann negativ.
            #var_dump(PHP_INT_MAX);

            // Zahl mit 1 bis 19 Ziffern, 10^19-1, in php aber nur 9*10^18 als Integer möglich
            $regex_digi    = "/^\d{1,19}$/";
            if (!preg_match($regex_digi, $get_id)
                || $get_id != (int)$get_id)
            {
                $error_arr []= "uups, a wrong ID ... u'r out";
                $status = false;

            } else {
                $akt_file_id = (int)$get_id;
            }
        }
        unset($_GET['id']);

        // globale Variablen setzen
        $_SESSION['fileid'] = $akt_file_id;
        self::$akt_file_id = $akt_file_id;
        self::$error_arr = $error_arr;
        self::$show_form = $status;
    }


    /***********************
     * Summary of checkGroupID
     */
    protected static function checkGroupID(int $gid=0)
    {
        // Nutzer nicht angemeldet?
        if (!Auth::isCheckedIn()) {

            // wenn nicht angemeldet und nicht von der Hauptseite kommend,
            // und aktuelles Bild nicht der letzten Gruppe angehört,
            // dann unregulärer Seitenaufruf -> Startseite
            if (empty($_SESSION['main']))
                $_SESSION['main'] = "/";

            $return2 = ['index', 'index2'];
            if (!isset($_SERVER['HTTP_REFERER']) OR
                !in_array(pathinfo($_SERVER['HTTP_REFERER'])['filename'], $return2))
            {
                if (empty($_SESSION['groupid']) || $gid <> $_SESSION['groupid']) {
                    header("location: {$_SESSION['main']}");
                    exit;
                }
            }
        }
    }


    /***********************
     * ermittelt Vorgänger und Nachfolger der akt. Seite
     *
     * !!! in PDO\SQLite funktioniert nicht die sql-Funktion: ROW_NUMBER() OVER (ORDER BY ...)
     * bei direktem DB-Zugriff (ohne PDO) funktioniert es
     */
    protected static function siteJump(int $gid): array
    {
        // $_SESSION['start'],$_SESSION['proseite'], $_SESSION['filter'], $_SESSION['sort'],
        // $_SESSION['idx2'], $_SESSION['fileid']

        #$fids = [-1,-1];

        if (isset($_SESSION['start'], $_SESSION['proseite'])) {
            $start = $_SESSION['start'];
            $proseite = $_SESSION['proseite'];
        } else {
            $start = 0;
            $proseite = 5;
        }

        //-------------------------------------------------
        // Filter/Sortierung
        //
        $filter = (!empty($_SESSION['filter']))
            ? "{$_SESSION['filter']}"
            : "the.id<>0 AND sta.deakt=0 AND dat.deakt=0";

        $sort = (!empty($_SESSION['sort']))
            ? "{$_SESSION['sort']},"
            : "the.id DESC,";

        // $sort String in Array wandeln, Leerz. und 'print' entfernen, als String rückwandeln
        $sort_arr = explode(",", $sort);
        foreach ($sort_arr as $v) {
            if (!strpos($v, "print")) {
                $tmp []= trim($v);
            }
        }
        $sort = implode(", ", $tmp);

        $sort0 = " sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23";
            #, sta.id, dat.id";
        $sort .= $sort0;

        // $sort String in Array wandeln, Leerz. und die ersten 4 Zeichen entfernen
        $tmp = [];
        $sort_arr = explode(",", $sort);
        foreach ($sort_arr as $v) {
            $tmp []= substr(trim($v), 4);
        }
        // Array in String rückwandeln und am Anfang 'id' ersetzen
        $tmp = implode(", ", $tmp);
        $sort1 = 'thema'.substr($tmp, 2);

        $select = "dat.id fid, sta.id gid, the.id thema,
            sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20, dat.kat21, dat.kat22, dat.kat23";

        //-------------------------------------------------
        // Einzel- oder Gruppen-Liste
        //
        $mainpage2 = isset($_SESSION['idx2'])
            ? $_SESSION['idx2']
            : False;

        if ($mainpage2) {
            // Gruppen-Liste, index2.php
            $current_id = $gid;
            $sql_filler1 = "";
            $sql_filler2 = "-- ";
        }
        else {
            // Einzel-Liste, index.php
            $current_id = (int)$_SESSION['fileid'];
            $sql_filler1 = "-- ";
            $sql_filler2 = "";
        }

        // SQL für [prev, curr, next]
        $stmt = "WITH

        main AS (
        -- Haupttabelle-Abfrage + filter (+ sortierung)
        SELECT {$select}
        FROM dzg_file AS dat
            LEFT JOIN dzg_group AS sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
            LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_dirliste AS list ON list.id=dat.id_dirliste
            LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix
        WHERE {$filter}
        -- Gruppen-Liste
        {$sql_filler1} GROUP BY sta.id
        -- wenn Ausgabe begrenzt werden soll, dann ORDER/LIMIT,
        -- geht dann aber nicht über Seitengrenzen hinweg
        ORDER BY {$sort}
        -- LIMIT :start, :proseite
        ),

        cte AS (
        SELECT
            -- mit SQL-Funktion die Zeilennummer der Haupttabellenabfrage bestimmen,
            -- die Reihenfolge der zu nummerierenden Zeilen durch ORDER festgelegen,
            -- aus 'filter' zuvor die ersten 4 Zeichen entfernen!
            ROW_NUMBER() OVER (ORDER BY {$sort1}) AS row_num,
            fid, gid
        FROM main),

        -- Zeilennummer der aktuellen ID aus 'cte' holen
        current AS (
        SELECT row_num FROM cte
        -- für Gruppen-Liste
        {$sql_filler1} WHERE gid = :id
        -- für Einzel-Liste
        {$sql_filler2} WHERE fid = :id)

        -- Datenabfrage (prev, curr, next)
        SELECT fid, gid FROM cte
        WHERE
            cte.row_num >= (SELECT row_num-1 FROM current) AND
            cte.row_num <= (SELECT row_num+1 FROM current);
        ";
        #  AND pre.prefix='t_'

        // Datenabfrage Einzel-, Gruppen-ID (fid, gid)
        $data = [':id' => $current_id];     # int
        $results = Database::sendSQL($stmt, $data, 'fetchall', 'num');

        // results = [ Bild_VOR=>[fid,sid], Bild_AKT=>[fid,sid], Bild_NACH=>[fid,sid]]
        switch (count($results)) {

            // kein Ergebnis, durch geändertes Thema ('Bearbeiten')
            // dann bisherige Einzel-Sprungmarken nehmen
            case 0:
                $results = [[-1,-1],[-1,-1]];
                if (!empty($_SESSION['prev']) &&
                    $_SESSION['prev'] != $_SESSION['fileid'])
                {
                        $results[0] = [$_SESSION['prev'], -1];
                }
                if (!empty($_SESSION['next']) &&
                    $_SESSION['next'] != $_SESSION['fileid'])
                {
                        $results[1] = [$_SESSION['next'], -1];
                }
            break;

            // kein Vorgänger/Nachfolger (ausgrenzende Suche)
            case 1:
                $results = [[-1,-1],[-1,-1]];
            break;

            // wenn am Anfang/Ende der Liste, dann hat Array nur zwei Elemente.
            // Werte entsprechend zuordnen und Marker setzen.
            case 2:
                // kein Vorgänger
                if ($results[0][0] == $_SESSION['fileid']) {
                    // Listenanfang modifizieren, 1.Element '-1' setzen
                    $results[0] = [-1, -1];
                } else {
                    // Listenende modifizieren. letztes Element '-1' setzen
                    $results[1] = [-1, -1];
                };
            break;

            // Vorgänger - Aktuell_(löschen) - Nachfolger
            default:
                unset($results[1]);
        }

        // Ergebnisse in Einzel-Arrays speichern
        // results = [ Bild_VOR=>[fid,sid], Bild_NACH=>[fid,sid]]
        foreach ($results AS $k=>$v) {
            $fids []= $v[0];    # file_id's     [$prev, $next]
            $gids []= $v[1];    # group_id's
        }

        // $fids: [$prev, $next]
        return $fids;
    }


    /***********************
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        // Initialisierung
        self::$akt_file_idx = 0;
        self::$spaltennamen = [];
        self::$stamps = [];
        self::$max_file = 0;
        self::$gid = 0;
        self::$prev = -1;
        self::$next = -1;
        self::$status_message = "";

        $show_form = self::$show_form;
        $error_arr = self::$error_arr;
        $success_msg = "";

        // Seiten-Check okay, Seite starten
        if ($show_form):

        $akt_file_id = self::$akt_file_id;
        if (!isset($_SESSION['start'])) $_SESSION['start'] = 0;


        // abfragende Adresse
        $remaddr = $_SERVER['REMOTE_ADDR'];

        // Test auf Heimnetz
        $addr = substr($remaddr, 0, 11);
        $home = ['192.168.11.',];
        $home = ['192.168.10.','192.168.11.'];
        foreach ($home AS $chk) {
            if ($addr == $chk) $homechk = True;
        }


        // im Formular anzuzeigende DB-Spalten (incl. Reihenfolge); Name muss mit DB übereinstimmen
        $show_db_spalten = [
            'thema',  // 'Thema',
            'kat10',  // 'Postamt',
            'kat11',  // 'AMT',
            'datum',  // 'Datum',
            'kat12',  // 'StempNr.',
            'kat13',  // 'Wolff',
            #'kat14',  // 'Michel',  # ALT!!
            'kat15',  // 'Frankatur',
            'kat16',  // 'Zielort',
            'kat17',  // 'Notiz.1', # Gruppe
            #'kat18',  // '',
            #'kat19',  // '',
            'kat20',  // 'Ansicht',
            'kat21',  // 'Attest',
            #'kat22',  // 'Notiz.2', # Bild
            'kat23',  // 'Michel',  # NEU!!
            #'kat24',  // '',
            'created', // 'erfasst',
            'changed', // 'geändert',
            'gid',     // 'Gruppen.ID',
            'fid',     // 'Bild.ID',
            'print',   //
        ];

        // anzuzeigende Bezeichnung für Spaltenkategorie; überschreibt DB-Bezeichnung
        $nameof_spalten = [
            'created' => 'erfasst',
            'changed' => 'bearbeitet',
            'print'   => 'druckbar',
            'gid'   => 'Gruppen.ID',
            'fid'   => 'Bild.ID',
            'thema' => 'Thema',
            'datum' => 'Datum',
            'kat17' => 'Notiz',
            #'kat23' => 'Michel',
        ];

        // Kateg.bezeichnung (kat10-kat29) aus DB holen
        $stmt = "SELECT * FROM dzg_katbezeichnung";
        $result = Database::sendSQL($stmt, [], 'fetchall', 'num');

        foreach ($result AS $entry) {
            $nameof_col_db[$entry[1]] = $entry[2];
        }

        // beides zusammenführen php-Init/DB
        $col_array = [];
        // anzuzeigende Spalten laut in php-Init. (thema, datum, kat0...)
        foreach ($show_db_spalten as $i) {

            // wenn dafür ein Nameneintrag in DB existiert,
            if (isset($nameof_col_db[$i])) {
                // diesen verwenden / Vorrang (bei Verwendung elseif)
                $col_array[$i] = $nameof_col_db[$i];
            }

            // wenn aber Bezeichnung für Spalte auch hier in php-Init. vergeben,
            #elseif (isset($nameof_spalten[$i]))
            if (isset($nameof_spalten[$i])) {
                // diesen verwenden / Vorrang (bei Verwendung if)
                $col_array[$i] = $nameof_spalten[$i];
            }

            if (!isset($nameof_col_db[$i]) && !isset($nameof_spalten[$i])) {
                // wenn nirgends ein Name vergeben wurde
                $col_array[$i] = '[ - nix - ]';
            }
        }

        if (!isset($_SESSION['userid']) ||
            !($_SESSION['userid']==3 || isset($_SESSION['su'])))
        {
                unset($col_array['print']);
        }

        self::$spaltennamen = $col_array;


        //-------------------------------------------------
        // Hauptdatenabfrage
        // alle Dateien mit gleicher Gruppen.ID holen
        //
        # LEFT JOIN thb.thumbs AS thb ON thb.id=ort.id_thumb ...
        # --> , thb.thumb thumb: sqlite, thumb-Blob, wird nicht benötigt
        # where AND pre.prefix='t_'

        $sort = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        $stmt = "WITH
        dzgfile AS (
            SELECT id_group FROM dzg_file WHERE id=:id)

        SELECT
            dat.id fid, sta.id gid, dat.name, the.thema,
            dat.changed changed, dat.created created, sta.changed s_changed, sta.created s_created,
            list.webroot, sub1.sub1, sub2.sub2, pre.prefix, dat.name, suf.suffix, sta.*, dat.*
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
        ORDER BY {$sort} ";

        $data = [':id' => $akt_file_id];     # int
        $results = Database::sendSQL($stmt, $data, 'fetchall');

        // Abfrage verarbeiten
        //
        if (empty($results)) {
            $error_arr []= "ID not found ... #{$akt_file_id}";
            self::$error_arr = $error_arr;

        } else {
            // Gruppen-ID
            $gid = (int)$results[0]['gid'];

            // auf regulären Seitenzugriff prüfen, ggf Exit
            self::checkGroupID($gid);

            // Gruppen-ID global setzen
            $_SESSION['groupid'] = self::$gid = $gid;

            // Vorbereitung für Sprungmarken-Ermittlung
            if (!empty($_SESSION['jump'][$gid])) {

                // im Gruppen-Modus (idx2) Sprung zur nächsten Gruppe
                $tmp_fid = array_key_first($_SESSION['jump'][$gid]);
                $tmp_jump = $_SESSION['jump'][$gid][$tmp_fid];

            } else {
                // wenn keine Sprungmarken vorhanden,
                // dann auf -1 setzen -> keine Anzeige der Navi-Pfeile
                $tmp_jump = [-1, -1];
            }

            $i = $j = 0;
            $ffn = [];

            foreach ($results as $k=>$v) {

                // für die aktuelle Datei die 5 Fullfilenames zusammensetzen,
                // $ffn[original, large, ...]
                // "original" => "data/original/Lochungen/Dzg.Neufahrwasser_1920-12-14.jpg",
                // "large" => "data/large/Lochungen/l_Dzg.Neufahrwasser_1920-12-14.jpg",
                // "medium" ... , "small" ... , "thumb" ...
                // webroot / sub1 / sub2 / prefix datei suffix
                // data / original / Lochungen / 1_Dzg1_LO_1921-01-15.jpg

                // $ffn['original'=>... , 'large'=> ... , ...]
                $ffn[$v['sub1']] = $v['webroot'].'/'.$v['sub1'].'/'.$v['sub2'].'/'.
                                    $v['prefix'].$v['name'].$v['suffix'];

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
                        $akt_file_idx = $i;

                    // im Gruppen-Modus (idx2) erhalten alle Einzeldateien der Gruppe
                    // die Sprungmarken zur nächsten Gruppe
                    if (!empty($_SESSION['idx2'])) {
                        #$_SESSION['jump'][$gid][$v['fid']] = $tmp_jump;
                    }

                    $i++;    # Dateizähler
                    $ffn = [];
                }

            }


            // die beiden 'changed'-Einträge (Datei, Gruppe) zusammenfassen,
            // es wird der Neueste angezeigt
            if (isset($akt_file_idx)) {
                # beide leer, nix
                if (empty($stamps[$akt_file_idx]['changed']) &&
                    empty($stamps[$akt_file_idx]['s_changed']))
                {

                # datei leer
                } elseif (empty($stamps[$akt_file_idx]['changed']) &&
                    !empty($stamps[$akt_file_idx]['s_changed']))
                {
                    $stamps[$akt_file_idx]['changed'] = $stamps[$akt_file_idx]['s_changed'];

                # gruppe leer
                } elseif (empty($stamps[$akt_file_idx]['s_changed']) &&
                    !empty($stamps[$akt_file_idx]['changed']))
                {

                # datei älter als gruppe, gruppenwert übernehmen
                } elseif (strtotime($stamps[$akt_file_idx]['changed']) <
                    strtotime($stamps[$akt_file_idx]['s_changed']))
                {
                    $stamps[$akt_file_idx]['changed'] = $stamps[$akt_file_idx]['s_changed'];
                }
            }

            self::$stamps = $stamps;

            // Anzahl Dateien/Bilder pro Gruppe
            self::$max_file = $i;     # count($stamps);
            self::$akt_file_idx = $akt_file_idx;


            //-------------------------------------------------
            // Navi-Pfeile: Rück-/Vorsprung Seiten ermitteln
            //

            // Sprungmarken (prev, next) seitenübergreifend per SQL ermitteln
            [$prev, $next] = self::siteJump($gid);

            $_SESSION['prev'] = ($prev > -1) ? $prev : $akt_file_id;  # wird für 'lastsite' benötigt
            #$_SESSION['next'] = ($next > -1) ? $next : $akt_file_id;
            #$_SESSION['prev'] = $prev;
            $_SESSION['next'] = $next;

            self::$prev = $prev;
            self::$next = $next;

            # if (empty($_SESSION['mariaDB'])) {} else {}
            // SQLite
            // Sprungmarken (prev, next) per Hauptabfrage ermittelt
            // nur für die jeweilige Seite
            # [$prev, $next] = (!empty($_SESSION['jump'][$gid][$akt_file_id]))
            # ? $_SESSION['jump'][$gid][$akt_file_id]
            # : [-1, -1];


            //-------------------------------------------------
            // Rücksprung zur Hauptseite setzen, für das Navi-Menü
            //
            if (!empty($_SESSION['main'])) {

                // Rücksprung wenn möglich zur entspr. ID
                // start ist im Zweifel schon auf 0 gesetzt worden
                if (!empty($_SESSION['prev']) && $_SESSION['start'] > 0) {
                    $_SESSION['lastsite'] =
                        $_SESSION['main']."?start=".$_SESSION['start'].'#'.$_SESSION['prev'];

                // prev aber start = 0
                } elseif (!empty($_SESSION['prev']) && empty($_SESSION['start'])) {
                    $_SESSION['lastsite'] = $_SESSION['main'].'#'.$_SESSION['prev'];

                // wenn start ohne prev warum auch immer gesetzt
                } elseif (empty($_SESSION['prev']) && $_SESSION['start'] > 0) {
                    $_SESSION['lastsite'] = $_SESSION['main']."?start=".$_SESSION['start'];

                // wenn nur main warum auch immer gesetzt
                } else {
                    $_SESSION['lastsite'] = $_SESSION['main'];
                }

            // wenn nix bekannt, dann Standard-Einstiegsseite
            } else {
                $_SESSION['lastsite'] = "/";
            }

            // wenn nicht angemeldet, dann Standard-Einstiegsseite
            if (!Auth::isCheckedIn()) {
                if (empty($_SESSION['main'])) $_SESSION['main'] = "/";
                $_SESSION['lastsite'] = $_SESSION['main'];
            }
        }       # ID gefunden
        endif;  # Seiten-Check okay

        $error_msg = (!empty($error_arr))
            ? implode("<br>", $error_arr)
            : "";

        self::$show_form = ($error_msg === "") ? true : false;
        self::$status_message = Tools::statusOut($success_msg, $error_msg);
    }
}