<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\TableData as Data;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../sitedata/tabledata.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Table
 * Webseite:
 * Tabellenausgabe der Datenbank
 *
 * __public__
 * show()
 * spaltennamen()
 * getSession()
 * setSession()
 * DBspalten
 * $maxID
 * $stamps_db
 * $_session
 */
class TablePrep
{
    public static int $maxID;
    public static array $stamps_db;
    private static array $spaltennamen;
    public static array $_session;

    // Zuordnung der Spaltennamen: DatenbankTabelle -> WebTabelle
    // Reihenfolge wie Ausgabe
    public const DBspalten = [
        'sta.datum' => 'datum',
        'sta.kat10' => 'kat10',
        'sta.kat11' => 'kat11',
        'sta.kat12' => 'kat12',
        'sta.kat13' => 'kat13',
        'sta.kat14' => 'kat14',
        'sta.kat15' => 'kat15',
        'sta.kat16' => 'kat16',
        'sta.kat17' => 'kat17',
        'sta.kat18' => 'kat18',
        'sta.kat19' => 'kat19',
        'dat.kat20' => 'kat20',
        'dat.kat21' => 'kat21',
        'dat.kat22' => 'kat22',
        'dat.kat23' => 'kat23',
        'dat.kat24' => 'kat24',
        'dat.kat25' => 'kat25',
        'dat.kat26' => 'kat26',
        'dat.kat27' => 'kat27',
        'dat.kat28' => 'kat28',
        'dat.kat29' => 'kat29',
        'the.thema' => 'thema',
        'sub2.sub2' => 'sub2',
        'dat.name'  => 'name',
        'suf.suffix' => 'suffix',
        'sta.id' => 'gid',
        'dat.id' => 'fid',
        'print'  => 'print',
    ];

    protected const SPALTEN_EINZEL = [
        'thema'    => 'Thema',
        'webthumb' => 'Pic0',
        'kat10'    => 'Postamt',
        'kat11'    => 'AMT',
        'datum'    => 'Datum',
        'kat20'    => 'Ansicht',
        'kat21'    => 'Attest',
        'kat12'    => 'StempNr.',
        'kat13'    => 'Wolff',
        #'kat14'    => 'Michel',
        'kat23'    => 'Michel',
        'kat15'    => 'Frankatur',
        'kat16'    => 'Zielort',
        'kat17'    => 'Notiz',
        #'kat22'    => 'Notiz.2',
        #'kat23'    => 'Michel_NEU',
        #'kat24'    => '\'kat24\'',
        'fid'      => 'ID',
        #'gid'      => 'G.ID',
        'print'     => 'druck'
    ];

    protected const SPALTEN_GRUPPE = [
        'thema' => 'Thema',
        'kat10' => 'Postamt',
        'kat11' => 'AMT',
        'datum' => 'Datum',
        'kat12' => 'StempNr.',
        'kat13' => 'Wolff',
        #'kat14' => 'Michel',
        'kat15' => 'Frankatur',
        'kat16' => 'Zielort',
        'kat17' => 'Notiz.1',
        #'kat20    => 'Ansicht',
        #'kat21'   => 'Attest',
        #'kat24'   => '\'kat24\'',
        'webthumb' => 'Pic0',
        'gid'  => 'gID',
    ];


    /**
     * Summary of spaltennamen
     * Zuordnung der Spaltennamen: DatenbankTabelle -> WebTabelle
     * Reihenfolge -> Ausgabe
     * ... wird in TableBody verwendet
     */
     public static function spaltennamen(): array
     {
         if (empty(self::$spaltennamen)) {
            $idx2 = self::$_session['idx2'];
            self::$spaltennamen = ($idx2)
                ? self::SPALTEN_GRUPPE
                : self::SPALTEN_EINZEL;
         };
         return self::$spaltennamen;
     }


    protected static function getSession(): void
    {
        $keys_str = ['version','sort','dir','col','search', 'suche','thema','filter','rootdir'];
        $keys_int = ['siteid','start','proseite','userid','su','loggedin','idx2'];
        $keys_arr = ['jump'];

        foreach ($keys_str as $k) {
            self::$_session[$k] = (isset($_SESSION[$k])) ? $_SESSION[$k] : "";
        };
        foreach ($keys_int as $k) {
            self::$_session[$k] = (isset($_SESSION[$k])) ? (int)$_SESSION[$k] : 0;
        };
        foreach ($keys_arr as $k) {
            self::$_session[$k] = (isset($_SESSION[$k])) ? (array)$_SESSION[$k] : [];
        };
    }

    public static function setSession(): void
    {
        $key = ['idx2', 'sort', 'dir', 'col', 'search', 'suche',
                'thema', 'filter', 'start', 'proseite'];

        foreach ($key AS $k)
        {
            if (isset($_SESSION[$k]) && $_SESSION[$k] != self::$_session[$k]) {
                $_SESSION[$k] = self::$_session[$k];

            } elseif (!isset($_SESSION[$k]) && !empty(self::$_session[$k])) {
                $_SESSION[$k] = self::$_session[$k];
            };
        };
        $_SESSION['version'] = Data::getVersion();
    }


    function __construct() {}
    function __destruct() {}
    protected static function siteEntryCheck()
    {}


    /**
     * Summary of dataPreparation
     * @return void
     */
    protected static function dataPreparation(): void
    {
        // TODO ggf Nutzerdaten holen ???
        if (!Auth::isCheckedIn()) {
            Auth::checkUser();
        };

        // globale $_SESSION Werte in Klassenvariblen importieren
        self::getSession();


        // Reset-Button
        self::resetting();

        // Sortierung bestimmen
        self::setSort();

        // Thema/Filter bestimmen
        $filter = self::setTheme();

        // Suche bestimmen, filter wird geändert/ergänzt
        self::setSearch($filter);

        // mit neuem Filter Elemente zählen
        self::$maxID = Data::getMaxID($filter);


        // globale $_SESSION Werte mit den Klassenwerten aktualisieren
        self::setSession();
    }


    private static function resetting(): void
    {
        if (isset($_POST['reset'])) {
            unset($_POST['reset']);
            unset($_POST['col']);
            unset($_POST['dir']);
            $_POST['search'] = "";
            $_POST['start'] = 0;

            self::$_session['search'] = self::$_session['sort'] = "";
            #$_SESSION['search'] = $_SESSION['sort'] = "";
            self::setSession();
        };
    }


    private static function setSort(): void
    {
        $DBspalten = self::DBspalten;
        $sort = $col = $dir = "";

        if (isset($_POST['col'])) {
            // zuerste nach 'Thema' absteigend gruppieren
            $sort = "the.id DESC, ";

            foreach ($DBspalten AS $DBcol => $col_common) {   # substr($DBcol,4)
                if ($_POST['col'] == $col_common) {
                    $col = $DBcol;
                    break;    # foreach-Schleife verlassen
                } else {
                    $col = "";
                };
            };

            if (isset($_POST['dir'])) {
                $dir = (in_array($_POST['dir'], array('ASC', 'DESC')))
                    ? " ".$_POST['dir']
                    : "";
            };
            $sort .= $col.$dir;

            // wenn unkorrekte Sortierangabe, dann Leerstring
            if (strlen($sort) < 7) {  # 'the.id' = 6
                $sort = "";
            };
        };

        // wenn keine Sortierangabe, dann Standardwerte setzen
        if (strlen($sort) < 1) {
            $sort = " the.id DESC, sta.kat10, sta.datum";
            $col  = " sta.kat10";
            $dir  = " ASC";
        };
        self::$_session['sort'] = $sort;
        self::$_session['col']  = $col;
        self::$_session['dir']  = $dir;
    }


    private static function setTheme(): string
    {
        # TODO: $_POST['thema'] nicht direkt übernehmen!
        $theme = self::$_session['thema'];
        $filter = "";

        if (!empty($_POST['thema'])) {
            // wenn Dropdown-Auswahl

            if ($_POST['thema'] !== "- alle -") {
                // wenn Auswahl nicht 'alle'
                $filter = "the.thema = '{$_POST['thema']}'";
                if (empty($theme) || $_POST['thema'] != $theme) {
                    // wenn Auswahl anders als zuvor, speichern
                    $_POST['start'] = 0;
                    $theme = $_POST['thema'];
                };

            } elseif ($_POST['thema'] === "- alle -") {
                // 'alle'
                $filter = "the.id IS NOT NULL";
                $_POST['start'] = 0;
                $theme = $_POST['thema'];

            } else {
            };

        } elseif (!empty($theme) && ($theme != "- alle -")) {
            // kein Dropdown-Auswahl aber irgendwoher schon Thema gemerkt
            if ($theme != "- alle -") {
                $filter = "the.thema='{$theme}'";
            };

        } else {
            // keine Dropdown, keine Session-Wert oder 'alle'
            $filter = "the.id IS NOT NULL";
        };

        $filter .= " AND sta.deakt=0 AND dat.deakt=0";

        self::$_session['thema'] = $theme;
        return $filter;
    }


    private static function setSearch(&$filter): void
    {
        $search    = self::$_session['search'];
        $DBspalten = self::DBspalten;
        $suchwort  = "";

        if (!empty($_POST['search']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")
        {
            // Plausi-Check ...
            // erlaubte Zeichen definieren, SQL-kritische: [%;´`\'\"\-\{\}\[\]\*\/\\\\ (AND)(OR)]
            $error_msg = [];
            $regex_search = "/^[\w\s ×÷=<>:;,!@#€%&§`´~£¥₩₽° \\\\\^\$\.\|\?\*\+\-\(\)\[\]\{\}\/\"\']{0,100}$/u";
            $regex_search_no = "/[^\w\s ×÷=<>:;,!@#€%&§`´~£¥₩₽° \\\\\^\$\.\|\?\*\+\-\(\)\[\]\{\}\/\"\']/mu";
            $regex_search_no = "/[^\w\s ×÷=<>:,!@#€&§~£¥₩₽° \^\$\.\|\?\*\+\-\(\)\[\]]/mu";
            $regex_wrapper   = "/\r\r|\r\n|\n\r|\n\n|\n|\r|<br>/";      # Zeilenumbrüche

            // html-tags, Blackslash, Leerzeichen anfangs/ende entfernen
            // auth.func.php: cleanInput($data) = strip_tags(stripslashes(trim($data)));

            $input_search = Tools::cleanInput($_POST['search']);
            // Zeilenumbrüche entfernen
            $input_search = preg_replace($regex_wrapper, "", $input_search);

            if ($input_search !== "" &&
                preg_match_all($regex_search_no, $input_search, $match))
            {
                $error_msg []= 'unzulässige Zeichen in Suchanfrage: " '.
                                htmlentities(implode(" ", $match[0])).
                                ' "';
            };

            $input_search = htmlspecialchars($input_search);

            // Sucheingabe okay
            if (empty($error_msg)) {

                if (empty($search)) {
                    $search = "";
                };

                if ($input_search !== $search) {
                    $_POST['start'] = 0;
                };

                $search   = $input_search;
                $suchwort = $input_search;
            } else {
                $error_strg = implode("<br>", $error_msg);
            };
        }

        // keine Suchanfrage, aber vorangehende Suche gemerkt
        elseif (!empty($search)) {
            $suchwort = trim($search);
        };


        $suche = "";
        if (!empty($suchwort)) {
            $suchwort = explode(" ", $suchwort);
            $spalte   = array_keys($DBspalten);

            for ($i = 0; $i < sizeof($suchwort); $i++) {
                for ($ii = 0; $ii < sizeof($spalte); $ii++) {
                    $suche .= ($ii == 0) ? "(" : "";

                    // exakte Suche
                    #$suche .= "{$spalte[$ii]}='{$suchwort[$i]}'";

                    // sucht 'beginnend', mit Platzhalter %
                    #$suche .= "{$spalte[$ii]} LIKE '{$suchwort[$i]}%'";

                    // sucht 'enthaltend', mit Platzhalter %
                    $suche .= "{$spalte[$ii]} LIKE '%{$suchwort[$i]}%'";

                    $suche .= ($ii < (sizeof($spalte) - 1)) ? " OR " : ")";
                };
                $suche .= ($i < (sizeof($suchwort) - 1)) ? " AND " : "";
            };
            $filter .= " AND ".$suche;
        };
        self::$_session['filter'] = $filter;
        self::$_session['search'] = $search;
        self::$_session['suche']  = $suche;
    }

}




######################################
#foreach ($_POST AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_GET AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_REQUEST AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_COOKIE AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SERVER AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SESSION AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";

#if (!empty($_SESSION['su'])) var_dump($_SESSION['idx2'], $_SESSION['siteid']);

#print_r('ident: '.$_COOKIE['identifier'].'<br>');
#print_r('token: '.$_COOKIE['securitytoken'].'<br>');
#print_r('token: '.sha1($_COOKIE['securitytoken']).'<br>');
#print_r(pathinfo($_SESSION['main']));
