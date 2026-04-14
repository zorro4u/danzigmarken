<?php
namespace Dzg\Sites;
use Dzg\SiteData\TableData as Data;
use Dzg\SitePrep\{TablePrep, TableBody, TableNavi};
use Dzg\SitePrep\{Header, Footer};
use Dzg\Tools\Auth;

require_once __DIR__.'/../siteprep/loader_table.php';
require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../tools/auth.php';


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
class Table extends TablePrep
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::view();
        Footer::show();
    }


    /***********************
     * Summary of view
     *
     * @return void
     */
    private static function view()
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

            $stamps_db = Data::getMainData();

        } else {
            // Nutzer nicht angemeldet --> ohne Navi-Möglichkeit
            // und nur zufällige Elemente anzeigen
            if (empty($loggedin)) {
                $rand_num  = 10;
                self::$_session['proseite'] = $maxID;
                self::$_session['start'] = 0;              // random_int(0, $maxID - $proseite);
                self::setSession();

                $all_data  = Data::getMainData();
                $rand_idx  = array_rand($all_data, $rand_num);
                $rand_data = [];
                foreach ($rand_idx as $idx) {
                    $rand_data []= $all_data[$idx];
                };
                $stamps_db = $rand_data;
                self::$_session['proseite'] = $rand_num;

                // wird nicht weiter benötigt
                unset($all_data, $rand_idx, $rand_data);
            };

            $navi_tab = '';
            $output .= "<br>";
        };

        // Klassenwert setzen zur weiteren Verwendung
        Table::$stamps_db = $stamps_db;


        //-------------------------------------------------
        // Tabelle ausgeben
        //

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
        };

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
            $txt .= $idx2 ? " Marken" : " Einträgen";

            $output .= "
                <div class='fuss'>
                <div class='linksX'>
                    <p><br>
                    Hier nur eine zufällige ".self::$_session['proseite']."er Auswahl aus ".$txt.".<br>
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
        };

        $output .= "</div>";   # ende < /CENTER >  Zentralbereich

        // < BOTTOM >
        //
        $style = "";
        if(count($stamps_db) < 10) {
            $style = "border-radius: 6px 6px 6px 6px";
        };

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
