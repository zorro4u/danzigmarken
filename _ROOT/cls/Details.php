<?php
namespace Dzg\Cls;

// Datenbank- & Auth-Funktionen laden
#require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';

use PDO, PDOException;
#use Dzg\Cls\{Database, Auth Header, Footer};

/*
-- MVC Design --
Model,          Data
View,           Ansicht
Controller,     Steuerung: Data <--> Ansicht
*/


/***********************
 * Summary of Details
 * Webseite:
 *
 * __public__
 * show()
 */
class Details
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $pdo;
    protected static int $akt_file_id;
    protected static int $akt_file_idx;
    protected static array $spaltennamen;
    protected static array $stamps;
    protected static int $max_file;
    protected static int $gid;
    protected static int $prev;
    protected static int $next;
    protected static array $error_arr;
    protected static bool $showForm;
    protected static string $status_message;


    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connect_mariadb();
        }

        self::site_entry_check();
        self::data_preparation();

        Header::show();
        self::site_output();
        Footer::show();

        // Datenbank schließen
        self::$pdo = Null;
    }


    function __construct() {
        if (!is_object(self::$pdo)) {
            // Datenbank öffnen
            #$this->pdo = Database::connect_mariadb();
        }
    }
    function __destruct() {
        // Datenbank schließen
        unset($this->pdo);
    }


    protected static function site_entry_check()
    {
        // Plausi-Check der ID, bevor weiter gemacht wird !!
        $error_arr = [];
        $status = true;
        $akt_file_id = 0;

        if (!isset($_GET['id'])) {
            $error_msg = 'Seite ohne ID-Angabe funktioniert nicht.';

        } else {
            // Zahl mit 1 bis 19 Ziffern, 10*10^18-1, in php aber nur 9*10^18 möglich
            $get_id = $_GET['id'];
            $regex_digi    = "/^\d{1,19}$/";
            if (!preg_match($regex_digi, $get_id)
                || $get_id != (int)$get_id)
            {
                $error_arr []= 'a wrong ID transmitted ...';
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
        self::$showForm = $status;
    }


    protected static function groupID_check(int $gid=0)
    {
        // Nutzer nicht angemeldet?
        if (!Auth::is_checked_in()) {

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
    protected static function site_jump(int $gid): array
    {
        // $_SESSION['start'],$_SESSION['proseite'], $_SESSION['filter'], $_SESSION['sort'],
        // $_SESSION['idx2'], $_SESSION['fileid']

        $pdo_db = self::$pdo;
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
        $sort1 = 'theme'.substr($tmp, 2);

        $select = "dat.id fid, sta.id gid, the.id theme,
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
        $stmt = "WITH cte AS (
            -- Datenvorbreitung via Common Table Expression
            SELECT
                -- mit SQL-Funktion die Zeilennummer der Haupttabellenabfrage bestimmen,
                -- die Reihenfolge der zu nummerierenden Zeilen durch ORDER festgelegen,
                -- aus 'filter' zuvor die ersten 4 Zeichen entfernen!
                ROW_NUMBER() OVER (ORDER BY {$sort1}) AS row_num
                ,fid, gid
            FROM (

                -- Haupttabelle-Abfrage + filter (+ sortierung)
                SELECT {$select}
                FROM dzg_file AS dat
                    LEFT JOIN dzg_fileplace AS ort ON ort.id_datei=dat.id
                    LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                    LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
                    LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=ort.id_sub2
                    LEFT JOIN dzg_dirliste AS list ON list.id=ort.id_dirliste
                    LEFT JOIN dzg_filesuffix AS suf ON suf.id=ort.id_suffix
                WHERE {$filter}
                -- Gruppen-Liste
                {$sql_filler1} GROUP BY sta.id
                -- wenn Ausgabe begrenzt werden soll, dann ORDER/LIMIT,
                -- geht dann aber nicht über Seitengrenzen hinweg
                ORDER BY {$sort}
                -- LIMIT :start, :proseite
            ) t
        ),

        -- Zeilennummer der aktuellen ID aus 'cte' holen
        current AS (
            SELECT row_num FROM cte
            -- für Gruppen-Liste
            {$sql_filler1} WHERE gid = :id
            -- für Einzel-Liste
            {$sql_filler2} WHERE fid = :id
        )

        -- Datenabfrage (prev, curr, next)
        SELECT fid, gid
        FROM cte
        WHERE
            cte.row_num >= (SELECT row_num-1 FROM current) AND
            cte.row_num <= (SELECT row_num+1 FROM current);
        ";
        #  AND pre.prefix='t_'

        // Datenabfrage Einzel-, Gruppen-ID (fid, gid)
        try {
            $qry = $pdo_db->prepare($stmt);
            $qry->bindParam(":id", $current_id, PDO::PARAM_INT);
            #$qry->bindParam(":start", $start, PDO::PARAM_INT);
            #$qry->bindParam(":proseite", $proseite, PDO::PARAM_INT);
            $qry->execute();
            $results = $qry->fetchAll(PDO::FETCH_NUM);
        } catch(PDOException $e) {echo'details_site_jump: ';die($e->getMessage());}

        // Datenbank schließen
        unset($pdo_db);

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
     *
     */
    protected static function data_preparation()
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

        $pdo_db = self::$pdo;
        $showForm = self::$showForm;
        $error_arr = self::$error_arr;
        $success_msg = "";

        // Seiten-Check okay, Seite starten
        if ($showForm):

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


        // Der Name der Datenbank-Tabelle (wird nicht weiter verwendet)
        $table = [
            "thumbs", "dzg_file", "dzg_group", "dzg_dirliste", "dzg_dirsub1", "dzg_dirsub2",
            "dzg_fileprefix", "dzg_filesuffix", "dzg_katbezeichnung",];

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
            'kat17',  // 'Notiz.1', # Gruppe
            #'kat18',  // '',
            #'kat19',  // '',
            'kat20',  // 'Ansicht',
            'kat21',  // 'Attest',
            #'kat22',  // 'Notiz.2', # Bild
            #'kat23',  // 'Bildherkunft',
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
            'kat23' => 'Bildherkunft',
        ];

        // Kateg.bezeichnung (kat10-kat29) aus DB holen
        $stmt = "SELECT * FROM dzg_katbezeichnung";
        try {
            $qry = $pdo_db->query($stmt);
            $result = $qry->fetchAll();
        } catch(PDOException $e) {die($e->getMessage().': details_katbezeichnung');}


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
            $results = $qry->fetchAll(PDO::FETCH_ASSOC);  # {key}: Spaltenname
        } catch(PDOException $e) {'details_Hauptdatenabfrage: '.die($e->getMessage());}

        // Datenbank schließen
        unset($pdo_db);

        // Abfrage verarbeiten
        //
        if (empty($results)) {
            $error_arr []= "ID not found ... #{$akt_file_id}";
            self::$error_arr = $error_arr;

        } else {
            // Gruppen-ID
            $gid = (int)$results[0]['gid'];

            // auf regulären Seitenzugriff prüfen, ggf Exit
            self::groupID_check($gid);

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
                                    $v['prefix'].$v['name_orig'].$v['suffix'];

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

            self::$stamps = $stamps;

            // Anzahl Dateien/Bilder pro Gruppe
            self::$max_file = $i;     # count($stamps);
            self::$akt_file_idx = $akt_file_idx;


            //-------------------------------------------------
            // Navi-Pfeile: Rück-/Vorsprung Seiten ermitteln
            //

            // Sprungmarken (prev, next) seitenübergreifend per SQL ermitteln
            [$prev, $next] = self::site_jump($gid);

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
            if (!Auth::is_checked_in()) {
                if (empty($_SESSION['main'])) $_SESSION['main'] = "/";
                $_SESSION['lastsite'] = $_SESSION['main'];
            }
        }       # ID gefunden
        endif;  # Seiten-Check okay

        $error_msg = (!empty($error_arr))
            ? implode("<br>", $error_arr)
            : "";

        self::$showForm = ($error_msg === "") ? true : false;
        self::$status_message = Tools::status_out($success_msg, $error_msg);
    }


    /***********************
     * HTML erzeugen
     */
    protected static function site_output()
    {
        $showForm = self::$showForm;
        $status_message = self::$status_message;
        $output = "<div class='container'>";
        #$output = "<div class='grid-container-detail'>";
        #$output .= '<div class="content detail">';

        if (!$showForm):
            $output .= $status_message;
        else:

        // Seiten-Check okay, Seite starten
        $spaltennamen = self::$spaltennamen;
        $stamps = self::$stamps;
        $akt_file_id = self::$akt_file_id;
        $akt_file_idx = self::$akt_file_idx;
        $gid = self::$gid;
        $max_file = self::$max_file;
        $prev = self::$prev;
        $next = self::$next;

        $output .= $status_message;
        $output .= "<div class='center-detail'>";
        $output .= "<div class='main-detail'>";


        // linke Seite, Detail-Angaben
        //
        $output .= "
          <div class='main-detail-left'>
          <div class='detail-kat-tab'>
          <table style='padding-top: 6px;'><tbody>";

        $tfoot = 'color:hsl(0, 0%, 80%); font-size: 85%; padding-top: 15px;';
        #style='color:hsl(0,0%,45%); font-style:italic;'

        foreach ($spaltennamen as $spalte_db => $spalte_web) {

            // Fussnoten zeigen, nur wenn angemeldet
            //
            // erstellt
            if ($spalte_db === 'created') {
                // Zeit-String (yyyy-mm-dd hh:mm) in (dd.mm.yyyy hh:mm) wandeln
                $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';
                $output .= (Auth::is_checked_in())
                    ? "</tbody><tfoot>
                        <tr><td class='detail-key' style='".$tfoot."padding-bottom: 0px;'>{$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-bottom: 0px;'>
                        {$data}</td></tr>"
                    : "</tbody><tfoot></tfoot>";

            // bearbeitet
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

            // Gruppen-ID
            } elseif ($spalte_db === 'gid') {
                $data = $gid;
                $output .= (Auth::is_checked_in())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 6px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 0px;'>
                        {$data}</td></tr>"
                    : "";

            // Bild-ID
            } elseif ($spalte_db === 'fid') {
                $data = $akt_file_id;
                $output .= (Auth::is_checked_in())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 0px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 6px;'>
                        {$data}</td></tr>"
                    : "";

            // Druckoption
            } elseif ($spalte_db === 'print') {
                $data = $stamps[$akt_file_idx][$spalte_db];
                $data_rev = ($data == 1) ? 0 : 1;      # switchen
                $checked = ($data == 1) ? "checked" : "";
                # onclick='return false;' .. disabled='disabled'

                $output .= (Auth::is_checked_in())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 0px;'>
                        {$spalte_web}</td>
                        <td class='detail-val' style='".$tfoot."padding-top: 0px;'
                        title='in Druckauswahl ja/nein'>
                        <input type='checkbox' name='{$spalte_db}' id='print' class='chkbx'
                        {$checked} onclick='prn_toogle(".$akt_file_id.",".$data_rev.")' />
                        <label for='print' ></label></td></tr></tfoot>"
                    : "";

            // ab hier die Infos für alle zugänglich
            // Ansicht, mit Linie oberhalb
            } elseif ($spalte_web === 'Ansicht') {
                $data = htmlspecialchars($stamps[$akt_file_idx][$spalte_db]);
                $output .= "
                    <tr><td colspan='2' style='padding-top:6px;
                        border-bottom:1px solid hsl(0,0%,90%);'></td></tr>
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

        $output .= "</table></div>";  # Tabelle, < /detail-kat-tab >
        $output .= "</div>";  # ende linke Seite, < /main-detail-left >


        // rechte Seite, Bilder
        //
        # <img src='".htmlspecialchars($stamps[$akt_file_idx]['small'])."' width='300' height='' alt='".htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>

        $output .= "
            <div class='main-detail-right'>
            <table class=detail-pic><tbody><tr><td>
            <div class='detail-pic'>";

            if (!Auth::is_checked_in()) {
                $output .= "<a href='/".
                    htmlspecialchars($stamps[$akt_file_idx]['medium']).
                    "' title='größere Ansicht'><img src='/".
                    htmlspecialchars($stamps[$akt_file_idx]['small']).
                    "' width='300' height='' alt='".
                    htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>";

        #    } elseif ($_SESSION['userid']!=3 && empty($_SESSION['su'])) {
            } else {
                $output .= "<a href='/".
                    htmlspecialchars($stamps[$akt_file_idx]['large']).
                    "' title='große Ansicht'><img src='/".
                    htmlspecialchars($stamps[$akt_file_idx]['small']).
                    "' width='300' height='' alt='".
                    htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>";

            }
        /*
        elseif ($_SESSION['userid']==9 || !empty($_SESSION['suX'])) {
                $output .= "<a href='".
                htmlspecialchars($stamps[$akt_file_idx]['original']).
                "' title='originale Ansicht'><img src='".
                htmlspecialchars($stamps[$akt_file_idx]['small']).
                "' width='300' height='' alt='".
                htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>";
            }
        */
            $output .= "</div></td></tr></tbody>";

            /*
          <tfoot><tr><td style=''>
          <div class='pic-link'>";

          if (!Auth::is_checked_in()) {
            #$output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['small']}' title='(800x600)'>klein</button></form>";
            $output .= "<form style='margin:0; margin-top: 4px;'><a class='button btn_pic'
            style='text-decoration:none;' title='- nur angemeldet -'><i class='fas fa-lock'></i>
            mittel</a></form>";
            $output .= "<form style='margin:0; margin-top: 4px;'><a class='button btn_pic'
            style='text-decoration:none;' title='- nur angemeldet -'><i class='fas fa-lock'></i>
            groß</a></form>";

          } elseif (!isset($homechk)) {
            #$output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['small']}' title='(800x600)'>klein</button></form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['medium']}' title='(1280x1024)'>mittel</button></
            form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['large']}' title='(1920x1200)'>groß</button></
            form>";

          } elseif (isset($homechk)) {
            #$output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['medium']}' title='(1280x1024)'>mittel</button></
            form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['large']}' title='(1920x1200)'>groß</button></
            form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['original']}' title=''>original</button></form>";
          }
        */
          #$output .= "</div></td></tr></tfoot>";
          $output .= "</table>";


        //-------------------------------------------------
        // Thumbnail-Grid
        //
        // durch Laufvariable $i in der Schleife geht keine vorzeitige Variablen-Zuweisung

        // Thumb-Datei
        # '<img src="'.htmlspecialchars($stamps[$i]['webthumb']).'" width="70" height="70"
        # alt="['.htmlspecialchars($stamps[$i]['dateiname']).']">';
        # <img src='".htmlspecialchars($stamps[$akt_file_idx]['thumb'])."' width='70' height='70'
        # alt='#".$stamps[$akt_file_idx]['fid']."'>

        // Thumb-BLOB
        # '<img src="data:image/jpg;charset=utf8;base64,'.base64_encode($stamps[$i]['thumb-blob']).
        # '" width="70" height="70" alt="['.htmlspecialchars($stamps[$i]['dateiname']).']">';
        # <img src='data:image/jpg;charset=utf8;base64,".base64_encode($file['thumb-blob'])."'
        # width='70' height='70' alt='#".$file['fid']."'>

        $output .= "<div class='detail-thumb-grid detail-gal'>";

        if ($max_file > 1)
          foreach ($stamps as $idx => $file)
            if ($idx != $akt_file_idx)
                #$output .= "<div class='detail-thumb' title='#{$file['fid']}'><a href='".
                #$_SERVER['PHP_SELF']."?id={$file['fid']}'><img src='data:image/jpg;charset=utf8;#base64,".base64_encode($file['thumb-blob'])."' width='70' height='70'
                #alt='#{$file['fid']}'></a></div>";
              $output .= "<div class='detail-thumb' title='#{$file['fid']}'>
                <a href='".$_SERVER['PHP_SELF']."?id={$file['fid']}'>
                <img src='/".htmlspecialchars($file['thumb']).
                "' width='70' height='70'alt='#{$file['fid']}'></a></div>";
            else
                # $output .= "<div class='detail-thumb_akt'><img src='data:image/jpg;charset=utf8;
                # base64,".base64_encode($file['thumb-blob'])."' width='70' height='70' alt='#".
                # $file['fid']."'></div>";
              $output .= "<div class='detail-thumb_akt' title='#{$file['fid']}'>
                <img src='/".htmlspecialchars($file['thumb']).
                "' width='70' height='70' alt='#".$file['fid']."'></div>";


        $output .= "</div>";  # ende thumb-grid
        $output .= "</div>";  # ende rechte Seite, </main-detail-right>
        $output .= "</div>";  # ende </main-detail>


        // ZURÜCK .. DRUCKEN .. VOR
        //
        // - Drucken -
        $ziel = "print.php?id=".$akt_file_id;
        $btn_print0 = "<form action='{$ziel}' method='POST' style='display:inline'>".
                    "<button class='btn Xbtn-primary' type='submit' value='print'
                    name='print'><i class='fas fa-print'>&ensp;</i>Drucken</button></form>";

        $btn_print = "<button onclick='window.print();' class='noprint btn print'
                    title='Drucken'><i class='fas fa-print'>&ensp;</i>Drucken</button>";

        $label = (empty($_SESSION['idx2'])) ? "Bild" : "Gruppe";

        $output .= "<div class='fuss noprint' style='padding-top:0; padding-bottom:0;'>";
        // < &lt; > &gt;
        // long-arrow-left angle-double-left chevron-circle-left arrow-circle-left caret-square-left


        if (Auth::is_checked_in()) {
            ($prev > -1)
                ? $output .= "<div><a class='noprint' style='color:hsl(0, 0%, 45%);
                    background-color:transparent;' href={$_SERVER['PHP_SELF']}?id={$prev}
                    title='{$label} zurück: #{$prev}'><i class='fas fa-long-arrow-left'
                    style='font-size:16px; Xcolor:#9d9d9d;'></i></a></div>"
                : $output .= "<div>&nbsp;</div>";

            $output .= "{$btn_print}";
            #$output .= "{$btn_print0}";

            ($next > -1)
                ? $output .= "<div><a class='noprint' style='color:hsl(0, 0%, 45%);
                    background-color:transparent;' href={$_SERVER['PHP_SELF']}?id={$next}
                    title='{$label} vor: #{$next}'><i class='fas fa-long-arrow-right'
                    style='font-size:16px; Xcolor:#9d9d9d;'></i></a></div>"
                : $output .= "<div>&nbsp;</div>";
        }

        $output .= "</div>";   # ende </fuss>
        $output .= '<div class="onlyprint"><hr></div>';
        $output .= "</div>";   # ende </center-detail>


        ###################################################
        //
        // < BOTTOM >
        //
        /*
        $output .= "
          <div class='bottom'>
          <div style='text-align: right; color: grey; padding-right: 12px;'></div>
          </div>";   # ende < /BOTTOM >
        */

        ###################################################
        //
        // < LAST >
        //
        /*
        $output .= "<div class='last'>
        <div class='links kleingrau cc'></div>
        <div class='mitte kleingrau'></div>
        <div class='rechts kleingrau'></div>
        </div>";   # ende < /LAST >
        */

        endif;                 # showForm
        #$output .= '</div>';   # ende < /content detail >
        $output .= '</div>';   # ende < /grid-container-detail >


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;

    }



}