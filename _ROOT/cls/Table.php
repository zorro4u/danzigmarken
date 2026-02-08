<?php
namespace Dzg;
require_once __DIR__.'/TableData.php';   // Datenbank-Abfrage
require_once __DIR__.'/TableBody.php';   // Tabellen-Erzeugung
require_once __DIR__.'/TableNavi.php';   // Tabellen-Navigation
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';


/***********************
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
class Table
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::siteOutput();
        Footer::show();
    }


    /***********************
     * Klassenvariablen / Eigenschaften
     */
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
        'ort.name'  => 'name',
        'suf.suffix' => 'suffix',
        'sta.id' => 'gid',
        'dat.id' => 'fid',
        'print'  => 'print',
    ];

    private const SPALTEN_EINZEL = [
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
        'kat17'    => 'Notiz.1',
        'kat22'    => 'Notiz.2',
        #'kat23'    => 'Michel_NEU',
        #'kat24'    => '\'kat24\'',
        'fid'      => 'ID',
        #'gid'      => 'G.ID',
        'print'     => 'druck'
    ];

    private const SPALTEN_GRUPPE = [
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


    /***********************
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
         }
         return self::$spaltennamen;
     }


    public static function getSession()
    {
        Header::setSiteID(basename($_SERVER['PHP_SELF']));

        $keys_str = ['version','sort','dir','col','search', 'suche','thema','filter','rootdir'];
        $keys_int = ['siteid','start','proseite','userid','su','loggedin','idx2'];
        $keys_arr = ['jump'];

        foreach ($keys_str as $k) {
            self::$_session[$k] = (isset($_SESSION[$k])) ? $_SESSION[$k] : "";
        }
        foreach ($keys_int as $k) {
            self::$_session[$k] = (isset($_SESSION[$k])) ? (int)$_SESSION[$k] : 0;
        }
        foreach ($keys_arr as $k) {
            self::$_session[$k] = (isset($_SESSION[$k])) ? (array)$_SESSION[$k] : [];
        }
    }

    public static function setSession()
    {
        $key = ['idx2', 'sort', 'dir', 'col', 'search', 'suche',
                'thema', 'filter', 'start', 'proseite'];

        foreach ($key AS $k)
        {
            if (isset($_SESSION[$k]) && $_SESSION[$k] != self::$_session[$k]) {
                $_SESSION[$k] = self::$_session[$k];

            } elseif (!isset($_SESSION[$k]) && !empty(self::$_session[$k])) {
                $_SESSION[$k] = self::$_session[$k];
            }
        }
        $_SESSION['version'] = Database::version();
    }


    function __construct() {}
    function __destruct() {}


    /***********************
     * Summary of siteEntryCheck
     * @return void
     */
    private static function siteEntryCheck()
    {

    }


    /***********************
     * Summary of dataPreparation
     * @return void
     */
    private static function dataPreparation()
    {
        // TODO ggf Nutzerdaten holen ???
        if (!Auth::isCheckedIn()) {
            Auth::checkUser();
        }

        // globale $_SESSION Werte in Klassenvariblen importieren
        self::getSession();

        // Klassenvariblen laden
        $session = self::$_session;
        $DBspalten = self::DBspalten;


        ///////////////////////////////////////////////////
        // Reset-Button
        //
        if (isset($_POST['reset'])) {
            unset($_POST['reset']);
            unset($_POST['col']);
            unset($_POST['dir']);
            $_POST['search'] = "";
            $_POST['start'] = 0;

            self::$_session['search'] = self::$_session['sort'] = "";
            #$_SESSION['search'] = $_SESSION['sort'] = "";
            self::setSession();
        }


        ///////////////////////////////////////////////////
        // Sortierung
        //
        $sort = "";
        if (isset($_POST['col'])) {
            // zuerste nach 'Thema' absteigend gruppieren
            $sort = "the.id DESC, ";

            foreach ($DBspalten AS $DBcol => $col_common) {   # substr($DBcol,4)
                if ($_POST['col'] == $col_common) {
                    $col = $DBcol;
                    break;    # foreach-Schleife verlassen
                } else {
                    $col = "";
                }
            }

            if (isset($_POST['dir'])) {
                $dir = (in_array($_POST['dir'], array('ASC', 'DESC')))
                    ? " ".$_POST['dir']
                    : "";
            }
            $sort .= $col.$dir;

            // wenn unkorrekte Sortierangabe, dann Leerstring
            if (strlen($sort) < 7) {  # 'the.id' = 6
                $sort = "";
            }
        }

        // wenn keine Sortierangabe, dann Standardwerte setzen
        if (strlen($sort) < 1) {
            $sort = " the.id DESC, sta.kat10, sta.datum";
            $col = " sta.kat10";
            $dir = " ASC";
        }


        ///////////////////////////////////////////////////
        // Thema-Filter bestimmen
        //
        # TODO: $_POST['thema'] nicht direkt übernehmen!
        $thema = self::$_session['thema'];
        if (!empty($_POST['thema'])) {
            // wenn Dropdown-Auswahl

            if ($_POST['thema'] !== "- alle -") {
                // wenn Auswahl nicht 'alle'
                $filter = "the.thema = '{$_POST['thema']}'";
                if (empty($thema) || $_POST['thema'] != $thema) {
                    // wenn Auswahl anders als zuvor, speichern
                    $_POST['start'] = 0;
                    $thema = $_POST['thema'];
                }

            } elseif ($_POST['thema'] === "- alle -") {
                // 'alle'
                $filter = "the.id IS NOT NULL";
                $_POST['start'] = 0;
                $thema = $_POST['thema'];

            } else {
            }

        } elseif (!empty($thema) && ($thema != "- alle -")) {
            // kein Dropdown-Auswahl aber irgendwoher schon Thema gemerkt
            if ($thema != "- alle -") {
                $filter = "the.thema='{$thema}'";
            }

        } else {
            // keine Dropdown, keine Session-Wert oder 'alle'
            $filter = "the.id IS NOT NULL";
        }

        $filter .= " AND sta.deakt=0 AND dat.deakt=0 AND ort.deakt=0";


        ///////////////////////////////////////////////////
        // Suche bestimmen
        //
        $search = self::$_session['search'];

        if (!empty($_POST['search']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST") {

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
            }

            $input_search = htmlspecialchars($input_search);

            // Sucheingabe okay
            if (empty($error_msg)) {

                if (empty($search)) {
                    $search = "";
                }

                if ($input_search !== $search) {
                    $_POST['start'] = 0;
                }

                $search = $input_search;
                $suchwort = $input_search;
            } else {
                $error_strg = implode("<br>", $error_msg);
            }


        // keine Suchanfrage, aber vorangehende Suche gemerkt
        } elseif (!empty($search)) {
            $suchwort = trim($search);
        }

        $suche = "";
        if (!empty($suchwort)) {
            $suchwort = explode(" ", $suchwort);
            $spalte = array_keys($DBspalten);

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
                }
                $suche .= ($i < (sizeof($suchwort) - 1)) ? " AND " : "";
            }
            $filter .= " AND ".$suche;
        }


        // Klassenwerte setzen
        self::$maxID = TableData::getMaxID($filter);
        self::$_session['sort'] = $sort;
        self::$_session['dir'] = $dir;
        self::$_session['col'] = $col;
        self::$_session['thema'] = $thema;
        self::$_session['search'] = $search;
        self::$_session['suche'] = $suche;
        self::$_session['filter'] = $filter;

        // globale $_SESSION Werte mit den Klassenwerten aktualisieren
        self::setSession();
    }


    /***********************
     * Summary of siteOutput
     *
     * @return void
     */
    private static function siteOutput()
    {
        // Klassenvariblen laden
        $maxID = self::$maxID;
        $idx2 = self::$_session['idx2'];
        $rootdir = self::$_session['rootdir'];
        $loggedin = self::$_session['loggedin'];
        $userid = self::$_session['userid'];
        $su = self::$_session['su'];


        // < CONTENT >
        $output = "<div class='container' id='top'>";
        #$output = "<div class='content'>";

        // < FIRST >
        $output .= "<div class='firstX'></div>";

        // < TOP > Überschrift
        $output .= "<div class='topX' style='grid-template-columns: 1fr 1fr 1fr;'>";
        $output .= "<div class='links'></div>";
        $output .= "<div class='mitte'></div>";
        $output .= "<div class='rechts'></div>";
        $output .= "</div>";    // ende <top>
        echo $output;
        $output = '';


        ///////////////\\\\\\\\\\\\\\\
        // < CENTER > Zentralbereich
        //
        $output .= "<div class='centerX'>";

        // < Tabellen-NAVI >  Seiten-, Filter-Wahl
        //
        #if (1===1) {
        if (Auth::isCheckedIn()) {
            // Nutzer angemeldet, Ausgabe Tabellen-Navi

            $navi_tab = "<div class='navi'>".
                TableNavi::feldAnzeigewahl().   # Anzahl der Listeneinträge pro Seite
                TableNavi::feldThemenwahl().    # Filter Thema
                TableNavi::feldSeitenwahl().    # Seitenwahl / Pagination
                TableNavi::feldSuchen().        # Suche
                "</div>";

        } else {
            // Nutzer nicht angemeldet --> ohne Navi-Möglichkeit

            // 10 zufällige Elemente anzeigen
            if (empty($loggedin)) {
                $proseite = 10;
                $start = random_int(0, $maxID - $proseite);

                // Klassenwerte setzen
                self::$_session['start'] = $start;
                self::$_session['proseite'] = $proseite;

                // globale $_SESSION Werte mit den Klassenwerten aktualisieren
                self::setSession();
            }

            $navi_tab = '';
            $output .= "<br>";
        }


        //-------------------------------------------------
        // -- zentrale Datenabfrage, die ausgegeben wird --
        //   (nach Filter- und Seitenwahl!)
        //
        $stamps_db = TableData::getData();


        // < TABELLE >
        //
        $output .= "<div class='main'><table class=''>";
        $output .= "<tr><td>$navi_tab</td></tr>";

        $output .= "<tr><td><table class='data'>";
        $output .= TableBody::htmlTabkopf();
        $output .= TableBody::htmlTabelle();
        $output .= "</table></td></tr>";    # ende </data>

        $output .= "</table></div>";        # ende </main>


        // < FUSS >  unterer Block
        //
        // - Download / Drucken -
        $btn_down_link = "";
        if ((Auth::isCheckedIn() && $userid===300) || !empty($su)) {
            // fa-download, fa-arrow-circle-down, fa-arrow-circle-o-down, fa-arrow-down
            $btn_down_link = "<a style='color:gray; background-color: transparent;' ".
                "href='../tools/printview' title='Druckanzeige'>".
                "<i class='fas fa-print'>&ensp;</i>Druckanzeige</a>";
        }

        if(count($stamps_db) > 5 && Auth::isCheckedIn()) {
            $output .= "
                <div class='fuss'>
                <div class='links noprint'>".TableNavi::feldSeitenwahl()."</div>
                <div class='mitte noprint'>
                    <form action='#top' style='display:inline'>
                    <button class='btn Xbtn-primary' type='submit' title='zum Seitenanfang'>
                    Seitenanfang</button></form>
                </div>
                <div class='rechts noprint'>{$btn_down_link}</div>
                </div>";    # ende < /FUSS >

        } elseif (!Auth::isCheckedIn()) {
            // nicht angemeldet --> ohne Navi-Möglichkeit
            $navi_tab = '';
            $txt = number_format($maxID, 0, ',', '.');
            $txt .= $idx2 ? " Marken" : " Einzelbildern";

            # Hier nur eine <b>zufällige</b> ".$proseite."er Auswahl aus ".$txt.". <br>
            $output .= "
                <div class='fuss'>
                <div class='linksX'>
                    <p><br>
                    Hier nur eine <b>zufällige</b> ".$proseite."er Auswahl. <br>
                    Voller Zugang mit Filter- und Suchfunktion nach dem  &gg;&nbsp;
                    <a href='/auth/login' title='Anmelden'><b>Anmelden</b></a>&nbsp;&ll;<br>&nbsp;</p>
                </div>
                <div class='mitte'></div>
                <div class='rechts noprint'></div>
                </div>";    # ende < /FUSS >

        } else {
            $output .= "
                <div class='fuss'>
                <div class='links'></div>
                <div class='mitte'></div>
                <div class='rechts noprint'>{$btn_down_link}</div>
                </div>";    # ende < /FUSS >
        }

        $output .= "</div>";   # ende < /CENTER >  Zentralbereich

        // < BOTTOM >
        //
        $style = "";
        if(count($stamps_db) < 10) {
            $style = "border-radius: 6px 6px 6px 6px";
        }

        $output .= "<div class='bottomX' style='{$style}'>";
        $output .= "<div class='links'></div>";
        $output .= "<div class='mitte'></div>";
        $output .= "<div class='rechts'></div>";
        $output .= "</div>";    # ende < /BOTTOM >

        // < LAST >
        $output .= "<div class='lastX'>";
        $output .= "<div class='links kleingrau' style='font-style: italic;'></div>";
        $output .= "<div class='mitte kleingrau'></div>";
        $output .= "<div class='rechts kleingrau'></div>";
        $output .= "</div>";   # ende < /LAST >
        $output .= "</div>";   # ende < /CONTENT >


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
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
