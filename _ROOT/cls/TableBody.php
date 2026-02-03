<?php
namespace Dzg;
require_once __DIR__.'/Table.php';
require_once __DIR__.'/TableData.php';


/***********************
 * Summary of TableBody
 * Tabellenkopf & Tabellenkörper
 *
 * __public__
 * htmlTabkopf()
 * htmlTabelle()
 */
class TableBody
{
    /***********************
     * Summary of htmlTabkopf
     * Tabellenkopf erstellen
     *
     * IN: $_SESSION['dir'], $_SESSION['col'], $DBspalten, $spaltennamen;
     */
    public static function htmlTabkopf(): string
    {
        $DBspalten = Table::DBspalten;
        $spaltennamen = Table::spaltennamen();
        $dir = Table::$_session['dir'];
        $col = Table::$_session['col'];
        $is_checked_in = Auth::isCheckedIn();
        #$is_checked_in = $_SESSION['su'];

        $output = "<thead><tr class='head-row'><th>&nbsp;</th>";

        $sort_col = array_values($DBspalten);
        array_splice($sort_col,21,4);   # delete thema..suffix
        #array_splice($sort_col,8,1);    # delete kat17 (Notiz.1)
        // letztes Element (print) entfernen
        array_pop($sort_col);

        if (!$is_checked_in) {
            unset($spaltennamen['print'], $spaltennamen['fid'], $spaltennamen['gid']);
        }

        # Gruppen.ID nur bei admin ausgeben
        if (empty($_SESSION['su'])) {
            unset($spaltennamen['gid']);
            #unset($spaltennamen['print']);
        }

        // Tabelle-Spaltenreihenfolge entspr. $spaltennamen
        foreach ($spaltennamen as $spalte_db=>$spalte_web) {

            // Thumbnail
            if ($spalte_web == 'Pic0') {
                $output .= "<th class='head-cell thumb'>thumbs</th>";

            // sortierbare Spalten
            } elseif (in_array($spalte_db, $sort_col)) {
                if (empty($dir)) {$dir = 'ASC';}
                if (empty($col)) {$col = 'sta.kat10';}

                // ' sta.datum' -> 'datum'
                switch (trim($col)) {
                    case 'sta.id':
                        $col0 = 'gid';
                        break;
                    case 'dat.id':
                        $col0 = 'fid';
                        break;
                    case 'print':
                        $col0 = 'print';
                        break;
                    default:
                        $col0 = substr(trim($col), 4);
                }

                // ' ASC' -> 'ASC', Leerzeichen löschen
                $dir0 = trim($dir);

                // IDs + Druck grau formatieren
                $idx = "";
                if (($spalte_db == 'gid') || ($spalte_db == 'fid') || ($spalte_db == 'print')) {
                    $idx = "idx' style='color:hsl(0,0%,55%); font-style:italic";
                }

                // wenn angemeldet, dann Spaltenkopf sortierbar
                #if (Auth::isCheckedIn()) {
                if (1) {

                    // aufsteigend -> absteigend
                    if (($col0 == $spalte_db) && ($dir0 == 'ASC')) {
                        $link_down = "?col={$spalte_db}&dir=DESC";
                        if (empty($_SESSION['su'])) {
                        $output .= "<th class='head-cell sort'>
                            <a class='sort upArrow {$idx}' href='{$link_down}'
                            title='&#9660; absteigend'>{$spalte_web}</a></th>";

                        } else {
                        $output .= "<th class='head-cell sort'>
                            <a class='sort {$idx}'
                            title='&#9660; absteigend'>

<form method='POST' action='{$_SERVER['PHP_SELF']}' class='upArrow'>
<input type='hidden' name='col' value='{$spalte_db}' />
<input type='hidden' name='dir' value='DESC' />
<button class='lnk'>{$spalte_web}</button>
</form>
                            </a></th>";
                        }

                    // absteigend -> aufsteigend
                    } elseif (($col0 == $spalte_db) && ($dir0 == 'DESC')) {
                        $link_up = "?col={$spalte_db}&dir=ASC";
                        if (empty($_SESSION['su'])) {
                        $output .= "<th class='head-cell sort'>
                            <a class='sort dnArrow {$idx}' href='{$link_up}'
                            title='&#9650; aufsteigend'>{$spalte_web}</a></th>";
                        } else {
                        $output .= "<th class='head-cell sort'>
                            <a class='sort {$idx}'
                            title='&#9650; aufsteigend'>

<form method='POST' action='{$_SERVER['PHP_SELF']}' class='dnArrow'>
<input type='hidden' name='col' value='{$spalte_db}' />
<input type='hidden' name='dir' value='ASC' />
<button class='lnk'>{$spalte_web}</button>
</form>
                            </a></th>";
                        }

                    // nicht aktiviert, initial aufsteigend, ohne Symbol
                    } else {
                        $link_up = "?col={$spalte_db}&dir=ASC";
                        if (empty($_SESSION['su'])) {
                        $output .= "<th class='head-cell sort' style='text-align:center;'>
                            <a class='sort dbArrowX {$idx}' href='{$link_up}'
                            title='&#9650; aufsteigend'>{$spalte_web}</a></th>";
                        } else {

                        $output .= "<th class='head-cell sort'>
                            <a class='sort dbArrowX {$idx}'
                            title='&#9650; aufsteigend'>

<form method='POST' action='{$_SERVER['PHP_SELF']}' class=''>
<input type='hidden' name='col' value='{$spalte_db}' />
<input type='hidden' name='dir' value='ASC' />
<button class='lnk'>{$spalte_web}</button>
</form>
                            </a></th>";
                        }
                    }

                } else {
                    $nolink = "style='cursor:unset; th.sort:hover:background-color:unset;'";
                    $output .= "<th class='head-cell sortx {$idx}'>{$spalte_web}</th>";
                }

            // restliche Spalten
            } else {
                $idx = "";
                // grau formatieren
                if (($spalte_db == 'print')) {
                    $idx = "idx noprint' title='in Druckauswahl aufnehmen' style='color:hsl(0,0%,55%); font-style:italic";
                }
                $output .= "<th class='head-cell sortx {$idx}'>{$spalte_web}</th>";
            }
        }
        $output .= "<th>&nbsp;</th></tr></thead>";

        return $output;
    }


    /***********************
     * Summary of htmlTabelle
     * Tabellen erstellen, Werte ausgeben
     *
     * IN: $stamps_db, $spaltennamen, $mainpage2;
     */
    public static function htmlTabelle(): string
    {
        $stamps_db = Table::$stamps_db;
        $spaltennamen = Table::spaltennamen();
        $idx2 = Table::$_session['idx2'];
        $is_checked_in = Auth::isCheckedIn();

        if (!$is_checked_in) {
            unset($spaltennamen['print'], $spaltennamen['fid'], $spaltennamen['gid']);
        }

        # Gruppen.ID nur bei admin ausgeben
        if (empty($_SESSION['su'])) {
            unset($spaltennamen['gid']);
            #unset($spaltennamen['print']);
        }

        $output = "<tbody>";

        foreach ($stamps_db as $stamp) {
            $output .= "<tr class='row-body' style='padding:0' title='details'><td></td>";
    /*
        $output .= '<tr class="row-body" style="padding:0" title="details"
        onmouseover="this.className=\'spezial\';"
        onmouseout="this.className=\'normal\';"
        onclick="window.location.href=\'/details.php?id={$stamp[$id]}\';">
        <td></td>';
    */
    /*  onmouseover="this.className='spezial';"
        onmouseout="this.className='normal';"
        onclick="window.location.href='http://www.example.com/';"
    */


            // Einzelbilder-Liste
            //
            if (!$idx2) {
                $id = 'fid';
                $title = "details #".$stamp[$id];

                foreach ($spaltennamen as $spalte_db => $spalte_web) {
                    // Thumbnail
                    if ($spalte_web == 'Pic0') {
                        $output .= "<td class='data-cell' style='text-align: center;'
                            title='{$title}' id='{$stamp[$id]}'>
                            <a class='akt-link2' href=./details.php?id={$stamp[$id]}>
                            <img src='/".htmlspecialchars($stamp['thumb']).
                            "' width='50' height='50' alt='#{$stamp[$id]}'></a></td>";

                        # <div><img src='data:image/jpg;charset=utf8;base64,".
                        # base64_encode($stamp['thumb-blob']).
                        # "' width='50' height='50' alt='#{$stamp[$id]}'></div></a></td>";

                    // ID's
                    } elseif ($spalte_db == 'fid' || $spalte_db == 'gid') {
                        $output .= "<td class='data-cell' title='{$title}'>
                            <a class='akt-link2 box2 idx'
                            href=./details.php?id={$stamp[$id]}>{$stamp[$spalte_db]}</a></td>";

                    // Druckoption, startet JS: prn_toogle()
                    // Symbol per CSS
                    } elseif ($spalte_db === 'print') {
                        $data = $stamp[$spalte_db];
                        $fid = $stamp[$id];
                        $data_rev = ($data == 1) ? 0 : 1;      # switchen
                        $checked = ($data == 1) ? "checked" : "";
                        # onclick='return false;' .. disabled='disabled'  onclick='prn_toogle(".$stamp[$id].",".$data_rev.")'

                        $output .= "
                                <td class='data-cell noprint' title='druck ja/nein'
                                    style='text-align:center;'>
                                <input type='checkbox' name='{$spalte_db}'
                                    id='prn_{$fid}' class='chkbx noprint' {$checked} onclick='prn_toogle({$fid},{$data_rev})' />
                                <label for='prn_{$fid}'></label></td>";

                    // Ansicht
                    } elseif ($spalte_db == 'kat20') {
                        $output .= "<td class='data-cell' title='{$title}'>
                            <a class='akt-link2 box2' style='text-align:center;'
                            href=./details.php?id={$stamp[$id]}>{$stamp[$spalte_db]}</a></td>";

                    // alle anderen Spalten
                    } else {
                        // Datum umformatieren
                        if ($spalte_db == 'datum') {
                            ($stamp[$spalte_db])
                                ? $data = date("d.m.Y",strtotime($stamp[$spalte_db]))
                                : $data = '';
                        } else {
                            $data = $stamp[$spalte_db];
                        }

                        $output .= "<td class='data-cell' style='text-wrap: nowrap'
                            title='{$title}'><a class='akt-link2 box2'
                            href=./details.php?id={$stamp[$id]}>".$data."</a></td>";
                    } # else, andere Spalten
                }   # foreach, spaltennamen
            }     # if: Einzel-Liste


            // Gruppen-Liste
            //
            else {
                $id = 'fid';
                $title = "details";

                // Thumbs der Einzeldateien holen
                # $thumb_liste = getThumbBlob($stamp['gid']);
                $thumb_liste = TableData::getThumbPath($stamp['gid']);

                // Anzahl Dateien (Bilder) pro Gruppe
                $maxfile = count($thumb_liste);

                foreach ($spaltennamen AS $spalte_db => $spalte_web) {
                    // Thumbnail  ... id='{$stamp['gid']}'
                    if ($spalte_web == 'Pic0' ) {
                        $output .= "
                        <td class='data-cell'>
                        <div class='thumb-grid'>";   # 3-Elemente-Feld
                        if ($maxfile > 0) {
                            foreach ($thumb_liste AS $idx => $file) {
                                $output .= "<div class='detail-thumb' title='#{$file[$id]}'
                                    style='box-shadow:none;'>
                                    <a href='./details.php?id={$file[$id]}'
                                    title='#{$file[$id]}'><img src='/".
                                    htmlspecialchars($file['thumb']).
                                    "' width='50' height='50' alt='#{$file[$id]}'></a></div>";

                                # ."<img src='data:image/jpg;charset=utf8;base64,".
                                # base64_encode($file['thumb-blob'])
                            }
                        }
                        $output .= "</div></td>";
                    }
                    // ID's
                    elseif ($spalte_db == 'gid') {
                        $output .= "<td class='data-cell' title='{$title}'>
                            <a class='akt-link2 box2 idx' href=./details.php?id={$stamp[$id]}>".
                            $stamp[$spalte_db]."</a></td>";

                    // alle anderen Spalten
                    } else {
                        $data = $stamp[$spalte_db];
                        if ($spalte_db == 'datum') {
                            // Datum umformatieren
                            $data = $data
                                ? date("d.m.Y", strtotime($data))
                                : '';
                            $output .= "<td class='data-cell' style='text-wrap: nowrap'
                                title='{$title}' id='{$stamp['fid']}'>";
                        }
                        else {
                            $output .= "<td class='data-cell' style='text-wrap: nowrap'
                                title='{$title}'>";
                        }

                        $output .= "<a class='akt-link2 box2'
                            href=./details.php?id={$stamp[$id]}>".$data."</a></td>";
                    }
                }
            }   # else: Gruppen-Liste
            $output .= "<td></td></tr>";
        }   # foreach stamp

        $output .= "</tbody>";

        if (count($stamps_db) > 10000) {
            $output .= "&nbsp;";  # Leerzeile, wird am Anfang statt Ende eingefügt ???
        }

        return $output;
    }
}