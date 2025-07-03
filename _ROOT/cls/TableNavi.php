<?php
namespace Dzg;

require_once __DIR__.'/Table.php';
use Dzg\Table;
use PDO, PDOException;


/***********************
 * Summary of TableNavi
 * Container für Navigations-Funktionen
 *
 * __public__
 * feldSeitenwahl()
 * feldAnzeigewahl()
 * feldThemenwahl()
 * feldSuchen()
 */
class TableNavi
{
    /***********************
     * Summary of datenbereich
     * setzt den Startwert und die Schrittweite der Tabellenausgabe
     *
     * IN:  $_SESSION['start'], $_SESSION['proseite'], $_POST['range'], $_POST['new_range']
     * OUT: $_SESSION['start'], $_SESSION['proseite'], $_POST['range'], $_POST['new_range'],
     *      $_POST['start']
     * @return int
     */
    private static function datenbereich(): int
    {
        $start = Table::$_session['start'];
        $proseite = Table::$_session['proseite'];

        // Start setzen
        if (empty($start)) $start = 0;

        // Datensätze pro Ausgabeseite / Standard: 10
        if (empty($proseite)) $proseite = 10;

        if (empty($_POST['range'])) {
            // wenn 'range' leer
            if (empty($_POST['new_range'])) {
                // und 'new_range' leer, dann auf Standardwert setzen
                $_POST['new_range'] = $proseite;
            } else {} // aber 'new_range' existiert
        } else {
            // wenn 'range' vorhanden
            if (empty($_POST['new_range'])) {
                // und 'new_range' leer,
                // dann Wert zur weiteren Verwendung setzen
                # todo: $_POST['range'] nicht direkt übernehmen!
                $_POST['new_range'] = $_POST['range'];
            } else {
                // und 'new_range' vorhanden
                if ($_POST['range'] != $_POST['new_range']) {
                    // wenn 'range' <> 'new_range'
                    // dann bei neuer Schrittweite Datenbank vom ersten Element an ausgeben
                    # todo: $_POST['new_range'] nicht direkt übernehmen!
                    $_POST['range'] = $_POST['new_range'];
                    $_POST['start'] = 0;
                } else {} // 'range' = 'new_range'
            }
        }
        // Schrittweite auf (geänderten) Dropdown-Wert setzen
        $proseite = (int)$_POST['new_range'];


        // Klassenwerte setzen
        Table::$_session['start'] = $start;
        Table::$_session['proseite'] = $proseite;

        // globale $_SESSION Werte mit den Klassenwerten aktualisieren
        Table::setSession();

        return $proseite;
    }


    /***********************
     * Summary of feldSeitenwahl
     * -- Pagination --
     * Übergabeparameter für seitenweise Ausgabe in Abhängigkeit anpassen
     * $_REQUEST globales Array mit Übergabeparameter von POST, GET, ...
     * ...hier: start (Beginn Datenausgabe), range (Schrittweite Datenausgabe),
     * new_range (geänderte Schrittweite per Dropdown)
     *
     * IN:  $maxID - max. Anzahl anzuzeigender Elemente
     * OUT: $startNr, $proseite - akt Seite beginnt bei ElementNr.
     * und endet x-Elemente später (DB-Abfrage: LIMIT start, proseite)
     * @return string
     */
    public static function feldSeitenwahl(): string
    {
        $maxID = Table::$maxID;
        $start = Table::$_session['start'];

        // TODO: Seitensprung ändern, logischer
        // start:0 --> site:1
        // start:10 --> site:2
        // maxSite = maxID / proseite
        // 0 < abs((int)site) <= maxSite
        // start = (site-1) * proseite
        // ... site = start / proseite + 1
        // bei DB Abfrage (LIMIT) auf start=0 dann wieder zurücksetzen


        //-------------------------------------------------
        // Anzahl der li/re anzuzeigenden Seitenzahlen (2= 3er Block, 3: 5er Block)
        $cut = 2;   # --> 5-9 Zellen (nur mit Symbolen: 5 (7) Zellen)
        $jump = 1;  # fastjump: cut + jump; jump=1 -> Anschlusssprung

        //-------------------------------------------------
        // Datensatz bestimmen, der ausgegeben werden soll:
        // $_SESSION['proseite'], $_POST['range'], $_POST['new_range'], $_POST['start']
        //
        $proseite = self::datenbereich();

        //-------------------------------------------------
        // max. Seitenzahl
        $seitenanzahl = (!($maxID % $proseite))
            // wenn alle Seiten gleichmäßig aufgeteilt sind (Div.Rest=0)
            ? intdiv($maxID, $proseite)

            // ansonsten +1 Seite für die Restelemente
            : intdiv($maxID, $proseite) + 1;

        //-------------------------------------------------
        // mit $startNr den Anfang der jeweiligen Listenausgabe festsetzen
        //
        // wenn noch keine Navi-Sprungmarke jemals gesetzt wurde
        if (empty($_POST['start']) && empty($start)) {
            $startNr = 0;

        // wenn keine Navi-Sprungmarke aktuell, aber davor schon mal gesetzt wurde
        } elseif (!isset($_POST['start']) && !empty($start)) {
            $startNr = $start;

        // wenn Navi-Seitensprungmarke gesetzt wurde
        } else {
            # todo: Plausi-Check! $_POST['start'] nicht direkt übernehmen!
            $startNr = abs((int)$_POST['start']);  # String in Zahl wandeln
        }

        // ggf. $startNr korrigieren (falls Parameter in der URL manipuliert wurde)
        // und auf Bereichsbeginn setzen und es gibt nicht mehr Seiten als anzuzeigende Elemente
        $startNr = ($startNr < $maxID)
            ? intdiv($startNr, $proseite) * $proseite
            : intdiv($maxID, $proseite) * $proseite;

        $start = $startNr;

        //-------------------------------------------------
        // aktuellen Seitenindex bestimmen
        $akt_seite = $startNr / $proseite;


        //-------------------------------------------------
        // 'zurück'
        $newStart_back = ($startNr - $proseite < 0) ? 0 : ($startNr-$proseite);
        $rewind = ($akt_seite - 2*$cut + $jump) * $proseite;

        //-------------------------------------------------
        // 'vor'
        $newStart_fwd = $startNr + $proseite;
        $fastforward  = ($akt_seite + 2*$cut - $jump) * $proseite;
        $last_site = $proseite * ($seitenanzahl - 1);        # '-1' da Zählg. mit '0' beginnt


        ///////////////////////////////////////////////////
        //  -- Ausgabe Seiten-Navigation --
        //
        $output  = "
            <div class='navi_seitenwahl'>
            <table class='navi'><tr>";

        //-------------------------------------------------
        //  Anzeige "zurück"
        //
        // <: &lt; <<: &ll; <<<: &Ll;
        // &LessLess; &laquo; &LeftTriangle; &langle; &lang; &Lang; &blacktriangleleft;
        //
        /*<a class='site' href={$_SERVER['PHP_SELF']}?start={$newStart_back}
                title='eine Seite rückwärts'>&lt;</a>*/
        $output .= ($startNr !== 0)
            // akt.Seite nicht erste Seite -> mit Symbol und Link beginnen
            ? "<td class='back' title='eine Seite rückwärts'>
                <a class='site'>
                <form method='POST' action='' class='site'>
                <input type='hidden' name='start' value='{$newStart_back}' />
                <button class='lnk' style='color:initial'>&lt;</button>
                </form></a></td>
                <td>&nbsp;</td>

                <td class='site' title='erste Seite'>
                <a class='site'>
                <form method='POST' action='' class='site'>
                <input type='hidden' name='start' value='0' />
                <button class='lnk' style='color:initial'>1</button>
                </form></a></td>"

            // akt.Seite = erste Seite -> ohne Symbol/Link beginnen
            : "<td class='active rund-links'><a class='active' title='aktuelle Seite 1'>1</a></td>";

        //-------------------------------------------------
        //  Anzeige "Seitennummern"
        //
        for ($i = 1; $i < $seitenanzahl-1; $i++) {

            $str_active = "
            <td class='active' title='aktuelle Seite ".($i+1)."'>
            <a class='active'>".($i+1)."
            </a></td>";

            $str_step = "
            <td class='site' title='Seite ".($i+1)."'>
            <a class='site'>
            <form method='POST' action='' class='site'>
            <input type='hidden' name='start' value='".($i*$proseite)."' />
            <button class='lnk' style='color:initial'>".($i+1)."</button>
            </form></a></td>";

            $str_jump_backward = "
            <td class='site' title='td1_Seite {$i}'>
            <a class='site' style='padding:1px 8px;'>
            <form method='POST' action='' class='site'>
            <input type='hidden' name='start' value='{$rewind}' />
            <button class='lnk' style='color:initial'>&hellip;</button>
            </form></a></td>";

            $str_jump_forward = "
            <td class='site' title='td2_Seite ".($i+2)."'>
            <a class='site' style='padding:1px 8px;'>
            <form method='POST' action='' class='site'>
            <input type='hidden' name='start' value='{$fastforward}' />
            <button class='lnk' style='color:initial'>&hellip;</button>
            </form></a></td>";


            // unterhalb Cut: nix anzeigen
            if ($i < ($akt_seite - $cut)) {
                if ($rewind <= 0) {
                    $output .= $str_step;
                }

            // unterer Cut: "..." anzeigen
            } elseif ($i === ($akt_seite - $cut)) {
                if ($rewind > 0) {
                    $output .= $str_jump_backward;
                } else {
                    $output .= $str_step;
                }

            // innerhalb Cut-Bereich: Seitennummer anzeigen
            } elseif ($i > ($akt_seite - $cut) && $i < ($akt_seite + $cut)) {
                if ($i !== $akt_seite) {
                    // wenn nicht aktuelle Seite, dann einen Link erstellen
                    $output .= $str_step;
                } else {
                    // ansonsten (aktuelle Seite) ohne Link
                    $output .= $str_active;
                }

            // oberer Cut: "..." anzeigen
            } elseif ($i === ($akt_seite + $cut)) {
                if ($fastforward < $last_site) {
                    $output .= $str_jump_forward;
                } else {
                    $output .= $str_step;
                }

            // oberhalb Cut: nix anzeigen
            } elseif ($i > ($akt_seite + $cut)) {
                if ($fastforward >= $last_site) {
                    $output .= $str_step;
                }
            }
        }

        //-------------------------------------------------
        //  Anzeige "vor"
        //
        // >: &gt; >>: &gg; >>>: &ggg;
        // &GreaterGreater; &raquo; &RightTriangle; &rangle; &rang; &Rang; &blacktriangleright;
        //
        if ($startNr !== $last_site) {
            // akt.Seite nicht letzte Seite -> letzte Seitenzahl mit Link + Symbol
            if ($seitenanzahl > 1) {
                $output .= "
                    <td class='site' title='letzte Seite {$seitenanzahl}'>
                    <a class='site'>
                    <form method='POST' action='' class='site'>
                    <input type='hidden' name='start' value='{$last_site}' />
                    <button class='lnk' style='color:initial'>{$seitenanzahl}</button>
                    </form>
                    </a></td>";
            }
            $output .= "
                <td>&nbsp;</td>
                <td class='for' title='eine Seite vorwärts'>
                <a class='site'>
                <form method='POST' action='' class='site'>
                <input type='hidden' name='start' value='{$newStart_fwd}' />
                <button class='lnk' style='color:initial'>&gt;</button>
                </form>
                </a></td>";

/*
                <td class='for'>
                <a class='site' href={$_SERVER['PHP_SELF']}?start={$newStart_fwd}
                title='eine Seite vorwärts'>&gt;</a></td>";
*/
        } elseif ($seitenanzahl > 1) {
            // akt.Seite = letzte Seite -> ohne Link/Symbol
            $output .= "<td class='active'><a class='active'
                        title='aktuelle Seite {$seitenanzahl}'>{$seitenanzahl}</a></td>";
        }

        $output .= "</tr></table></div>";   # ende < /SEITENAUSWAHL >


        // Klassenwerte setzen
        Table::$_session['start'] = $start;

        // globale $_SESSION Werte mit den Klassenwerten aktualisieren
        Table::setSession();

        return $output;
    }


    /***********************
     * Summary of feldAnzeigewahl
     * "Dropdown-proSeite"
     *
     * IN:  $_SESSION['proseite'], $maxID
     * OUT: $_SESSION['new_range']
     * @return string
     */
    public static function feldAnzeigewahl(): string
    {
        $maxID = Table::$maxID;

        // Datenanzeigebereich bestimmen
        $proseite = self::datenbereich();

        // Dropdown Auswahl für Listengröße
        $dd_options = [
                '5',
                '10',
                '25',
                '50',
                '100',
                '250',
                '500',
                '900'
            ];

        // -- Ausgabe --
        $output  = "<div class='navi_seitenanzeige'>";
        $output .= "
            <form class='naviform seitenanzeige' style='' name='new_range' method='POST'
            action=''>
            <label>
            <select class='form-dd' name='new_range' onchange='this.form.submit()'
            title='Anzahl pro Seite festlegen'>";
            #padding-right:0.5em;
        foreach ($dd_options as $opt_val) {
            ($opt_val == $proseite)
                ? $value = "selected value=".$opt_val
                : $value = "value=".$opt_val;
            $output .= "<option {$value}>{$opt_val}&nbsp;</option>";
        }
        $output .= "
            </select></label>
            <label style=''> / ".number_format($maxID, 0, ',', '.')."</label></form>
            </div>";   # ende < /SEITENAUSWAHL >
            #background-color:transparent;padding:0px 4px 1px 0px;

        return $output;
    }


    /***********************
     * Summary of feldThemenwahl
     * "Dropdown Thema"
     *
     * IN: $pdo
     * @return string
     */
    public static function feldThemenwahl()
    {
        $pdo = Table::$pdo;
        $thema = Table::$_session['thema'];

        $theme_db = ['- alle -'];
        $stmt = "SELECT sub2 FROM dzg_dirsub2 ORDER BY sub2 DESC";
        try {
            $qry = $pdo->query($stmt);
            $theme_qry = $qry->fetchAll(PDO::FETCH_NUM);
        } catch(PDOException $e) {die($e->getMessage().': TableNavi.feldThemenwahl()');}

        foreach ($theme_qry AS $entry) {
            foreach ($entry AS $theme) {
                array_push($theme_db, $theme);
            }
        }
        # &#10005; &#10006; &#10007; &#10008; &#10799 &#215;(&times;)
        $button1 = "<span class='btn_reset deaktiv2' style='font-size:80%'
                    title=''>&#10005;</span>";
        if (!empty($thema) && $thema !== '- alle -') {
            $button1 = "<button class='btn_reset' type='submit' name='thema'
                        value='- alle -' title='Auswahl löschen'>&#10006;</button>";
        }

        // -- Ausgabe --
        $output  = "
            <div class='navi_themenwahl'>
            <form class='naviform theme' method='post' title='Filter wählen'><label>
            <select class='filter-option' name='thema' onchange='this.form.submit()'>";
            #action=''

        foreach ($theme_db as $opt_val) {
            $theme = '- alle -';
            if (!empty($thema)) {
                $theme = $thema;
            }

            if ($opt_val === '- alle -') {
                if ($opt_val === $theme) {
                    $output .= "<option>Thema auswählen &hellip;&ensp;</option>";
                }
                $output .= "<hr>";
                #echo"<optgroup label=''>";
            }
            else {
                $value = ($opt_val === $theme) ? "'{$opt_val}' selected" : "'{$opt_val}'";
                $output .= "<option value={$value}>{$opt_val}</option>";
            }
        }
        #echo"</optgroup>";
        $output .= "
            </select>{$button1}</label></form>
            </div>";   # ende < /FILETERAUSWAHL >

        return $output;
    }


    /***********************
     * Summary of feldSuchen
     * "Suche"
     * @return string
     */
    public static function feldSuchen()
    {
        $search = Table::$_session['search'];

        $value = '';
        $placeholder = "suchen &hellip;";
        $button1_img = "<img src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gU3ZnIFZlY3RvciBJY29ucyA6IGh0dHA6Ly93d3cub25saW5ld2ViZm9udHMuY29tL2ljb24gLS0+DQo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMjU2IDI1NiIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjU2IDI1NiIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8bWV0YWRhdGE+IFN2ZyBWZWN0b3IgSWNvbnMgOiBodHRwOi8vd3d3Lm9ubGluZXdlYmZvbnRzLmNvbS9pY29uIDwvbWV0YWRhdGE+DQo8Zz48Zz48cGF0aCBmaWxsPSIjMDAwMDAwIiBkPSJNMTU5LjIsMTY1LjNjLTE1LjgsMTQuOC0zNy4xLDIzLjgtNjAuNSwyMy44Yy00OSwwLTg4LjctMzkuNy04OC43LTg4LjdzMzkuNy04OC43LDg4LjctODguN2M0OSwwLDg4LjcsMzkuNyw4OC43LDg4LjdjMCwyMS42LTcuOCw0MS41LTIwLjYsNTYuOWM3LjMsMC41LDE2LDQuNSwyMC44LDkuMkwyNDIsMjIxYzUuMiw1LjIsNS4zLDE0LDAsMTkuM2MtNS40LDUuNC0xNCw1LjMtMTkuMywwbC01NC41LTU0LjVDMTYzLjUsMTgxLjEsMTU5LjcsMTcyLjQsMTU5LjIsMTY1LjN6IE05OC43LDE2MS44YzMzLjksMCw2MS40LTI3LjUsNjEuNC02MS40YzAtMzMuOS0yNy41LTYxLjQtNjEuNC02MS40cy02MS40LDI3LjUtNjEuNCw2MS40QzM3LjMsMTM0LjMsNjQuOCwxNjEuOCw5OC43LDE2MS44eiIvPjwvZz48L2c+DQo8L3N2Zz4=' width='12' height='12'> ";
        $button1 = "<button class='btn_search' title='Suche starten'>{$button1_img}</button>";
        $button2 = "<span class='btn_reset deaktiv2' style='font-size:80%'
                    title=''>&#10005;</span>";

        if ($search !== "") {
            $placeholder = '';
            $value = $search;
            $button2 = "<button class='btn_reset' type='submit' name='reset'
                        title='Suche löschen'>&#10006;</button>";
        }

        // -- Ausgabe --
        $output  = "
            <div class='navi_suche'>
            <form class='naviform suche' method='POST'>
            <input class='suchfeld2' type='search' name='search' value='{$value}' results='0'
            title='Suchwörter eingeben' placeholder='{$placeholder}'/>
            {$button1}{$button2}</form>
            </div>";   # ende < /FILTERAUSWAHL >

        return $output;
    }
}